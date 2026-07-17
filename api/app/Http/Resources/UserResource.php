<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->whenLoaded('role', fn () => [
                'name' => $this->role->name,
                'label' => $this->role->label,
                'permissions' => $this->role->permissions->pluck('name'),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
