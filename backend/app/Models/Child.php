<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Child extends Model
{
    protected $fillable = ['family_id', 'name', 'birthday'];

    protected $casts = [
        'birthday' => 'date',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function readRecords(): BelongsToMany
    {
        return $this->belongsToMany(ReadRecord::class, 'child_read_record')
            ->withPivot('reaction');
    }
}
