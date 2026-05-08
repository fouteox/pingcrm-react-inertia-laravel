<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TrashedFilter;
use Illuminate\Http\Request;

final readonly class OrganizationsFilters
{
    public function __construct(
        public ?string $search = null,
        public ?TrashedFilter $trashed = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $search = $request->string('search')->trim()->toString() ?: null;
        $trashed = TrashedFilter::tryFrom((string) $request->input('trashed'));

        return new self($search, $trashed);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'trashed' => $this->trashed?->value,
        ], fn ($value) => $value !== null);
    }
}
