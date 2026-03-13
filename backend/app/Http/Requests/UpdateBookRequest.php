<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request to update a picture book's reading information.
 *
 * Required: read_status (unread|reading|read). Optional: rating (1-5), review.
 */
class UpdateBookRequest extends FormRequest
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
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'read_status' => ['required', 'string', 'in:unread,reading,read'],
            'review' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
