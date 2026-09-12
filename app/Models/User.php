<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Scout\Searchable;

#[Fillable([
    'account_id',
    'first_name',
    'last_name',
    'email',
    'email_verified_at',
    'password',
    'owner',
    'photo',
])]
#[Hidden(['password', 'remember_token'])]
final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Concerns\Filterable, HasFactory, Notifiable, Searchable, SoftDeletes;

    /**
     * Retrieve the model for a bound value.
     *
     * @param  string|null  $field
     */
    public function resolveRouteBinding(mixed $value, mixed $field = null): static
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function isDemoUser(): bool
    {
        return $this->email === 'johndoe@example.com';
    }

    public function isProtectedDemoUser(): bool
    {
        return app()->isProduction() && $this->isDemoUser();
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'sort_id' => $this->id,
            'account_id' => $this->account_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'owner' => (bool) $this->owner,
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
