<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SearchIndexUnavailable;
use App\Jobs\RebuildSearchGeneration;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use stdClass;
use Typesense\Client;
use Typesense\Exceptions\ObjectAlreadyExists;
use Typesense\Exceptions\ObjectNotFound;

/**
 * Builds independent collections while journaling live projection snapshots.
 * The manifest lock serializes cutover with projection acknowledgements;
 * Typesense requests always run outside these SQL transactions.
 */
final class SearchGenerations
{
    /** @var list<class-string<Contact|Organization|User>> */
    private const array MODELS = [Contact::class, Organization::class, User::class];

    public function __construct(private readonly Client $client, private readonly VersionedSearchDocuments $documents) {}

    public static function collection(Contact|Organization|User $model, ?string $generation): string
    {
        return $model->indexableAs().($generation === null ? '' : '__'.$generation);
    }

    public function activeGeneration(): ?string
    {
        return $this->manifest()->useWritePdo()->value('active_generation');
    }

    /** @return list<string|null> */
    public function schemaTargets(): array
    {
        $manifest = $this->manifest()->useWritePdo()->firstOrFail();

        return $manifest->building_generation === null
            ? [$manifest->active_generation]
            : [$manifest->active_generation, $manifest->building_generation];
    }

    /** @return 'active'|'retired'|'preparing'|'abandoned' */
    public function invalidateMissingCollection(?string $generation): string
    {
        return DB::transaction(function () use ($generation): string {
            $manifest = $this->manifest()->lockForUpdate()->firstOrFail();

            if ($manifest->active_generation === $generation) {
                Account::query()->update([
                    'indexed_revision' => null,
                    'search_revision' => DB::raw('search_revision + 1'),
                ]);

                return 'active';
            }

            if ($generation === null || $manifest->building_generation !== $generation) {
                return 'retired';
            }

            if ($manifest->phase === 'preparing') {
                return 'preparing';
            }

            DB::table('search_index_generations')->where('generation', $generation)->update(['retired_at' => now()]);
            $this->manifest()->update(['building_generation' => null, 'phase' => 'idle']);
            DB::table('search_generation_changes')->where('generation', $generation)->delete();

            return 'abandoned';
        });
    }

    /** @return array{active: ?string, building: ?string} */
    public function captureTargets(): array
    {
        $manifest = $this->lockedManifest();

        return [
            'active' => $manifest->active_generation,
            'building' => in_array($manifest->phase, ['backfill', 'catchup'], true) ? $manifest->building_generation : null,
        ];
    }

    /** @param array{active: ?string, building: ?string} $targets */
    public function isCurrent(array $targets): bool
    {
        return $this->lockedManifest()->active_generation === $targets['active'];
    }

    /**
     * @param  array{active: ?string, building: ?string}  $targets
     * @param  list<Contact|Organization|User>  $models
     */
    public function recordChanges(array $targets, array $models, int $accountId, int $revision): void
    {
        $current = $this->captureTargets();

        if ($current['active'] !== $targets['active']) {
            throw new SearchIndexUnavailable;
        }

        if ($targets['building'] === null || $targets['building'] !== $current['building'] || $models === []) {
            return;
        }

        DB::table('search_generation_changes')->insertOrIgnore(array_map(fn ($model): array => [
            'generation' => $targets['building'],
            'account_id' => $accountId,
            'model_class' => $model::class,
            'model_id' => $model->getKey(),
            'revision' => $revision,
        ], $models));
    }

    public function start(): string
    {
        if (config('scout.driver') !== 'typesense') {
            throw new LogicException('Background search generations require Typesense.');
        }

        return DB::transaction(function (): string {
            $manifest = $this->manifest()->lockForUpdate()->firstOrFail();
            $generation = $manifest->building_generation ?? (string) Str::uuid();

            if ($manifest->building_generation === null) {
                DB::table('search_index_generations')->insert(['generation' => $generation]);
                $this->manifest()->update([
                    'building_generation' => $generation,
                    'phase' => 'preparing',
                    'created_collections' => 0,
                    'account_cursor' => 0,
                    'account_id' => null,
                    'revision' => 0,
                    'stage' => 0,
                    'last_id' => 0,
                    'upper_id' => null,
                ]);
            }

            Bus::dispatch((new RebuildSearchGeneration($generation))->onConnection('search-index')->onQueue('search-index')->beforeCommit());

            return $generation;
        });
    }

