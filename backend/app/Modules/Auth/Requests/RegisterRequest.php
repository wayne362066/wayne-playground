<?php

namespace App\Modules\Auth\Requests;

use App\Core\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends ApiFormRequest
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
            'username' => [
                'required',
                'string',
                'min:3',
                'max:32',
                'regex:/^[a-z0-9_]+$/',
                'unique:users,username',
            ],
            'password' => [
                'required',
                'string',
                Password::min(10),
                'max:72',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => '帳號只能使用小寫英文字母、數字與底線。',
            'username.unique' => '這個帳號已被使用。',
            'password.confirmed' => '兩次輸入的密碼不一致。',
        ];
    }
}
