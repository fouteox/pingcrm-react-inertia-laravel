<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Role;
use App\Enums\TrashedFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Builder as SearchBuilder;
use Laravel\Scout\Engines\TypesenseEngine;
use Laravel\Scout\Searchable;
use Typesense\Client;
use Typesense\Documents;
use Typesense\Exceptions\TypesenseClientError;

/**
 * @mixin Searchable
 */
trait Filterable
{
    /**
     * Within an existing transaction, the caller must provide a consistent database snapshot.
     *
     * @param  array{search?: string, role?: string, trashed?: string}  $filters
     * @return LengthAwarePaginator<int, self>
     */
    public static function paginateFiltered(array $filters, int $accountId): LengthAwarePaginator
    {
        $model = new self;
        $search = $filters['search'] ?? null;
        $databaseQuery = $model->newQuery()->where('account_id', $accountId);
        $model->applyFilters($databaseQuery, $filters);

        if ($search === null) {
            return $databaseQuery->with($model->searchRelations())->orderByName()->paginate();
        }

        if ($model->searchableUsing() instanceof TypesenseEngine) {
            return $model->paginateTypesense($databaseQuery, $search);
        }

        $query = static::search($search)->where('account_id', $accountId);
        $model->applyFilters($query, $filters);

        return $model->paginateSearch($query, orderByKey: true);
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function orderByName(Builder $query): void
    {
        foreach ($this->nameOrderColumns() as $column) {
            $query->orderBy($column);
        }

        $query->orderBy($this->getKeyName());
    }

    /**
     * @return list<string>
     */
    protected function nameOrderColumns(): array
    {
        return ['last_name', 'first_name'];
    }

    /**
     * @return list<string>
     */
    protected function searchRelations(): array
    {
        return [];
    }

    /**
     * @param  Builder<self>  $databaseQuery
     * @return LengthAwarePaginator<int, self>
     */
    private function paginateTypesense(Builder $databaseQuery, string $search): LengthAwarePaginator
    {
        return $this->getConnection()->transaction(function () use ($databaseQuery, $search): LengthAwarePaginator {
            $connection = $this->getConnection();

            if ($connection->getDriverName() === 'pgsql' && $connection->transactionLevel() === 1) {
                $connection->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ, READ ONLY');
            }

            $ids = $databaseQuery->pluck($this->getKeyName());

            if ($ids->isEmpty()) {
                return $databaseQuery->paginate();
            }

            $query = static::search($search, function (Documents $documents, string $term, array $parameters): array {
                $response = app(Client::class)->getMultiSearch()->perform([
                    'searches' => [['collection' => $this->indexableAs(), ...$parameters]],
                ]);
                $result = $response['results'][0];

                if (isset($result['error'])) {
                    throw new TypesenseClientError($result['error'], $result['code']);
                }

                return $result;
            })->withTrashed()->whereIn('id', $ids)->options(['filter_curated_hits' => true]);

            return $this->paginateSearch($query);
        });
    }

    /**
     * @param  SearchBuilder<self>  $query
     * @return LengthAwarePaginator<int, self>
     */
    private function paginateSearch(SearchBuilder $query, bool $orderByKey = false): LengthAwarePaginator
    {
        foreach ($this->nameOrderColumns() as $column) {
            $query->orderBy($column);
        }

        if ($orderByKey) {
            $query->orderBy($this->getKeyName());
        }

        $paginator = $query->paginate()->appends(['query' => null]);
        $this->newCollection($paginator->items())->loadMissing($this->searchRelations());

        return $paginator;
    }

    /**
     * @param  array{search?: string, role?: string, trashed?: string}  $filters
     * @param  Builder<self>|SearchBuilder<self>  $query
     */
    private function applyFilters(Builder|SearchBuilder $query, array $filters): void
    {
        $trashed = TrashedFilter::tryFrom($filters['trashed'] ?? '');

        if ($trashed === TrashedFilter::With) {
            $query->withTrashed();
        } elseif ($trashed === TrashedFilter::Only) {
            $query->onlyTrashed();
        }

        $role = Role::tryFrom($filters['role'] ?? '');

        if ($role !== null) {
            $query->where('owner', $role === Role::Owner);
        }
    }
}
