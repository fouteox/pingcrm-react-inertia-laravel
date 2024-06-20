<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

/**
 * @mixin User
 */
class UserResource extends JsonResource
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
            'photo' => $this->photo ? url()->route('image', ['path' => $this->photo, 'w' => 60, 'h' => 60, 'fit' => 'crop']) : null,
            'deleted_at' => $this->deleted_at,
            'account' => $this->whenLoaded('account'),
            'can_delete' => ! App::environment('demo') || ! $this->isDemoUser(),
        ];
    }
}
