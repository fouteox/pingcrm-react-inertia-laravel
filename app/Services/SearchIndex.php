<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SearchIndexUnavailable;
use App\Jobs\SearchIndexProjection;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

final class SearchIndex
{
    /** @var list<class-string<Contact|Organization|User>> */
    private const array MODELS = [Contact::class, Organization::class, User::class];

    public function __construct(private readonly VersionedSearchDocuments $documents) {}

    /**
     * @template TModel of Contact|Organization|User
     *
     * @param  Closure(): TModel  $mutation
     * @return TModel
     */
    public function mutate(int $accountId, Closure $mutation): Model
    {
        return DB::transaction(function () use ($accountId, $mutation): Model {
            $account = Account::query()->lockForUpdate()->findOrFail($accountId);
            $model = $this->usesTypesense() ? $this->withoutSearchSyncing($mutation) : $mutation();

            if (! in_array($model::class, self::MODELS, true) || $model->getAttribute('account_id') !== $accountId) {
                throw new InvalidArgumentException('Search mutations must return a model belonging to the locked account.');
            }

            if ($this->usesTypesense()) {
                $revision = (int) $account->getAttribute('search_revision') + 1;
                $fullRebuild = $model instanceof Organization && ! $model->exists;
                $account->forceFill([
                    'search_revision' => $revision,
                    ...($fullRebuild ? ['search_rebuild_revision' => $revision] : []),
                ])->save();
                $this->enqueue($fullRebuild
                    ? new SearchIndexProjection($accountId, $revision)
                    : new SearchIndexProjection($accountId, $revision, $model::class, $model->getKey()));
            }

            return $model;
        });
    }

    public function rebuild(int $accountId): ?SearchIndexProjection
    {
        if (! $this->usesTypesense()) {
            return null;
        }

        return DB::transaction(function () use ($accountId): SearchIndexProjection {
            $account = Account::query()->lockForUpdate()->findOrFail($accountId);
            $revision = (int) $account->getAttribute('search_revision') + 1;
            $account->forceFill(['search_revision' => $revision, 'search_rebuild_revision' => $revision])->save();
            $projection = new SearchIndexProjection($accountId, $revision);
            $this->enqueue($projection);

            return $projection;
        });
    }

    /** @return array{revision: int, indexedRevision: ?int, rebuildRevision: int} */
    public function readState(int $accountId): array
    {
        $connection = DB::connection();

        if ($connection->transactionLevel() > 0 && $connection->getDriverName() === 'pgsql') {
            $isolation = $connection->selectOne('SHOW transaction_isolation', useReadPdo: false);

            if ($isolation->transaction_isolation !== 'read committed') {
                throw new SearchIndexUnavailable;
            }
        }

        return $this->stateOn($connection, $accountId);
    }

    public function assertReady(int $accountId): int
    {
        return $this->readyState($accountId)['revision'];
    }

    /** @return array{revision: int, indexedRevision: ?int, rebuildRevision: int} */
    public function readyState(int $accountId): array
    {
        $state = $this->readState($accountId);

        if ($state['revision'] !== $state['indexedRevision']) {
            throw new SearchIndexUnavailable;
        }

        return $state;
    }

    public function assertUnchanged(int $accountId, int $revision): void
    {
        if ($this->assertReady($accountId) !== $revision) {
            throw new SearchIndexUnavailable;
        }
    }

