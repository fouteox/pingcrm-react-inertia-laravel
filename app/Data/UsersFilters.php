<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\Role;
use App\Enums\TrashedFilter;
use Illuminate\Http\Request;

final readonly class UsersFilters
{
    public function __construct(
        public ?string $search = null,
        public ?Role $role = null,
        public ?TrashedFilter $trashed = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $search = $request->string('search')->trim()->toString() ?: null;
        $role = Role::tryFrom((string) $request->input('role'));
        $trashed = TrashedFilter::tryFrom((string) $request->input('trashed'));

        return new self($search, $role, $trashed);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'role' => $this->role?->value,
            'trashed' => $this->trashed?->value,
        ], fn ($value) => $value !== null);
    }
}
