<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 読み聞かせ記録更新のバリデーションリクエスト。
 */
class UpdateReadRecordRequest extends FormRequest
{
    /**
     * リクエストの認可判定を行う。
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルールを取得する。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'read_date' => ['required', 'date', 'before_or_equal:today'],
            'memo' => ['nullable', 'string', 'max:5000'],
            'children' => ['required', 'array', 'min:1'],
            'children.*.child_id' => ['required', 'integer', 'exists:children,id'],
            'children.*.reaction' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }
}
