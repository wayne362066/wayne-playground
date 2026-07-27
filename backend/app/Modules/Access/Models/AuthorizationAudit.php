<?php

namespace App\Modules\Access\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorizationAudit extends Model
{
    use HasUlids;

    protected $fillable = [
        'actor_id',
        'subject_type',
        'subject_id',
        'action',
        'before',
        'after',
        'ip_address',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
        ];
    }
}
