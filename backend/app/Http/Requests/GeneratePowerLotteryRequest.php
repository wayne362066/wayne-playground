<?php

namespace App\Http\Requests;

final class GeneratePowerLotteryRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'count' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'count.required' => '請輸入模擬組數。',
            'count.integer' => '模擬組數必須是整數。',
            'count.min' => '模擬組數至少為 1。',
            'count.max' => '模擬組數最多為 100。',
        ];
    }
}