    public function project(string $generation): bool
    {
        if (DB::getDriverName() === 'pgsql' && DB::selectOne('SHOW transaction_isolation', useReadPdo: false)->transaction_isolation !== 'read committed') {
            throw new SearchIndexUnavailable;
        }

        $remaining = Config::integer('search.projection_batch_size');
        $seconds = Config::integer('search.projection_seconds');

        if ($remaining < 1 || $remaining > 250 || $seconds < 1 || $seconds > 30) {
            throw new InvalidArgumentException('Projection portions require 1 to 250 documents and 1 to 30 seconds.');
        }

        $lock = Cache::lock('search-generation:'.$generation, Config::integer('search.lock_seconds'));

        if (! $lock->get()) {
            throw new SearchIndexUnavailable;
        }

        $deadline = hrtime(true) + $seconds * 1_000_000_000;

        try {
            while ($remaining > 0 && hrtime(true) < $deadline) {
                $manifest = $this->manifest()->useWritePdo()->firstOrFail();

                if ($manifest->building_generation !== $generation) {
                    return true;
                }

                if ($manifest->phase === 'preparing') {
                    $this->prepare($manifest, $generation);

                    continue;
                }

                if ($manifest->phase === 'backfill') {
                    $snapshot = DB::transaction(fn (): ?array => $this->snapshot($generation, $remaining));

                    if ($snapshot === null) {
                        continue;
                    }

                    foreach ($snapshot['models'] as $model) {
                        if ($remaining === 0 || hrtime(true) >= $deadline) {
                            return false;
                        }

                        $this->documents->write($model, $snapshot['account'], $snapshot['revision'], generation: $generation);
                        $remaining--;
                        if ($this->progress($generation, $snapshot)->update(['last_id' => $model->getKey()]) !== 1) {
                            return false;
                        }
                        $snapshot['lastId'] = $model->getKey();
                    }

                    continue;
                }

                $changes = DB::table('search_generation_changes')->where('generation', $generation)->orderBy('id')->limit($remaining)->get();

                foreach ($changes as $change) {
                    if ($remaining === 0 || hrtime(true) >= $deadline) {
                        return false;
                    }
                    $this->applyChange($change);
                    $remaining--;
                }

                if ($changes->isEmpty()) {
                    return $this->activate($generation);
                }
            }

            return false;
        } finally {
            $lock->release();
        }
    }

    public function prune(string $generation): void
    {
        if (! Str::isUuid($generation)) {
            throw new InvalidArgumentException('A retired search generation must be identified by its UUID.');
        }

        DB::transaction(function () use ($generation): void {
            $manifest = $this->lockedManifest();
            if ($manifest->active_generation === $generation || $manifest->building_generation === $generation
                || ! DB::table('search_index_generations')->where('generation', $generation)->whereNotNull('retired_at')->exists()) {
                throw new InvalidArgumentException('Only a recorded, retired search generation may be pruned.');
            }
        });

        foreach (self::MODELS as $class) {
            try {
                $this->client->getCollections()->{self::collection(new $class, $generation)}->delete();
            } catch (ObjectNotFound) {
                // A previous attempt may already have removed this retired collection.
            }
        }
    }

    private function prepare(stdClass $manifest, string $generation): void
    {
        $position = (int) $manifest->created_collections;

        if ($position === count(self::MODELS)) {
            $this->manifest()->where('building_generation', $generation)->where('phase', 'preparing')
                ->where('created_collections', $position)->update(['phase' => 'backfill']);

            return;
        }

        $class = self::MODELS[$position] ?? throw new LogicException('Invalid candidate collection checkpoint.');
        $schema = Config::array('scout.typesense.model-settings.'.$class.'.collection-schema');

        try {
            $this->client->getCollections()->create(['name' => self::collection(new $class, $generation), ...$schema]);
        } catch (ObjectAlreadyExists) {
            $this->client->getCollections()->{self::collection(new $class, $generation)}->retrieve();
        }

        $this->manifest()->where('building_generation', $generation)->where('phase', 'preparing')
            ->where('created_collections', $position)->update(['created_collections' => $position + 1]);
    }

