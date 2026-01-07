<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $productId = $this->route('product')->id;

        return [
            'title' => [
                'required',
                'string',
                'max:255',
                'unique:products,title,'.$productId,
            ],
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'draw_type' => 'required|in:once,regular',
            'regular_type' => 'nullable|in:hourly,daily|required_if:draw_type,regular',
            'draw_date' => 'nullable|date',
            'draw_time' => 'nullable|date_format:H:i',
            'pick_number' => 'required|integer|min:1|max:10',
            'type_number' => 'required|integer|min:1|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'prize_type' => 'required|in:bet,number',
            'bet_prizes' => 'nullable|array',
            'number_prizes' => 'nullable|array',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Product title is required.',
            'title.unique' => 'Product title already exists.',
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Invalid category.',
            'price.required' => 'Product price is required.',
            'draw_type.required' => 'Draw type is required.',
            'regular_type.required_if' => 'Regular type is required for regular draws.',
            'draw_date.required' => 'Draw date is required.',
            'draw_time.required' => 'Draw time is required.',
            'pick_number.required' => 'Pick number is required.',
            'type_number.required' => 'Max type number is required.',
            'image.image' => 'File must be an image.',
            'image.mimes' => 'Image must be jpeg, png, jpg, or gif.',
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
