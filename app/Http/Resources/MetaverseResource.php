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
            'is_collaborator' => $this->is_collaborator,
        ];
    }
}
