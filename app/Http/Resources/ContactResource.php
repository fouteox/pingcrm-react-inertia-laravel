<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Contact
 */
final class ContactResource extends JsonResource
{
    /**
     * @return array{id: int, first_name: string, last_name: string, email: string|null, phone: string|null, address: string|null, city: string|null, region: string|null, country: string|null, postal_code: string|null, deleted_at: \Carbon\CarbonInterface|null, organization_id: int|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'region' => $this->region,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'deleted_at' => $this->deleted_at,
            'organization_id' => $this->organization_id,
        ];
    }
}
