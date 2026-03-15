<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 読み聞かせ記録一覧取得リクエストのバリデーション。
 */
class IndexReadRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'child_id' => ['nullable', 'integer'],
            'picture_book_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function childId(): ?int
    {
        return $this->validated('child_id') ? (int) $this->validated('child_id') : null;
    }

    public function pictureBookId(): ?int
    {
        return $this->validated('picture_book_id') ? (int) $this->validated('picture_book_id') : null;
    }

    public function dateFrom(): ?string
    {
        return $this->validated('date_from');
    }

    public function dateTo(): ?string
    {
        return $this->validated('date_to');
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 20);
    }

    public function page(): int
    {
        return (int) ($this->validated('page') ?? 1);
    }
}
