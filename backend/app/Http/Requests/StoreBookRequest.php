<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request to add a new picture book.
 *
 * Required: title, authors. Optional: google_books_id, isbn, thumbnail_url.
 */
class StoreBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'google_books_id' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:13'],
            'title' => ['required', 'string', 'max:500'],
            'authors' => ['required', 'array'],
            'authors.*' => ['string', 'max:255'],
            'thumbnail_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
