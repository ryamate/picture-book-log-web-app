<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PictureBook extends Model
{
    protected $fillable = [
        'family_id',
        'registered_by',
        'google_books_id',
        'isbn',
        'title',
        'authors',
        'thumbnail_url',
        'rating',
        'read_status',
        'review',
    ];

    protected $casts = [
        'authors' => 'array',
        'rating' => 'integer',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function registeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
