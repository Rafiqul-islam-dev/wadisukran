<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPrize;

class ProductService
{
    public function createProduct(array $data): string
    {
        $imagePath = null;

        if (!empty($data['image'])) {
            $image = $data['image'];

            $fileName = str()->slug($data['title']) . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

            $imagePath = $image->storeAs(
                'uploads/products',
                $fileName
            );
        }

        $product = Product::create([
            'title'        => $data['title'],
            'category_id'  => $data['category_id'],
            'price'        => $data['price'],
            'draw_type'    => $data['draw_type'],
            'draw_date'    => $data['draw_date'] ?? null,
            'draw_time'    => $data['draw_time'] ?? null,
            'pick_number'  => $data['pick_number'],
            'type_number'  => $data['type_number'],
            'prize_type'   => $data['prize_type'],
            'image'        => $imagePath,
            'is_active'    => $data['is_active'] ?? false
        ]);

        foreach ($data['prizes'] as $prize) {
            if ($prize['prize'] > 0) {
                ProductPrize::create([
                    'product_id' => $product->id,
                    'type' => $prize['type'],
                    'name' => $prize['name'],
                    'chance_number' => $prize['chance_number'] ?? null,
                    'prize' => $prize['prize']
                ]);
            }
        }

        return 'Product created successfully.';
    }

    function statusChange(Product $product): string
    {
        $product->is_active = $product->is_active ? false : true;
        $product->save();
        return 'Product status changed';
    }

    public function updateProduct(Product $product, array $data): string
    {
        if (!empty($data['image'])) {
            if ($product->image && file_exists(public_path($product->image))) {
                unlink(public_path($product->image));
            }

            $image = $data['image'];
            $fileName = str()->slug($data['title']) . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('uploads/products', $fileName);
            $product->image = $imagePath;
        }

        $product->title       = $data['title'];
        $product->category_id = $data['category_id'];
        $product->price       = $data['price'];
        $product->draw_type   = $data['draw_type'];
        $product->draw_date   = $data['draw_date'] ?? null;
        $product->draw_time   = $data['draw_time'] ?? null;
        $product->pick_number = $data['pick_number'];
        $product->type_number = $data['type_number'];
        $product->prize_type  = $data['prize_type'];
        $product->save();

        $product->prizes()->delete();

        foreach ($data['prizes'] as $prize) {
            if ($prize['prize'] > 0) {
                ProductPrize::create([
                    'product_id' => $product->id,
                    'type'       => $prize['type'],
                    'name'       => $prize['name'],
                    'prize'      => $prize['prize']
                ]);
            }
        }

        return 'Product updated successfully.';
    }
    public function productPermanentDelete($id) : string {
        $product = Product::withTrashed()->find($id);
        if ($product) {
            if ($product->image && file_exists(public_path($product->image))) {
                unlink(public_path($product->image));
            }
            $product->prizes()->delete();
            $product->forceDelete();
            
            return 'Product permanently deleted successfully.';
        }
        return 'Product not found.';
    }
}
