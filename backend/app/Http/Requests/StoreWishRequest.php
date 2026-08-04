<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreWishRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', Rule::in(config('wishes.categories'))],
            'author_type' => ['required', Rule::in(config('wishes.author_types'))],
            'author_name' => [
                'nullable',
                'string',
                'max:80',
                Rule::requiredIf(fn (): bool => $this->input('author_type') === 'guest'),
            ],
        ];
    }
}
