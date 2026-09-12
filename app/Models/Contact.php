<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Laravel\Scout\Searchable;

#[Fillable([
    'account_id',
    'organization_id',
    'first_name',
    'last_name',
    'email',
    'phone',
    'address',
    'city',
    'region',
    'country',
    'postal_code',
])]
final class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use Concerns\Filterable, HasFactory, Searchable, SoftDeletes;

    /**
     * Retrieve the model for a bound value.
     *
     * @param  string|null  $field
     */
    public function resolveRouteBinding(mixed $value, mixed $field = null): static
    {
        return $this->where($field ?? 'id', $value)
            ->where('account_id', Auth::user()?->account_id)
            ->withTrashed()
            ->firstOrFail();
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'account_id' => $this->account_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email ?? '',
            'organization_name' => $this->organization->name ?? '',
            'created_at' => $this->created_at->timestamp ?? 0,
        ];
    }

    /** @return Attribute<string, never> */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->first_name.' '.$this->last_name,
        );
    }

    /** @return list<string> */
    protected function searchRelations(): array
    {
        return ['organization'];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with('organization');
    }
}
