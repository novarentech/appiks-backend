<?php

namespace App\Http\Requests;

use App\Enums\CounselingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CloseReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $status = $this->report?->status instanceof CounselingStatus ? $this->report->status->value : $this->report?->status;
        return Gate::allows('update', $this->report) && in_array($status, ['menunggu', 'dijadwalkan', 'dijadwal_ulang', 'disetujui']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'result' => 'required|string',
        ];
    }
}
