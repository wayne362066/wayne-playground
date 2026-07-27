<?php

namespace App\Modules\Wishes\Resources;

use App\Core\Access\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'status' => $this->status,
            'moderation_status' => $this->moderation_status,
            'visibility' => $this->visibility,
            'author' => [
                'type' => $this->author_type,
                'name' => $this->author_type === 'anonymous' ? null : $this->author_name,
            ],
            'is_deleted' => $this->trashed(),
            'events' => $this->when(
                app(AuthorizationService::class)->allows(
                    $request->user(),
                    'wishes.history.view',
                ),
                WishEventResource::collection($this->whenLoaded('events')),
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
