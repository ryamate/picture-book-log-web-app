<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 絵本一覧取得リクエストのバリデーション。
 */
class IndexBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:unread,reading,read'],
            'sort' => ['nullable', 'string', 'in:created_at,title,rating'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function status(): ?string
    {
        return $this->validated('status');
    }

    public function sort(): string
    {
        return $this->validated('sort') ?? 'created_at';
    }

    public function order(): string
    {
        return $this->validated('order') ?? 'desc';
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 20);
    }
}
