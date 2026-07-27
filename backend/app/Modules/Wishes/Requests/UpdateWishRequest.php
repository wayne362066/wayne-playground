<?php

namespace App\Modules\Wishes\Requests;

use App\Core\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateWishRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'category' => ['sometimes', 'required', Rule::in(config('wishes.categories'))],
            'status' => ['sometimes', 'required', Rule::in(config('wishes.statuses'))],
            'moderation_status' => ['sometimes', 'required', Rule::in(config('wishes.moderation_statuses'))],
            'visibility' => ['sometimes', 'required', Rule::in(config('wishes.visibilities'))],
            'author_type' => ['sometimes', 'required', Rule::in(config('wishes.author_types'))],
            'author_name' => ['sometimes', 'nullable', 'string', 'max:80'],
        ];
    }
}
