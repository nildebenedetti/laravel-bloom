<?php

namespace App\Http\Requests\Api;

use App\Enums\RecordVisibility;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecordRequest extends FormRequest
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
            // sometimes so the PATCH method knows
            'title'        => ['sometimes', 'required', 'string', 'max:200'], 
            'description'  => ['nullable', 'string'],
            'date'         => ['sometimes', 'required', 'date_format:Y-m-d'], 
            'image'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], 
            'image_alt'    => ['nullable', 'string', 'max:255'],
            'visibility'   => ['sometimes', 'required', Rule::enum(RecordVisibility::class)], 
            'category_id'  => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'tier_id'      => ['sometimes', 'required', 'integer', 'exists:tiers,id'],
            'emotions'     => ['nullable', 'array'], 
            'emotions.*'   => ['required', 'integer', 'distinct', 'exists:emotions,id'],
        ];
    }
}