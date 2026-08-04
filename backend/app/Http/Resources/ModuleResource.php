<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->resource['key'],
            'name' => $this->resource['name'],
            'description' => $this->resource['description'],
            'icon' => $this->resource['icon'],
            'route' => $this->resource['route'],
            'enabled' => (bool) $this->resource['enabled'],
            'status' => $this->resource['status'],
            'sort_order' => (int) $this->resource['sort_order'],
        ];
    }
}
