<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePsychologistRequest extends FormRequest
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
        $user = $this->route('psychologist');
        $userId = $user instanceof \App\Models\User ? $user->id : $user;
        $profileId = $user instanceof \App\Models\User ? $user->psychologistProfile?->id : null;

        return [
            'name'             => 'required|string|max:255',
            'email'            => "required|email|max:255|unique:users,username,{$userId}",
            'str_number'       => "required|string|max:255|unique:users,identifier,{$userId}|unique:psychologist_profiles,str_number,{$profileId}",
            'specialization'   => 'nullable|string|max:255',
            'institution_name' => 'required|string|max:255',
            'phone_number'     => "nullable|string|digits_between:10,15|unique:users,phone,{$userId}",
            'password'         => 'nullable|string|min:8',
            'is_active'        => 'nullable|boolean',
        ];
    }
}
