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
            "metaverse" => $this->whenLoaded('metaverse'),
            'is_accepted' => $this->is_accepted,
            'can_edit' => $this->can_edit,
            'can_view' => $this->can_view,
        ];
    }
}
