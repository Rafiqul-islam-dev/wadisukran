<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Carbon\Carbon;

class ProductController extends Controller
{
    protected ProductService $productService;
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }
    public function apiIndex()
    {
        $products = Product::orderBy('id', 'desc')
            ->get()
            ->sortBy('order_by')
            ->each(function ($product) {
                if (!$this->productService->checkProductAvailability($product)) {
                    $product->is_active = false;
                }
            })
            ->values();

        return ProductResource::collection($products)->additional([
            'phone_require' => company_setting()->customer_phone_require === 1
        ]);
    }


    public function getProduct($id)
    {
        try {
            $product = Product::active()->find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Product retrieved successfully',
                'data' => $this->formatProductForFlutter($product)
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }


    private function formatProductForFlutter(Product $product): array
    {
        $imageUrl = $product->image_url
            ? url($product->image_url)
            : 'https://picsum.photos/60/60?random=' . $product->id;

        return [
            'id' => $product->id,
            'title' => $product->title,
            'price' => (float) $product->price,
            'drawDate' => $product->draw_date->format('Y-m-d'),
            'drawTime' => $product->draw_time->format('H:i:s'),
            'prizes' => $product->prizes,
            'image' => $imageUrl,
            'type' => $product->type,
            'pick_number' => $product->pick_number,
            'showing_type' => $product->showing_type,
            'type_number' => $product->type_number,
            'is_active' => $product->is_active,
            'created_at' => $product->created_at->toISOString(),
            'updated_at' => $product->updated_at->toISOString(),
        ];
    }

}
