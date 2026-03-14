<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 家族が登録した絵本を表すEloquentモデル。
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
 * @property Carbon $created_at
 * @property Carbon $updated_at
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
     * この絵本を所有する家族を取得する。
     *
     * @return BelongsTo
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * この絵本を登録したユーザーを取得する。
     *
     * @return BelongsTo
     */
    public function registeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /**
     * この絵本の読み聞かせ記録を取得する。
     *
     * @return HasMany
     */
    public function readRecords(): HasMany
    {
        return $this->hasMany(ReadRecord::class);
    }
}
