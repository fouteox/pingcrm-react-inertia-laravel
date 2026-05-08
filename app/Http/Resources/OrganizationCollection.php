<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

final class OrganizationCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
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
