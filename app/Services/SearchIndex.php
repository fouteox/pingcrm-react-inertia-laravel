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
use Illuminate\Database\Eloquent\Model;
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

    public function rebuild(int $accountId): void
    {
        if (! $this->usesTypesense()) {
            return;
        }

        DB::transaction(function () use ($accountId): void {
            $account = Account::query()->lockForUpdate()->findOrFail($accountId);
            $revision = (int) $account->getAttribute('search_revision') + 1;
            $account->forceFill(['search_revision' => $revision, 'search_rebuild_revision' => $revision])->save();
            $this->enqueue(new SearchIndexProjection($accountId, $revision));
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

    public function project(SearchIndexProjection $projection): void
    {
        $lock = Cache::lock('search-index:'.$projection->accountId, Config::integer('search.lock_seconds'));

        if (! $lock->get()) {
            throw new SearchIndexUnavailable;
        }

        try {
            $snapshot = DB::transaction(fn (): ?array => $this->snapshot($projection));

            if ($snapshot === null) {
                return;
            }

            foreach ($snapshot as $model) {
                $this->documents->write($model, $projection->accountId, $projection->revision, ! $model->exists);
            }

            if ($projection->modelClass === null) {
                foreach (self::MODELS as $modelClass) {
                    $currentIds = [];

                    foreach ($snapshot as $model) {
                        if ($model::class === $modelClass) {
                            $currentIds[] = $model->getKey();
                        }
                    }

                    foreach (array_diff($this->documents->ids(new $modelClass, $projection->accountId), $currentIds) as $id) {
                        $missing = (new $modelClass)->forceFill(['id' => $id, 'account_id' => $projection->accountId]);
                        $this->documents->write($missing, $projection->accountId, $projection->revision, deleted: true);
                    }
                }
            }

            $acknowledgement = DB::table('accounts')->where('id', $projection->accountId);

            if ($projection->modelClass === null) {
                $acknowledgement->where(fn ($query) => $query->whereNull('indexed_revision')->orWhere('indexed_revision', '<', $projection->revision));
            } else {
                $acknowledgement->where('indexed_revision', $projection->revision - 1);
            }

            $acknowledgement->update(['indexed_revision' => $projection->revision]);
        } finally {
            $lock->release();
        }
    }

    /** @return list<Contact|Organization|User>|null */
    private function snapshot(SearchIndexProjection $projection): ?array
    {
        $account = Account::query()->lockForUpdate()->findOrFail($projection->accountId);
        $indexed = $account->getAttribute('indexed_revision');

        if ($indexed !== null && $projection->revision <= $indexed) {
            return null;
        }

        if ($projection->revision > $account->getAttribute('search_revision')) {
            throw new InvalidArgumentException('A projection cannot acknowledge an unrequested revision.');
        }

        if ($projection->modelClass === null) {
            $models = [];

            foreach (self::MODELS as $modelClass) {
                $query = $modelClass::withTrashed()->where('account_id', $projection->accountId);

                if ($modelClass === Contact::class) {
                    $query->with('organization');
                }

                array_push($models, ...$query->get()->all());
            }

            return $models;
        }

        if ($indexed === null || $projection->revision !== $indexed + 1) {
            throw new SearchIndexUnavailable;
        }

        if (! in_array($projection->modelClass, self::MODELS, true) || $projection->modelId === null) {
            throw new InvalidArgumentException('Unsupported search projection model.');
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

        return $model instanceof Organization
            ? [$model, ...$model->contacts()->withTrashed()->with('organization')->get()->all()]
            : [$model];
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
