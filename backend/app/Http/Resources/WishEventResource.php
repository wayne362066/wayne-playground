<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->event_type,
            'from' => $this->from_value,
            'to' => $this->to_value,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
