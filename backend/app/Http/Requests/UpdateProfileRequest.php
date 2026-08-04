<?php

namespace App\Http\Requests;

class UpdateProfileRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $nickname = trim((string) $this->input('nickname'));

        $this->merge([
            'nickname' => $nickname === '' ? null : $nickname,
        ]);
    }

    public function rules(): array
    {
        return [
            'nickname' => ['nullable', 'string', 'max:40'],
        ];
    }
}
