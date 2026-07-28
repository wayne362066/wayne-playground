<?php

namespace App\Modules\Auth\Resources;

use App\Core\Access\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'nickname' => $this->nickname,
            'display_name' => $this->nickname ?: "@{$this->username}",
            'roles' => $this->roles()
                ->orderBy('key')
                ->pluck('key')
                ->all(),
            'permissions' => app(AuthorizationService::class)
                ->permissionsFor($this->resource)
                ->all(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
