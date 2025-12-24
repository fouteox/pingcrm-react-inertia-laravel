<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Searchable;

/**
 * @mixin Searchable
 */
trait Filterable
{
    private function applySearchFilter(Builder $query, string $search, int $accountId, ?string $trashed): void
    {
        $builder = static::search($search)->where('account_id', $accountId);

        if ($trashed === 'with') {
            $builder->withTrashed();
        } elseif ($trashed === 'only') {
            $builder->onlyTrashed();
        }

        $query->whereIn('id', $builder->keys()->toArray());
    }

    private function applyTrashedFilter(Builder $query, string $trashed): void
    {
        if ($trashed === 'with') {
            $query->withTrashed();
        } elseif ($trashed === 'only') {
            $query->onlyTrashed();
        }
    }
}
