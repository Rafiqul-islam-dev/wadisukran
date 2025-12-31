<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgentRequest extends FormRequest
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
            'user_type' => 'required|in:agent',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'address' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048',
            'role' => 'required|string|exists:roles,name',
            'join_date' => 'required|date',
            'commission' => 'required|numeric|min:0|max:100',
            'trn' => 'required|unique:agents,trn'
        ];
    }

    public function messages(): array
    {
        return [
            'user_type.required' => 'User type is required.',
            'user_type.in' => 'Invalid user type selected.',

            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.unique' => 'This email is already registered.',

            'phone.unique' => 'This phone number is already in use.',

            'photo.image' => 'The photo must be an image.',
            'photo.mimes' => 'Photo must be a jpeg, png, jpg, or gif.',
            'photo.max' => 'Photo size must not exceed 10MB.',

            'role.required' => 'Please select a role.',
            'role.exists' => 'Agent role does not exist. Please create Agent role first',

            'join_date.required' => 'Join date is required.',
            'join_date.date' => 'Join date must be a valid date.',

            'trn.required' => 'TRN is required.',
            'trn.unique' => 'This TRN already exists.',
        ];
    }
}
