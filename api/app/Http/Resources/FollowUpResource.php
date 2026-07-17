<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowUpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'due_at' => $this->due_at?->toIso8601String(),
            'done_at' => $this->done_at?->toIso8601String(),
            'overdue' => $this->done_at === null && $this->due_at?->isPast(),
            'customer' => $this->whenLoaded('customer', fn () => $this->customer
                ? ['id' => $this->customer->id, 'name' => $this->customer->name]
                : null),
            'lead' => $this->whenLoaded('lead', fn () => $this->lead
                ? ['id' => $this->lead->id, 'name' => $this->lead->name]
                : null),
            'assignee' => UserResource::make($this->whenLoaded('assignee')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
