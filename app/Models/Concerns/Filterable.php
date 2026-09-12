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
use LogicException;

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

        if ($search === null) {
            $query = $model->newQuery()->where('account_id', $accountId)
                ->with($model->searchRelations())->orderByName();
            $model->applyFilters($query, $filters);

            return $query->paginate();
        }

        $query = static::search($search)->where('account_id', $accountId);
        $model->applyFilters($query, $filters);

        foreach ($model->nameOrderColumns() as $column) {
            $query->orderBy($column);
        }

        $query->orderBy($model->searchableUsing() instanceof TypesenseEngine ? 'sort_id' : $model->getKeyName());

        $paginator = $query->paginate()->appends(['query' => null]);

        if (! $paginator instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            throw new LogicException('Search pagination must support replacing its collection.');
        }

        $items = $model->newCollection($paginator->items())->where('account_id', $accountId);
        $items = match (TrashedFilter::tryFrom($filters['trashed'] ?? '')) {
            TrashedFilter::With => $items,
            TrashedFilter::Only => $items->whereNotNull('deleted_at'),
            default => $items->whereNull('deleted_at'),
        };

        if (($role = Role::tryFrom($filters['role'] ?? '')) !== null) {
            $items = $items->where('owner', $role === Role::Owner);
        }

        $paginator->setCollection($items->values()->loadMissing($model->searchRelations()));

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
