<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TrashedFilter;
use Illuminate\Http\Request;

final readonly class ResourceFilters
{
    public function __construct(
        public ?string $search = null,
        public ?TrashedFilter $trashed = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $search = is_string($request->input('search')) ? $request->string('search')->trim()->toString() : '';
        $search = $search === '' ? null : $search;
        $trashed = is_string($request->input('trashed')) ? $request->enum('trashed', TrashedFilter::class) : null;

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
