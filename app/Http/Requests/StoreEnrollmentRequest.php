<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'min:2', 'max:120'],
            'phone'                 => ['required', 'string', 'min:7', 'max:40'],
            'email'                 => ['required', 'email:rfc', 'max:180'],
            'electrical_experience' => ['nullable', 'string', 'max:120'],
            'plc_experience'        => ['nullable', 'string', 'max:120'],
            'program_id'            => ['required', 'exists:programs,id'],
            'session_slot'          => ['required', Rule::in(config('ptt.session_slots'))],
            'preferred_date'        => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'preferred_date.after_or_equal' => 'Please choose today or a future date.',
            'session_slot.in'               => 'Please choose one of the listed sessions.',
        ];
    }
}
