<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClinicalSummaryFeedbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorized in controller via psychologist booking ownership check
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
             * Catatan klinis dari psikolog penanggung jawab.
             * @example Pasien menunjukkan kecemasan situasional yang dipicu oleh tekanan akademik.
             */
            'clinical_notes' => ['nullable', 'string', 'max:5000'],

            /**
             * Penilaian evaluasi terhadap ringkasan AI.
             * @example good
             */
            'rating' => ['nullable', 'string', 'in:good,bad'],

            /**
             * Masukan perbaikan untuk kualitas ringkasan AI.
             * @example Ringkasan sudah tepat, namun perlu detail pemicu spesifik.
             */
            'improvement_feedback' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
