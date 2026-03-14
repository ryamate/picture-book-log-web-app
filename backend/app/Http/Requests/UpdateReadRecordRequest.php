<?php

namespace App\Http\Requests;

use App\Models\Child;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

    /**
     * カスタムバリデーションを追加する。
     *
     * 子どもがリクエスト対象の家族に属していることを検証する。
     *
     * @param Validator $validator バリデーター
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $family = $this->route('family');

            // 子どもが家族に属しているか
            $childIds = collect($this->children)->pluck('child_id');
            $validChildCount = Child::where('family_id', $family->id)
                ->whereIn('id', $childIds)
                ->count();
            if ($validChildCount !== $childIds->count()) {
                $validator->errors()->add('children', 'Invalid child specified.');
            }
        });
    }
}
