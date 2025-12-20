<?php

namespace App\Services;

class ProductService
{
    public function createProduct(array $data): string
    {
        if ($data['image']) {
            $image = $data['image'];
            $fileName = $data['title'] . rand() . '.' . $image->getClientOriginalExtension();
            $data['image'] = $image->storeAs(
                'uploads/products',
                $fileName
            );
        }

        // Default values
        $data['type'] = 'product';
        $data['is_active'] = $data['is_active'] ?? false;
        $data['is_daily']  = $data['is_daily'] ?? false;


        Product::create($data);
        return 'Product created successfully.';
    }
}
