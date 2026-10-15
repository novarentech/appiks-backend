<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetReportRequest extends FormRequest
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
            /**
             * Filter berdasarkan kelas siswa (gabungan tingkat dan nama, contoh: "X IPA" atau "XI IPS 1").
             *
             * @example X IPA 1
             */
            'room' => ['nullable', 'string', 'max:50'],

            /**
             * Filter status laporan konseling.
             *
             * @example Belum Ditinjau
             */
            'status' => ['nullable', 'string', 'max:50'],

            /**
             * Filter prioritas laporan konseling (tinggi atau rendah).
             *
             * @example tinggi
             */
            'priority' => ['nullable', 'string', Rule::in(['tinggi', 'rendah','sedang'])],

            /**
             * Kata kunci pencarian nama siswa, NISN/NIP siswa, atau topik laporan.
             *
             * @example Bullying
             */
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}
