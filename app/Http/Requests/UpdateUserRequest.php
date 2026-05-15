<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi ditangani di Controller via Gate
    }

    public function rules(): array
    {
        /** @var \App\Models\User $user */
        $user = $this->route('user');

        $passwordRules = [
            'nullable', 'string', 'min:8',
            'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/',
        ];

        $baseRules = [
            'username' => "string|unique:users,username,{$user->id}",
            'phone'    => "string|digits_between:10,15|unique:users,phone,{$user->id}",
            'name'     => 'string',
            'password' => $passwordRules,
        ];

        if ($user->role === UserRole::STUDENT->value) {
            return array_merge($baseRules, [
                'identifier'=> "string|digits:10|unique:users,identifier,{$user->id}",
                'room_id'   => 'string|exists:rooms,code',
                'mentor_id' => [
                    'nullable',
                    'string',
                    Rule::exists('users', 'identifier')->where('role', UserRole::TEACHER->value),
                ],
            ]);
        }

        if (in_array($user->role, [
            UserRole::TEACHER->value,
            UserRole::HEADTEACHER->value,
            UserRole::COUNSELOR->value,
        ])) {
            return array_merge($baseRules, [
                'identifier' => "string|digits_between:16,25|unique:users,identifier,{$user->id}",
            ]);
        }

        if ($user->role === UserRole::ADMIN->value) {
            return array_merge($baseRules, [
                'identifier' => "string|digits_between:16,25|unique:users,identifier,{$user->id}",
                'school_id'  => 'integer|exists:schools,id',
            ]);
        }

        return $baseRules;
    }
}
