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
        return $this->collection->map->only(
            'id', 'name', 'phone', 'city', 'deleted_at'
        );
    }
}