    /** Return whether this projection has completed or been superseded by a rebuild. */
    public function project(SearchIndexProjection $projection): bool
    {
        $remaining = Config::integer('search.projection_batch_size');
        $seconds = Config::integer('search.projection_seconds');

        if ($remaining < 1 || $remaining > 250 || $seconds < 1 || $seconds > 30) {
            throw new InvalidArgumentException('Projection portions require 1 to 250 documents and 1 to 30 seconds.');
        }

        $lock = Cache::lock('search-index:'.$projection->accountId, Config::integer('search.lock_seconds'));

        if (! $lock->get()) {
            throw new SearchIndexUnavailable;
        }

        $deadline = hrtime(true) + $seconds * 1_000_000_000;

        try {
            while ($remaining > 0 && hrtime(true) < $deadline) {
                $snapshot = DB::transaction(fn (): ?array => $this->snapshot($projection, $remaining));

                if ($snapshot === null) {
                    return true;
                }

                $stage = $snapshot['stage'];
                $lastId = $snapshot['id'];
                $models = $snapshot['models'];
                $cleanup = $projection->modelClass === null && $stage >= count(self::MODELS);

                if ($cleanup) {
                    $modelClass = self::MODELS[$stage - count(self::MODELS)];
                    $models = array_map(
                        fn (int $id) => (new $modelClass)->forceFill(['id' => $id, 'account_id' => $projection->accountId]),
                        $this->documents->staleIds(new $modelClass, $projection->accountId, $projection->revision, $remaining),
                    );
                }

                if ($models === []) {
                    if ($this->progressQuery($projection, $stage, $lastId)->update([
                        'search_projection_stage' => $stage + 1,
                        'search_projection_id' => 0,
                        'search_projection_upper_id' => null,
                    ]) !== 1) {
                        return false;
                    }

                    continue;
                }

                foreach ($models as $model) {
                    if ($remaining === 0 || hrtime(true) >= $deadline) {
                        return false;
                    }

                    $this->documents->write($model, $projection->accountId, $projection->revision, ! $model->exists);
                    $remaining--;

                    if ($cleanup) {
                        if (! $this->progressQuery($projection, $stage, $lastId)->exists()) {
                            return false;
                        }
                    } else {
                        if ($this->progressQuery($projection, $stage, $lastId)->update(['search_projection_id' => $model->getKey()]) !== 1) {
                            return false;
                        }

                        $lastId = $model->getKey();
                    }
                }
            }

            return false;
        } finally {
            $lock->release();
        }
    }

    /** @return array{stage: int, id: int, models: list<Contact|Organization|User>}|null */
    private function snapshot(SearchIndexProjection $projection, int $limit): ?array
    {
        $account = Account::query()->lockForUpdate()->findOrFail($projection->accountId);
        $indexed = $account->getAttribute('indexed_revision');

        if (($indexed !== null && $projection->revision <= $indexed)
            || $projection->revision < $account->getAttribute('search_rebuild_revision')) {
            return null;
        }

        if ($projection->revision > $account->getAttribute('search_revision')) {
            throw new InvalidArgumentException('A projection cannot acknowledge an unrequested revision.');
        }

        if ($projection->modelClass !== null) {
            if ($indexed === null || $projection->revision !== $indexed + 1) {
                throw new SearchIndexUnavailable;
            }

            if (! in_array($projection->modelClass, self::MODELS, true) || $projection->modelId === null || $projection->modelId < 1) {
                throw new InvalidArgumentException('Unsupported search projection model.');
            }
        }

        if ($account->getAttribute('search_projection_revision') !== $projection->revision) {
            $account->forceFill([
                'search_projection_revision' => $projection->revision,
                'search_projection_stage' => 0,
                'search_projection_id' => 0,
                'search_projection_upper_id' => null,
            ])->save();
        }

        $stage = (int) $account->getAttribute('search_projection_stage');
        $lastId = (int) $account->getAttribute('search_projection_id');
        $stages = $projection->modelClass === null ? count(self::MODELS) * 2 : ($projection->modelClass === Organization::class ? 2 : 1);

        if ($stage === $stages) {
            $acknowledgement = $this->progressQuery($projection, $stage, $lastId);

            if ($projection->modelClass === null) {
                $acknowledgement->where(fn ($query) => $query->whereNull('indexed_revision')->orWhere('indexed_revision', '<', $projection->revision));
            } else {
                $acknowledgement->where('indexed_revision', $projection->revision - 1);
            }

            $acknowledgement->update([
                'indexed_revision' => $projection->revision,
                'search_projection_revision' => null,
                'search_projection_stage' => 0,
                'search_projection_id' => 0,
                'search_projection_upper_id' => null,
            ]);

            return null;
        }

        if ($account->getAttribute('search_projection_upper_id') === null) {
            $upperId = 0;

            if ($projection->modelClass !== null && $stage === 0) {
                $upperId = $projection->modelId;
            } elseif ($stage < count(self::MODELS)) {
                $upperId = (int) $this->modelQuery($projection, $stage)->max('id');
            }
            $account->forceFill(['search_projection_upper_id' => $upperId])->save();
        }

        return [
            'stage' => $stage,
            'id' => $lastId,
            'models' => $this->models($projection, $stage, $lastId, (int) $account->getAttribute('search_projection_upper_id'), $limit),
        ];
    }

