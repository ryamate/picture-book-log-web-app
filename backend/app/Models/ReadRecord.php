<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function pictureBook(): BelongsTo
    {
        return $this->belongsTo(PictureBook::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Child::class, 'child_read_record')
            ->withPivot('reaction');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'read_record_tag');
    }
}
