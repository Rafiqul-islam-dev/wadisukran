<?php

namespace App\Services;

use App\Models\Category;

class CategoryService
{
    function activeCategories()
    {
        return Category::where('status', 1)->get();
    }
    function createCategory(array $data): string {
        Category::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'draw_type' => $data['draw_type']
        ]);
        return 'Category created successfully';
    }
}
