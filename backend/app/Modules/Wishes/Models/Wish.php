<?php

namespace App\Modules\Wishes\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wish extends Model
{
    use HasUlids, SoftDeletes;

    protected $attributes = [
        'category' => 'feature',
        'status' => 'submitted',
        'moderation_status' => 'approved',
        'visibility' => 'public',
        'author_type' => 'guest',
    ];

    protected $fillable = [
        'title',
        'description',
        'category',
        'status',
        'moderation_status',
        'visibility',
        'author_id',
        'author_type',
        'author_name',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(WishEvent::class)->latest();
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('moderation_status', 'approved')
            ->where('visibility', 'public');
    }

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }
}
