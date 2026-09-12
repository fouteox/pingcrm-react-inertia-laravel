<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Role;
use App\Enums\TrashedFilter;
use App\Exceptions\SearchIndexUnavailable;
use App\Services\SearchIndex;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Builder as SearchBuilder;
use Laravel\Scout\Engines\TypesenseEngine;
use Laravel\Scout\Searchable;
use Psr\Http\Client\ClientExceptionInterface;
use Typesense\Documents;
use Typesense\Exceptions\TypesenseClientError;

/**
 * @mixin Searchable
 */
trait Filterable
{
    /**
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
            return $model->paginateTypesense($databaseQuery, $search, $filters, $accountId);
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
     * @param  array{search?: string, role?: string, trashed?: string}  $filters
     * @return LengthAwarePaginator<int, self>
     */
    private function paginateTypesense(Builder $databaseQuery, string $search, array $filters, int $accountId): LengthAwarePaginator
    {
        $index = app(SearchIndex::class);
        $state = $index->readyState($accountId);
        $query = static::search($search, fn (Documents $documents, string $term, array $parameters): array => $documents->search($parameters))
            ->where('account_id', $accountId)
            ->where('search_deleted', false)
            ->where('search_revision', '>=', $state['rebuildRevision'])
            ->options(['filter_curated_hits' => true])
            ->withRawResults(function (array $results): void {
                if ($results['search_cutoff'] ?? false) {
                    throw new SearchIndexUnavailable;
                }
            });
        $this->applyFilters($query, $filters);

        try {
            $paginator = $this->paginateSearch($query);
        } catch (TypesenseClientError|ClientExceptionInterface $exception) {
            throw new SearchIndexUnavailable($exception);
        }

        $expectedCount = min($paginator->perPage(), max(0, $paginator->total() - ($paginator->currentPage() - 1) * $paginator->perPage()));
        $ids = $this->newCollection($paginator->items())->modelKeys();

        if (count($ids) !== $expectedCount || $databaseQuery->whereKey($ids)->count() !== count($ids)) {
            throw new SearchIndexUnavailable;
        }

        $index->assertUnchanged($accountId, $state['revision']);

        return $paginator;
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
