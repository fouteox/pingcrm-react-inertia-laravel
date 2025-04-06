<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Contact extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->first_name.' '.$this->last_name,
        );
    }

    #[Scope]
    public function orderByName(Builder $query): void
    {
        $query->orderBy('last_name')->orderBy('first_name');
    }

    #[Scope]
    public function filter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['search'] ?? null, fn ($query, $search) => $this->applySearchFilter($query, $search))
            ->when($filters['trashed'] ?? null, fn ($query, $trashed) => $this->applyTrashedFilter($query, $trashed));
    }

    private function applySearchFilter(Builder $query, string $search): void
    {
        $query->where(function ($query) use ($search) {
            $query
                ->whereAny(['first_name', 'last_name', 'email'], 'LIKE', '%'.$search.'%')
                ->orWhereRelation('organization', 'name', 'like', '%'.$search.'%');
        });
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
