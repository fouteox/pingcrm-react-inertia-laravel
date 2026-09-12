<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

final class UserCollection extends ResourceCollection
{
    /** @var Collection<int, UserResource> */
    public $collection;

    /**
     * @return Collection<int, array{id: int, name: string, email: string, owner: bool, deleted_at: \Carbon\Carbon|null}>
     */
    public function toArray(Request $request): Collection
    {
        return $this->collection->map(fn (UserResource $user): array => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'owner' => $user->owner,
            'deleted_at' => $user->deleted_at,
        ]);
    }
}
