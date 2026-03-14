<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SendInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->email === $this->user()->email) {
                $validator->errors()->add('email', '自分自身を招待することはできません。');
            }

            $family = $this->route('family');
            $existingUser = User::where('email', $this->email)->first();
            if ($existingUser && $existingUser->family_id === $family->id) {
                $validator->errors()->add('email', 'このユーザーは既にこの家族のメンバーです。');
            }
        });
    }
}
