<?php

namespace App\Http\Requests;

use App\Enums\CounselingStatus;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CreateCounselingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->role == UserRole::COUNSELOR->value;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => [
                'required_without:psychologist_id',
                'nullable',
                'date_format:Y-m-d',
            ],
            /**
             * @var string
             *
             * @example 10:10
             */
            'time' => [
                'required_without:psychologist_id',
                'nullable',
                Rule::date()->format('H:i'),
            ],
            'student_id' => 'required|integer|exists:users,id',
            'counselor_id' => 'nullable|integer|exists:users,id',
            'sharing_id' => [
                'nullable',
                'integer',
                Rule::exists('sharings', 'id')
                    ->where(fn($query) => $query->where('user_id', $this->student_id)),
            ],
            'room' => 'required_without:counselor_id|nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
            'reason' => 'required_with:psychologist_id|string|max:255',
            'psychologist_id' => 'nullable|integer|exists:users,id',
        ];
    }   

    protected function passedValidation(){
        $this->merge(['scheduled_at'=>$this->date.' '.$this->time, 'status'=>CounselingStatus::MENUNGGU->value]);
        
        if ($this->psychologist_id) {
            $this->merge(['type' => 'external']);
        } else {
            $this->merge(['type' => 'internal','counselor_id'=>Auth::user()->id]);
        }
    }
}
