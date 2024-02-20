<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MetaverseResource extends JsonResource
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
            'name' => $this->name,
            'thumbnail' => $this->thumbnail ? asset($this->thumbnail) : null,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'url' => $this->url,
            'uuid' => $this->uuid,
            'userid' => $this->userid,
            "user" => $this->user,
            "invitedUsers" => $this->whenLoaded('invitedUsers'),
            "settings" => $this->whenLoaded('settings'),
            "languages" => $this->whenLoaded('languages'),
            "template" => $this->whenLoaded('template'),
            'mapped_user_settings' => $this->mapped_user_settings,
            'is_collaborator' => $this->is_collaborator,
            'is_blocked' => $this->is_blocked,
            'is_owner' => $this->is_owner,
            "links" => $this->links,
        ];
    }
}
