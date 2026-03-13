<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 絵本の読書情報更新リクエストのバリデーション。
 *
 * 必須: read_status (unread|reading|read)。任意: rating (1-5), review。
 */
class UpdateBookRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるかを判定する。
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'read_status' => ['required', 'string', 'in:unread,reading,read'],
            'review' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