    /** @return list<Contact|Organization|User> */
    private function models(SearchIndexProjection $projection, int $stage, int $lastId, int $upperId, int $limit): array
    {
        if ($projection->modelClass !== null && $stage === 0) {
            if ($lastId !== 0) {
                return [];
            }

            $model = $projection->modelClass::withTrashed()->find($projection->modelId);

            if ($model === null) {
                return [(new $projection->modelClass)->forceFill(['id' => $projection->modelId, 'account_id' => $projection->accountId])];
            }

            if ($model->getAttribute('account_id') !== $projection->accountId) {
                throw new InvalidArgumentException('A projection cannot index a model belonging to another account.');
            }

            if ($model instanceof Contact) {
                $model->load('organization');
            }

            return [$model];
        }

        if ($projection->modelClass === null && $stage >= count(self::MODELS)) {
            return [];
        }

        return array_values($this->modelQuery($projection, $stage)->where('id', '<=', $upperId)
            ->forPageAfterId($limit, $lastId)->get()->all());
    }

    /** @return EloquentBuilder<Contact>|EloquentBuilder<Organization>|EloquentBuilder<User> */
    private function modelQuery(SearchIndexProjection $projection, int $stage): EloquentBuilder
    {
        $modelClass = $projection->modelClass === null ? self::MODELS[$stage] : Contact::class;
        $query = $modelClass::withTrashed()->where('account_id', $projection->accountId);

        if ($projection->modelClass === Organization::class) {
            $query->where('organization_id', $projection->modelId);
        }

        if ($modelClass === Contact::class) {
            $query->with('organization');
        }

        return $query;
    }

    private function progressQuery(SearchIndexProjection $projection, int $stage, int $lastId): QueryBuilder
    {
        return DB::table('accounts')->where('id', $projection->accountId)
            ->where('search_projection_revision', $projection->revision)
            ->where('search_projection_stage', $stage)
            ->where('search_projection_id', $lastId)
            ->where('search_rebuild_revision', '<=', $projection->revision);
    }

    private function enqueue(SearchIndexProjection $projection): void
    {
        Bus::dispatch($projection->onConnection('search-index')->onQueue('search-index')->beforeCommit());

        if (Config::boolean('search.synchronous')) {
            DB::afterCommit(function () use ($projection): void {
                try {
                    $this->project($projection);
                } catch (SearchIndexUnavailable) {
                    // The durable worker retries pending predecessors and lock contention.
                } catch (Throwable $exception) {
                    report($exception);
                }
            });
        }
    }

    /** @return array{revision: int, indexedRevision: ?int, rebuildRevision: int} */
    private function stateOn(Connection $connection, int $accountId): array
    {
        $state = $connection->table('accounts')->useWritePdo()->where('id', $accountId)->firstOrFail();

        return [
            'revision' => (int) $state->search_revision,
            'indexedRevision' => $state->indexed_revision === null ? null : (int) $state->indexed_revision,
            'rebuildRevision' => (int) $state->search_rebuild_revision,
        ];
    }

    private function usesTypesense(): bool
    {
        return config('scout.driver') === 'typesense';
    }

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    private function withoutSearchSyncing(Closure $callback): mixed
    {
        return Contact::withoutSyncingToSearch(
            fn () => Organization::withoutSyncingToSearch(
                fn () => User::withoutSyncingToSearch($callback)
            )
        );
    }
}
