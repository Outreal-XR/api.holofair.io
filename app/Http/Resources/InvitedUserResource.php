<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitedUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->user ? new UserResource($this->user) : $this->email,
            'inviter' => new UserResource($this->inviter),
            "metaverse" => MetaverseResource::make($this->whenLoaded('metaverse')),
            'status' => $this->status,
            'role' => $this->role,
            'token' => $this->token,
            'token_expiry' => $this->token_expiry,
            'is_external' => $this->user ? false : true,
        ];
    }
}
