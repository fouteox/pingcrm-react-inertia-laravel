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
        $filters = ResourceFilters::fromRequest($request);
        $role = is_string($request->input('role')) ? $request->enum('role', Role::class) : null;

        return new self($filters->search, $role, $filters->trashed);
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
