<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBioRequest extends FormRequest
{
    /**
     * Determine if this request is authorized to make this request.
     *
     * The bio always lands on $request->user()->profile(): no user id travels
     * in the URL or the body, so there is nobody else's bio to authorize
     * against.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize an empty string to null before validating, so clients that
     * clear a textarea send "" and clients that clear a field send null —
     * both end up as "no bio".
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('bio') && $this->input('bio') === '') {
            $this->merge(['bio' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * `present` (not `required`) is deliberate: the key must be in the body —
     * an empty body is a client bug, not a no-op — but unlike `required` it
     * accepts null, which is how a client clears the bio.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bio' => ['present', 'nullable', 'string', 'max:2000'],
        ];
    }
}
