<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadStageHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_stage' => $this->from_stage,
            'to_stage' => $this->to_stage,
            'changed_by' => $this->whenLoaded('changedBy', fn () => $this->changedBy->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
