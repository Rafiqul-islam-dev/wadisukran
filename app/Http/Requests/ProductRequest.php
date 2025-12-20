<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255|unique:products,title',
            'price' => 'required|numeric|min:0',
            'draw_date' => 'required|date',
            'draw_time' => 'required|date_format:H:i',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'pick_number' => 'required|integer|min:1',
            'showing_type' => 'required|in:prizes,number',
            'type_number' => 'required|integer|min:1',
            'prizes' => 'required|array',
            'is_active' => 'boolean',
            'is_daily' => 'boolean'
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Product title is required.',
            'price.required' => 'Product price is required.',
            'price.numeric' => 'Price must be a valid number.',
            'price.min' => 'Price cannot be negative.',
            'draw_date.required' => 'Draw date is required.',
            'draw_date.after_or_equal' => 'Draw date cannot be in the past.',
            'draw_time.required' => 'Draw time is required.',
            'draw_time.date_format' => 'Draw time must be in H:i format.',
            'image.required' => 'Product image is required.',
            'image.image' => 'File must be an image.',
            'image.mimes' => 'Image must be jpeg, png, jpg, or gif.',
            'image.max' => 'Image size cannot exceed 10MB.',
            'pick_number.required' => 'Pick number is required.',
            'pick_number.integer' => 'Pick number must be an integer.',
            'pick_number.min' => 'Pick number must be at least 1.',
            'pick_number.max' => 'Pick number cannot exceed 10.',
            'showing_type.required' => 'Showing type is required.',
            'showing_type.in' => 'Showing type must be either prizes or number.',
            'type_number.required' => 'Type number is required.',
            'type_number.integer' => 'Type number must be an integer.',
            'type_number.min' => 'Type number must be at least 1.',
            'prizes.required' => 'At least one prize must be configured.',
            'prizes.array' => 'Prizes must be an array.',
            'prizes.min' => 'At least one prize must be configured.',
        ];
    }

    protected function prepareForValidation()
    {
        // Parse JSON prizes if it's a string
        if ($this->has('prizes') && is_string($this->prizes)) {
            $this->merge([
                'prizes' => json_decode($this->prizes, true) ?: []
            ]);
        }

        // Convert is_active to boolean
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN)
            ]);
        }
    }
}
