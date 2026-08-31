<?php

namespace App\Http\Requests;

final class SimulateSelectedPowerLotteryRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'zone_one' => ['required', 'array', 'size:6'],
            'zone_one.*' => ['required', 'integer', 'distinct', 'between:1,38'],
            'zone_two' => ['required', 'integer', 'between:1,8'],
            'period_count' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'zone_one.required' => '請選擇第一區號碼。',
            'zone_one.array' => '第一區號碼格式不正確。',
            'zone_one.size' => '第一區必須選擇 6 個號碼。',
            'zone_one.*.required' => '第一區號碼不可留空。',
            'zone_one.*.integer' => '第一區號碼必須是整數。',
            'zone_one.*.distinct' => '第一區號碼不可重複。',
            'zone_one.*.between' => '第一區號碼必須介於 1 到 38。',
            'zone_two.required' => '請選擇第二區號碼。',
            'zone_two.integer' => '第二區號碼必須是整數。',
            'zone_two.between' => '第二區號碼必須介於 1 到 8。',
            'period_count.required' => '請輸入模擬期數。',
            'period_count.integer' => '模擬期數必須是整數。',
            'period_count.min' => '至少模擬 1 期。',
            'period_count.max' => '最多模擬 1,000,000 期。',
        ];
    }
}
