<?php

namespace App\Services;

use App\Models\Category;

class CategoryService
{
    function createCategory(array $data): string {
        Category::create([
            'name' => $data['name'],
            'description' => $data['description']
        ]);
        return 'Category created successfully';
    }
}
