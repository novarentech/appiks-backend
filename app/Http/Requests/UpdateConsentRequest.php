<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConsentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Controlled via controller policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'is_granted' => ['required', 'boolean'],
            'scopes' => [
                Rule::requiredIf(fn () => $this->boolean('is_granted')),
                'nullable',
                'array',
                Rule::when($this->boolean('is_granted'), ['min:1']),
            ],
            'scopes.*' => [
                'string',
                Rule::in([
                    'mood_history',
                    'sharing_history',
                    'assesment_logs',
                ]),
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scopes.required' => 'At least one scope must be selected when granting consent.',
            'scopes.min' => 'At least one scope must be selected when granting consent.',
        ];
    }
}
