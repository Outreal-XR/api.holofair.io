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
            'user' => new UserResource($this->user),
            'inviter' => new UserResource($this->inviter),
            "metaverse" => MetaverseResource::make($this->whenLoaded('metaverse')),
            'status' => $this->status,
            'role' => $this->role,
            'token' => $this->token,
            'token_expiry' => $this->token_expiry,
        ];
    }
}
