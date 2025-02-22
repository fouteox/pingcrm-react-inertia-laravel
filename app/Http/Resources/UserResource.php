<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

/**
 * @mixin User
 */
final class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'owner' => $this->owner,
            'deleted_at' => $this->deleted_at,
            'account' => $this->whenLoaded('account'),
            'can_delete' => ! App::environment('production') || ! $this->isDemoUser(),
        ];
    }
}
