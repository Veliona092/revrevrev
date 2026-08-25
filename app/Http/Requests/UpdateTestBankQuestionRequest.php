<?php

namespace App\Http\Requests;

class UpdateTestBankQuestionRequest extends StoreTestBankQuestionRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['teacher', 'admin', 'superadmin'], true);
    }
}
