<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * タグ検索リクエストのバリデーション。
 */
class SearchTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:1'],
        ];
    }

    public function keyword(): string
    {
        return $this->validated('q');
    }
}
