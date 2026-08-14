<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecideReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Handled in controller via Gate
    }

    public function rules(): array
    {
        return [
            'action'        => ['required', 'string', 'in:confirm,reject'],
            'reject_reason' => ['required_if:action,reject', 'string', 'nullable', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reject_reason.required_if' => 'Alasan penolakan wajib diisi jika Anda menolak rujukan.',
        ];
    }
}
