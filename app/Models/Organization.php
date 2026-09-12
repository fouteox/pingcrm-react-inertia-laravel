<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Laravel\Scout\ModelObserver;
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
    /** @use HasFactory<OrganizationFactory> */
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

    /** @return HasMany<Contact, $this> */
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
            'created_at' => $this->created_at->timestamp ?? 0,
        ];
    }

    protected static function booted(): void
    {
        self::updated(function (Organization $organization): void {
            if ($organization->isDirty('name')) {
                $organization->reindexContacts();
            }
        });

        self::deleted(fn (Organization $organization) => $organization->reindexContacts());
        self::restored(fn (Organization $organization) => $organization->reindexContacts());
    }

    /** @return list<string> */
    protected function nameOrderColumns(): array
    {
        return ['name'];
    }

    private function reindexContacts(): void
    {
        if (ModelObserver::syncingDisabledFor($this)) {
            return;
        }

        $this->contacts()->withTrashed()->with('organization')
            ->chunkById(
                Config::integer('scout.chunk.searchable'),
                fn ($contacts) => $contacts->firstOrFail()->queueMakeSearchable($contacts)
            );
    }
}
