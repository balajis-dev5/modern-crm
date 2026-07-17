<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lead_id' => $this->lead_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'notes' => $this->notes,
            'owner' => UserResource::make($this->whenLoaded('owner')),
            'follow_ups' => FollowUpResource::collection($this->whenLoaded('followUps')),
            'open_follow_ups_count' => $this->whenCounted('open_follow_ups_count'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
