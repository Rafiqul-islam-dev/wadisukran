<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
   public function rules(): array
    {
        return [
            'phone' => 'required|string',
            'name'  => 'nullable|string',
            'email' => 'nullable|string|email'
        ];
    }

    public function authorize(): bool
    {
        return true; // allow everyone
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException(
            $validator,
            response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
