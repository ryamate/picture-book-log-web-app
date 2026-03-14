<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * 読み聞かせ記録を表すEloquentモデル。
 *
 * @property int $id
 * @property int $picture_book_id
 * @property int $family_id
 * @property int $recorded_by
 * @property Carbon $read_date
 * @property string|null $memo
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ReadRecord extends Model
{
    protected $fillable = [
        'picture_book_id',
        'family_id',
        'recorded_by',
        'read_date',
        'memo',
    ];

    protected $casts = [
        'read_date' => 'date',
    ];

    /**
     * この記録の絵本を取得する。
     *
     * @return BelongsTo
     */
    public function pictureBook(): BelongsTo
    {
        return $this->belongsTo(PictureBook::class);
    }

    /**
     * この記録が属する家族を取得する。
     *
     * @return BelongsTo
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * この記録を登録したユーザーを取得する。
     *
     * @return BelongsTo
     */
    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * この記録に関連する子どもを取得する。
     *
     * @return BelongsToMany
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Child::class, 'child_read_record')
            ->withPivot('reaction');
    }

    /**
     * この記録に付けられたタグを取得する。
     *
     * @return BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'read_record_tag');
    }
}
