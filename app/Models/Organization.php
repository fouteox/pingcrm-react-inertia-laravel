<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Laravel\Scout\Searchable;

#[Fillable([
    'account_id',
    'name',
    'email',
    'phone',
    'address',
    'city',
    'region',
    'country',
    'postal_code',
])]
final class Organization extends Model
{
    use Concerns\Filterable, HasFactory, Searchable, SoftDeletes;

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->where($field ?? 'id', $value)
            ->where('account_id', Auth::user()?->account_id)
            ->withTrashed()
            ->firstOrFail();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'account_id' => $this->account_id,
            'name' => $this->name,
            'created_at' => $this->created_at?->timestamp ?? 0,
        ];
    }

    #[Scope]
    public function filter(Builder $query, array $filters, int $accountId): void
    {
        $query
            ->when($filters['search'] ?? null, fn ($query, $search) => $this->applySearchFilter($query, $search, $accountId, $filters['trashed'] ?? null))
            ->when($filters['trashed'] ?? null, fn ($query, $trashed) => $this->applyTrashedFilter($query, $trashed));
    }

    protected static function booted(): void
    {
        self::updated(function (Organization $organization): void {
            if ($organization->isDirty('name')) {
                $organization->contacts->searchable();
            }
        });
    }
}
