<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 絵本の新規登録リクエストのバリデーション。
 *
 * 必須: title, authors。任意: google_books_id, isbn, thumbnail_url。
 */
class StoreBookRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるかを判定する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'google_books_id' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:13'],
            'title' => ['required', 'string', 'max:500'],
            'authors' => ['required', 'array'],
            'authors.*' => ['string', 'max:255'],
            'thumbnail_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
