<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectSalesPointRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // enforced by the `can:` route middleware
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|min:5|max:1000',
        ];
    }
}