    /** @return array{account: int, revision: int, stage: int, lastId: int, models: list<Contact|Organization|User>}|null */
    private function snapshot(string $generation, int $limit): ?array
    {
        $manifest = $this->manifest()->lockForUpdate()->firstOrFail();

        if ($manifest->building_generation !== $generation || $manifest->phase !== 'backfill') {
            return null;
        }

        if ($manifest->account_id === null) {
            $account = Account::query()->useWritePdo()->where('id', '>', $manifest->account_cursor)->orderBy('id')->first();

            if ($account === null) {
                $this->manifest()->update(['phase' => 'catchup']);

                return null;
            }

            $this->manifest()->update(['account_id' => $account->id, 'revision' => $account->search_revision]);
            $manifest->account_id = $account->id;
            $manifest->revision = $account->search_revision;
        }

        if ($manifest->stage === count(self::MODELS)) {
            $this->manifest()->update(['account_cursor' => $manifest->account_id, 'account_id' => null, 'stage' => 0, 'last_id' => 0, 'upper_id' => null]);

            return null;
        }

        $class = self::MODELS[$manifest->stage] ?? throw new LogicException('Invalid candidate model checkpoint.');
        $query = $class::withTrashed()->where('account_id', $manifest->account_id);
        if ($class === Contact::class) {
            $query->with('organization');
        }
        if ($manifest->upper_id === null) {
            $manifest->upper_id = (int) (clone $query)->max('id');
            $this->manifest()->update(['upper_id' => $manifest->upper_id]);
        }
        $models = $query->where('id', '<=', $manifest->upper_id)->forPageAfterId($limit, $manifest->last_id)->get()->all();

        if ($models === []) {
            $this->manifest()->update(['stage' => $manifest->stage + 1, 'last_id' => 0, 'upper_id' => null]);

            return null;
        }

        return ['account' => (int) $manifest->account_id, 'revision' => (int) $manifest->revision, 'stage' => (int) $manifest->stage, 'lastId' => (int) $manifest->last_id, 'models' => array_values($models)];
    }

    private function applyChange(stdClass $change): void
    {
        [$model, $revision] = DB::transaction(function () use ($change): array {
            $this->lockedManifest();
            $account = Account::query()->lockForUpdate()->whereKey($change->account_id)->first();
            $class = $change->model_class;
            if (! in_array($class, self::MODELS, true)) {
                throw new LogicException('Unexpected model in the candidate search journal.');
            }
            $model = $class::withTrashed()->whereKey($change->model_id)->first() ?? (new $class)->forceFill(['id' => $change->model_id, 'account_id' => $change->account_id]);
            if ($model->getAttribute('account_id') !== $change->account_id) {
                throw new LogicException('A candidate search change cannot move between accounts.');
            }
            if ($model instanceof Contact && $model->exists) {
                $model->load('organization');
            }

            return [$model, max((int) $change->revision, (int) ($account->search_revision ?? 0))];
        });

        $this->documents->write($model, (int) $change->account_id, $revision, ! $model->exists, $change->generation);
        DB::table('search_generation_changes')->where('id', $change->id)->where('generation', $change->generation)->delete();
    }

    private function activate(string $generation): bool
    {
        return DB::transaction(function () use ($generation): bool {
            $manifest = $this->manifest()->lockForUpdate()->firstOrFail();
            if ($manifest->building_generation !== $generation) {
                return true;
            }
            if ($manifest->phase !== 'catchup' || DB::table('search_generation_changes')->where('generation', $generation)->exists()) {
                return false;
            }
            if (Account::query()->useWritePdo()->whereNull('indexed_revision')->orWhereColumn('indexed_revision', '!=', 'search_revision')->exists()) {
                throw new SearchIndexUnavailable;
            }
            if ($manifest->active_generation !== null) {
                DB::table('search_index_generations')->where('generation', $manifest->active_generation)->update(['retired_at' => now()]);
            }
            $this->manifest()->update(['active_generation' => $generation, 'building_generation' => null, 'phase' => 'idle']);

            return true;
        });
    }

    /** @param array{account: int, revision: int, stage: int, lastId: int, models: list<Contact|Organization|User>} $snapshot */
    private function progress(string $generation, array $snapshot): Builder
    {
        return $this->manifest()->where('building_generation', $generation)->where('phase', 'backfill')
            ->where('account_id', $snapshot['account'])->where('revision', $snapshot['revision'])
            ->where('stage', $snapshot['stage'])->where('last_id', $snapshot['lastId']);
    }

    private function lockedManifest(): stdClass
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Search generation snapshots and acknowledgements require a database transaction.');
        }

        return $this->manifest()->sharedLock()->firstOrFail();
    }

    private function manifest(): Builder
    {
        return DB::table('search_index_manifest')->where('id', 1);
    }
}
