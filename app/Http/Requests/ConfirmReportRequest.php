<?php

namespace App\Http\Requests;

use App\Enums\CounselingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ConfirmReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $status = $this->report?->status instanceof CounselingStatus ? $this->report->status->value : $this->report?->status;
        return Gate::allows('update', $this->report) && in_array($status, ['menunggu', 'pending']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => 'required|date_format:Y-m-d',
            /**
             * @var string
             *
             * @example 10:10
             */
            'time' => Rule::date()->format('H:i'),
            'notes' => 'required|string',
            'room' => 'required|string',
        ];
    }

    protected function passedValidation()
    {
        $this->merge([
            'status' => CounselingStatus::DIJADWALKAN->value,
            'scheduled_at' => $this->date . ' ' . $this->time,
        ]);
    }
}
