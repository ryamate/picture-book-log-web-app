<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * タグのAPIリソース。
 *
 * JSON構造: {id, name}
 */
class TagResource extends JsonResource
{
    /**
     * リソースを配列に変換する。
     *
     * @param  Request  $request  リクエスト
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
