<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

final class ContactCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): Collection
    {
        return $this->collection->map(fn (ContactResource $contact): array => [
            'id' => $contact->id,
            'name' => $contact->name,
            'phone' => $contact->phone,
            'city' => $contact->city,
            'deleted_at' => $contact->deleted_at,
            'organization' => $contact->organization
                ? ['id' => $contact->organization->id, 'name' => $contact->organization->name]
                : null,
        ]);
    }
}
