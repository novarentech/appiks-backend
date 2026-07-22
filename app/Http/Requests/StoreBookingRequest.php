<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ownership guard is inside Controller
    }

    public function rules(): array
    {
        return [
            'counseling_id' => ['required', 'integer', 'exists:counselings,id'],
            'slot_id'       => ['required', 'integer', 'exists:psychologist_slots,id'],
        ];
    }
}
