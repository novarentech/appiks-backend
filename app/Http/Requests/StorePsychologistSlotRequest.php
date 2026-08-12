<?php

namespace App\Http\Requests;

use App\Models\PsychologistSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StorePsychologistSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage', PsychologistSlot::class);
    }

    public function rules(): array
    {
        return [
            'slot_date'       => ['required', 'date', 'after_or_equal:today'],
            'slot_start_time' => ['required', 'date_format:H:i'],
            'slot_end_time'   => ['required', 'date_format:H:i'],
        ];
    }
}
