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
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'draw_type' => 'required|in:once,regular',
            'regular_type' => 'nullable|in:hourly,daily|required_if:draw_type,regular',
            'draw_date' => 'nullable|date',
            'draw_time' => 'nullable|date_format:H:i',
            'pick_number' => 'required|integer|min:1|max:10',
            'type_number' => 'required|integer|min:1|max:100',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
            'prize_type' => 'required|in:bet,number',
            'bet_prizes' => 'nullable|array',
            'number_prizes' => 'nullable|array'
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Product title is required.',
            'title.unique' => 'Product title is already exists',
            'title.string' => 'Product title must be text.',
            'title.max' => 'Product title cannot be greater than 255 character.',
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Invalid category.',
            'price.required' => 'Product price is required.',
            'price.numeric' => 'Price must be a valid number.',
            'price.min' => 'Price cannot be negative.',
            'draw_type.required' => 'Draw Type is required.',
            'draw_type.in' => 'Draw Type must be in once or regular.',
            'regular_type.in' => 'Regular type must be in hourly or daily',
            'required_if.required_if' => 'Regular type is required.',
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
            'type_number.required' => 'Max Type number is required.',
            'type_number.integer' => 'Max Type number must be an integer.',
            'type_number.min' => 'Max Type number must be at least 1.',
            'type_number.max' => 'Max Type number must be greater than 100.',
            'bet_prizes.required' => 'prize must be configured.',
            'bet_prizes.array' => 'Prizes must be an array.',
            'number_prizes.required' => 'prize must be configured.',
            'number_prizes.array' => 'Prizes must be an array.',
        ];
    }
    public function withValidator($validator)
    {
        $validator->sometimes(['draw_date', 'draw_time'], 'required', function ($input) {
            return $input->draw_type === 'once' || $input->regular_type === 'daily';
        });

        $validator->sometimes('bet_prizes', 'required|array', function ($input) {
            return $input->prize_type === 'bet';
        });

        $validator->sometimes('number_prizes', 'required|array', function ($input) {
            return $input->prize_type === 'number';
        });
    }

}
