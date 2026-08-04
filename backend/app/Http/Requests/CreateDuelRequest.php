<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class CreateDuelRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ticket_count' => ['required', 'integer', 'min:1', 'max:10000'],
            'mode' => ['required', Rule::in(['single', 'until_profit', 'until_jackpot'])],
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
            'ticket_count.required' => '請輸入每期購買注數。',
            'ticket_count.integer' => '購買注數必須是整數。',
            'ticket_count.min' => '每期至少購買 1 注。',
            'ticket_count.max' => '每期最多模擬 10,000 注。',
            'mode.required' => '請選擇模擬模式。',
            'mode.in' => '模擬模式不正確。',
            'nickname.required' => '訪客必須輸入暱稱。',
            'nickname.max' => '暱稱最多 40 個字元。',
            'nickname.not_regex' => '暱稱包含不允許的控制字元。',
        ];
    }
}
