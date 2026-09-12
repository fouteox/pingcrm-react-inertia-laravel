<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

final class ContactCollection extends ResourceCollection
{
    /** @var Collection<int, ContactResource> */
    public $collection;

    /**
     * @return Collection<int, array{id: int, name: string, phone: string|null, city: string|null, deleted_at: \Carbon\Carbon|null, organization: array{id: int, name: string}|null}>
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
