<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                // Ignoring the current email in uniqueness check
                Rule::unique('users')->ignore($this->route('user')),
            ],
            'role' => 'required|in:admin,user', // accet one of the 2 values
            'password' => 'nullable|string|min:8', // Opzionale in modifica
            'bio' => 'nullable|string'
        ];
    }
}
