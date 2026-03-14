<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * タグを表すEloquentモデル。
 *
 * @property int $id
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Tag extends Model
{
    protected $fillable = ['name'];

    /**
     * このタグが付けられた読み聞かせ記録を取得する。
     */
    public function readRecords(): BelongsToMany
    {
        return $this->belongsToMany(ReadRecord::class, 'read_record_tag');
    }
}
