<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator; 
use Illuminate\Http\Exceptions\HttpResponseException;

class ChatRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'chatInput' => 'required|string',
            'sessionId' => 'required|string',
            'mainCategory'  => [
                'nullable',
                'string',
                'regex:/^(general|geral|\d+(\.\d+)*\s*-\s*.+)$/i'
            ],
            
            'childCategory' => [
                'required_with:mainCategory',
                'nullable',
                'string',
                'regex:/^(general|geral|\d+(\.\d+)*\s*-\s*.+)$/i'
            ],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'errors' => $validator->errors()
        ], 422));
    }
}
