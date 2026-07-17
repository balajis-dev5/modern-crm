<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'source' => $this->source,
            'stage' => $this->stage,
            'deal_value' => $this->deal_value,
            'owner' => UserResource::make($this->whenLoaded('owner')),
            'stage_history' => LeadStageHistoryResource::collection($this->whenLoaded('stageHistories')),
            'customer_id' => $this->whenLoaded('customer', fn () => $this->customer?->id),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
