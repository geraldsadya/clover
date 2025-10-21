<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateCoverLetterRequest extends FormRequest
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
            'cv' => [
                'required',
                'file',
                'mimes:pdf',
                'max:10240', // 10MB
                function ($attribute, $value, $fail) {
                    // Magic-byte validation for PDF
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $value->getPathname());
                    if ($mimeType !== 'application/pdf') {
                        $fail('File must be a valid PDF.');
                    }
                }
            ],
            'job_description' => 'required|string|min:50|max:10000'
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'cv.required' => 'Please upload a CV.',
            'cv.file' => 'The uploaded file is not valid.',
            'cv.mimes' => 'The CV must be a PDF file.',
            'cv.max' => 'The CV file must not be larger than 10MB.',
            'job_description.required' => 'Please enter a job description.',
            'job_description.min' => 'Job description must be at least 50 characters.',
            'job_description.max' => 'Job description must not exceed 10,000 characters.',
        ];
    }
}