<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'date'         => ['required', 'date'],
            // we need to handle the sent file instead of the image path
            'image'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // max iS 2048 KB = 2 MB 
            'image_alt'    => ['nullable', 'string', 'max:255'],
            'visibility'   => ['required', 'string', Rule::in(['public', 'private'])],
            'category_id'  => ['required', 'integer', 'exists:categories,id'],
            'tier_id'      => ['required', 'integer', 'exists:tiers,id'],
            'emotions'     => ['nullable', 'array'], 
            'emotions.*'   => ['integer', 'exists:emotions,id'], // elems in emotions array must be integers and exist in column ID of emotions table
        ];
    }
}
