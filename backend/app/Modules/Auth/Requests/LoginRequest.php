<?php

namespace App\Modules\Auth\Requests;

use App\Core\Http\Requests\ApiFormRequest;

class LoginRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => mb_strtolower(trim((string) $this->input('username'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string', 'max:72'],
        ];
    }
}
