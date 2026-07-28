<?php

namespace App\Modules\Lottery\Requests;

use App\Core\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class JoinDuelRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nickname' => [
                Rule::requiredIf(fn (): bool => $this->user() === null),
                'nullable',
                'string',
                'max:40',
                'not_regex:/[\\x00-\\x1F\\x7F]/u',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nickname.required' => '訪客必須輸入暱稱。',
            'nickname.max' => '暱稱最多 40 個字元。',
            'nickname.not_regex' => '暱稱包含不允許的控制字元。',
        ];
    }
}
