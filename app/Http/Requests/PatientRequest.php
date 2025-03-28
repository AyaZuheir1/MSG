<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PatientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required | string',
            'last_name' => 'required | string',
            'email' => 'required | email',
            'password' => 'required |string',
            'password_confirmation' => 'required |string',
            'SSN' => 'required | string',
            'age' => 'required | integer',
            'gender' => 'required |in:male,female',
            'phone_number' => 'required',
            'address' => 'required | string',
        ];
    }
}
