<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read User $invitedByUser
 */
class FamilyInvitation extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'family_id',
        'invited_by',
        'email',
        'token',
        'accepted_at',
        'expires_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function invitedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
