<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetPsychologistReferralsRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            /**
             * Filter pencarian berdasarkan nama siswa.
             * @example Budi
             */
            'search' => ['nullable', 'string'],

            /**
             * Filter status rujukan.
             * @example menunggu konfirmasi
             */
            'status' => ['nullable', 'string', 'in:menunggu konfirmasi,terkonfirmasi,selesai,ditolak,kadaluarsa'],

            /**
             * Filter prioritas rujukan.
             * @example kritis
             */
            'priority' => ['nullable', 'string', 'in:kritis,prioritas'],

            /**
             * Filter batas waktu rujukan.
             * @example aktif
             */
            'batas_waktu' => ['nullable', 'string', 'in:aktif,kadaluarsa'],

            /**
             * Jumlah data per halaman.
             * @example 10
             */
            'per_page' => ['nullable', 'integer', 'min:1'],

            /**
             * Halaman yang ingin ditampilkan.
             * @example 1
             */
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
