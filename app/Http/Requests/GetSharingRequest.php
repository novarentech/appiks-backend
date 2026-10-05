<?php

namespace App\Http\Requests;

use App\Enums\Priority;
use App\Enums\ReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetSharingRequest extends FormRequest
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
             * Filter berdasarkan kelas siswa (room.level + room.name).
             * @example XII IPA 4
             */
            'room' => ['nullable', 'string'],

            /**
             * Filter status curhatan siswa.
             * @example Belum Ditinjau
             */
            'status' => ['nullable', Rule::enum(ReportStatus::class)],

            /**
             * Filter prioritas curhatan siswa.
             * @example tinggi
             */
            'priority' => ['nullable', Rule::enum(Priority::class)],
        ];
    }
}
