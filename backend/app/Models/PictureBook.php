<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model representing a picture book registered by a family.
 *
 * @property int $id
 * @property int $family_id
 * @property int $registered_by
 * @property string|null $google_books_id
 * @property string|null $isbn
 * @property string $title
 * @property array $authors
 * @property string|null $thumbnail_url
 * @property int|null $rating
 * @property string $read_status
 * @property string|null $review
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
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

    /**
     * Get the family that owns this picture book.
     *
     * @return BelongsTo
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * Get the user who registered this picture book.
     *
     * @return BelongsTo
     */
    public function registeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
