<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

final class UserOrganizationCollection extends ResourceCollection
{
    public function toArray(Request $request): Collection
    {
        return $this->collection->map(fn (Organization $organization): array => [
            'id' => $organization->id,
            'name' => $organization->name,
        ]);
    }
}
