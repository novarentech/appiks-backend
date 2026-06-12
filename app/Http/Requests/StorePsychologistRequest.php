<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePsychologistRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|max:255|unique:users,username',
            'str_number'       => 'required|string|max:255|unique:psychologist_profiles,str_number|unique:users,identifier',
            'specialization'   => 'nullable|string|max:255',
            'institution_name' => 'required|string|max:255',
            'phone_number'     => 'nullable|string|digits_between:10,15|unique:users,phone',
        ];
    }
}
