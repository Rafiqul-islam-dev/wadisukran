<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Win;
use App\Services\ProductService;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ProductController extends Controller
{
    protected $productService;
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }
    public function apiIndex()
    {
        $products = Product::active()
            ->orderBy('id', 'desc')
            ->get()
            ->filter(function ($product) {
                return $this->productService->checkProductAvailability($product);
            });

        return ProductResource::collection($products)->additional([
            'phone_require' => company_setting()->customer_phone_require === 1 ? true : false
        ]);;
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


    private function getEffectiveDrawDate(Product $product)
    {
        if (!$product->is_daily) {
            return Carbon::parse($product->draw_date);
        }

        $today = Carbon::today();
        $drawDate = Carbon::parse($product->draw_date);
        $drawTime = Carbon::parse($product->draw_time);
        $currentTime = Carbon::now()->format('H:i');

        if ($today->gt($drawDate) || ($today->eq($drawDate) && $currentTime > $drawTime->format('H:i'))) {
            $daysDiff = $today->diffInDays($drawDate);
            $drawDate->addDays($daysDiff + ($currentTime > $drawTime->format('H:i') ? 1 : 0));
        }

        return $drawDate;
    }
}
