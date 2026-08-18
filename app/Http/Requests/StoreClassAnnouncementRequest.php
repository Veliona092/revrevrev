<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClassAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && in_array($user->role, ['teacher', 'admin', 'superadmin'], true);
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:1000'],
            'is_pinned' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'The announcement message is required.',
            'message.max' => 'The announcement message may not exceed 1000 characters.',
        ];
    }
}
