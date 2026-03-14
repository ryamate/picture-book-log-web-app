<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['name'];

    public function readRecords(): BelongsToMany
    {
        return $this->belongsToMany(ReadRecord::class, 'read_record_tag');
    }
}
