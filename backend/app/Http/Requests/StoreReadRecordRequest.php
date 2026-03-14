<?php

namespace App\Http\Requests;

use App\Models\Child;
use App\Models\PictureBook;
use Illuminate\Foundation\Http\FormRequest;

class StoreReadRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'picture_book_id' => ['required', 'integer', 'exists:picture_books,id'],
            'read_date' => ['required', 'date', 'before_or_equal:today'],
            'memo' => ['nullable', 'string', 'max:5000'],
            'children' => ['required', 'array', 'min:1'],
            'children.*.child_id' => ['required', 'integer', 'exists:children,id'],
            'children.*.reaction' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    public function withValidator($validator): void
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

            // 絵本が家族に属しているか
            if ($this->picture_book_id) {
                $bookExists = PictureBook::where('id', $this->picture_book_id)
                    ->where('family_id', $family->id)
                    ->exists();
                if (! $bookExists) {
                    $validator->errors()->add('picture_book_id', 'Invalid picture book specified.');
                }
            }
        });
    }
}
