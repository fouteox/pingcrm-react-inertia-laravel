<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

final class OrganizationCollection extends ResourceCollection
{
    /** @var Collection<int, OrganizationResource> */
    public $collection;

    /**
     * @return Collection<int, array{id: int, name: string, phone: string|null, city: string|null, deleted_at: \Carbon\Carbon|null}>
     */
    public function toArray(Request $request): Collection
    {
        return $this->collection->map(fn (OrganizationResource $organization): array => [
            'id' => $organization->id,
            'name' => $organization->name,
            'phone' => $organization->phone,
            'city' => $organization->city,
            'deleted_at' => $organization->deleted_at,
        ]);
    }
}
