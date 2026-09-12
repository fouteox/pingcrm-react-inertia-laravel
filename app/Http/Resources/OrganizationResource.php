<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Organization
 */
final class OrganizationResource extends JsonResource
{
    /**
     * @return array{id: int, name: string, email: string|null, phone: string|null, address: string|null, city: string|null, region: string|null, country: string|null, postal_code: string|null, deleted_at: \Carbon\CarbonInterface|null, contacts: \Illuminate\Http\Resources\Json\AnonymousResourceCollection}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'region' => $this->region,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'deleted_at' => $this->deleted_at,
            'contacts' => OrganizationContactResource::collection($this->whenLoaded('contacts')),
        ];
    }
}
