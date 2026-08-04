<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasUlids;

    protected $fillable = [
        'key',
        'name',
        'description',
        'icon',
        'route',
        'enabled',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
