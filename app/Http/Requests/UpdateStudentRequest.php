<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // route model binding param is {student}
        $studentId = $this->route('student');

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name'  => ['sometimes', 'required', 'string', 'max:100'],
            'email'      => [
                'sometimes',
                'required',
                'email',
                Rule::unique('students', 'email')->ignore($studentId),
            ],
            'age'        => ['sometimes', 'required', 'integer', 'min:15'],
            'course'     => ['sometimes', 'required', 'string', 'max:100'],
            'year_level' => ['sometimes', 'required', 'integer', 'min:1', 'max:4'],
            'status'     => ['sometimes', 'required', 'in:active,inactive'],
        ];
    }
}