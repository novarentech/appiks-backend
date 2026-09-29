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
            'action'        => ['required', 'string', 'in:confirm,reschedule'],
            'reschedule_reason' => ['required_if:action,reschedule', 'string', 'nullable', 'max:1000'],
            'slot_id' => ['required_if:action,reschedule', 'exists:psychologist_slots,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'reschedule_reason.required_if' => 'Alasan penjadwalan ulang wajib diisi jika Anda menolak rujukan.',
            'slot_id.required_if' => 'Slot wajib diisi jika Anda menolak rujukan.',
            'slot_id.exists' => 'Slot tidak ditemukan.',
        ];
    }
}
