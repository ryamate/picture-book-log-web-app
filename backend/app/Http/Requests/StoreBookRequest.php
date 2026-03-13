<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
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
