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
            /**
             * confirm   — terima jadwal yang diajukan siswa
             * reschedule — geser ke slot lain (auto-setuju, siswa diinformasikan)
             * reject    — tolak rujukannya
             */
            'action'            => ['required', 'string', 'in:confirm,reschedule,reject'],
            'reschedule_reason' => ['required_if:action,reschedule,reject', 'string', 'nullable', 'max:1000'],
            'slot_id'           => ['required_if:action,reschedule', 'exists:psychologist_slots,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'reschedule_reason.required_if' => 'Alasan wajib diisi jika Anda menggeser jadwal atau menolak rujukan.',
            'slot_id.required_if' => 'Slot pengganti wajib dipilih jika Anda menggeser jadwal.',
            'slot_id.exists' => 'Slot tidak ditemukan.',
        ];
    }
}
