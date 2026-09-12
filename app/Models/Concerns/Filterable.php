<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Role;
use App\Enums\TrashedFilter;
use App\Exceptions\SearchIndexUnavailable;
use App\Services\SearchGenerations;
use App\Services\SearchIndex;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as PaginatorContract;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Scout\Builder as SearchBuilder;
use Laravel\Scout\Engines\TypesenseEngine;
use Laravel\Scout\Searchable;
use Psr\Http\Client\ClientExceptionInterface;
use Typesense\Documents;
use Typesense\Exceptions\ObjectNotFound;
use Typesense\Exceptions\TypesenseClientError;

/**
 * @mixin Searchable
 */
trait Filterable
{
    /**
     * @param  array{search?: string, role?: string, trashed?: string}  $filters
     * @return PaginatorContract<int, self>
     */
    public static function paginateFiltered(array $filters, int $accountId): PaginatorContract
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

        $paginator = $model->orderSearch($query, orderByKey: true)->paginate()->appends(['query' => null]);
        $model->newCollection($paginator->items())->loadMissing($model->searchRelations());

        return $paginator;
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
    private function paginateTypesense(Builder $databaseQuery, string $search, array $filters, int $accountId, bool $retryOnGenerationChange = true): LengthAwarePaginator
    {
        $generation = app(SearchGenerations::class)->activeGeneration();
        $query = static::search($search, fn (Documents $documents, string $term, array $parameters): array => $documents->search($parameters))
            ->within(SearchGenerations::collection($this, $generation))
            ->where('account_id', $accountId)
            ->where('search_deleted', false)
            ->where('search_revision', '>=', app(SearchIndex::class)->completedRebuildRevision($accountId))
            ->options(['filter_curated_hits' => true]);
        $this->applyFilters($query, $filters);

        try {
            $paginator = $this->orderSearch($query)->paginateRaw();
        } catch (ObjectNotFound $exception) {
            if ($retryOnGenerationChange && $generation !== app(SearchGenerations::class)->activeGeneration()) {
                return $this->paginateTypesense($databaseQuery, $search, $filters, $accountId, retryOnGenerationChange: false);
            }

            throw new SearchIndexUnavailable($exception);
        } catch (TypesenseClientError|ClientExceptionInterface $exception) {
            throw new SearchIndexUnavailable($exception);
        }

        $ids = $this->searchableUsing()->mapIds($paginator->items())->all();
        $positions = array_flip($ids);
        $models = $databaseQuery->whereKey($ids)->with($this->searchRelations())->get()
            ->sortBy(fn (self $model): int => $positions[$model->getScoutKey()])->values();

        return new LengthAwarePaginator($models, $paginator->total(), $paginator->perPage(), $paginator->currentPage(), [
            'path' => $paginator->path(),
        ]);
    }

    /**
     * @param  SearchBuilder<self>  $query
     * @return SearchBuilder<self>
     */
    private function orderSearch(SearchBuilder $query, bool $orderByKey = false): SearchBuilder
    {
        foreach ($this->nameOrderColumns() as $column) {
            $query->orderBy($column);
        }

        if ($orderByKey) {
            $query->orderBy($this->getKeyName());
        }

        return $query;
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
