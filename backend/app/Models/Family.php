<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    protected $fillable = ['name'];

    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Child::class);
    }

    public function pictureBooks(): HasMany
    {
        return $this->hasMany(PictureBook::class);
    }

    /**
     * この家族の読み聞かせ記録を取得する。
     *
     * @return HasMany
     */
    public function readRecords(): HasMany
    {
        return $this->hasMany(ReadRecord::class);
    }
}
