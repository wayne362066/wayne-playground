<?php

namespace App\Modules\Wishes\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WishEvent extends Model
{
    use HasUlids;

    protected $fillable = [
        'event_type',
        'from_value',
        'to_value',
        'metadata',
    ];

    public function wish(): BelongsTo
    {
        return $this->belongsTo(Wish::class);
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
