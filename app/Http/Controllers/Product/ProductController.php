<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class ProductController extends Controller
{
    protected $productService;

    function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index()
    {
        $categories = Category::where('status', 1)->orderBy('name')->get();
        $products = Product::latest()->with(['category', 'prizes:id,product_id,type,name,prize'])->paginate(10);
        // return $products;

        return Inertia::render('Product/Index', [
            'products' => $products,
            'categories' => $categories
        ]);
    }

    public function create()
    {
        return Inertia::render('Products/Create');
    }

    public function store(ProductRequest $request)
    {
        $data = [
            'title' => $request->title,
            'category_id' => $request->category_id,
            'price' => $request->price,
            'draw_type' => ($request->draw_type === 'regular' ? $request->regular_type : $request->draw_type),
            'draw_date' => $request->draw_date,
            'draw_time' => $request->draw_time,
            'pick_number' => $request->pick_number,
            'type_number' => $request->type_number,
            'image' => $request->image,
            'prize_type' => $request->prize_type,
            'prizes' => $request->prize_type === 'bet' ? $request->bet_prizes : $request->number_prizes
        ];

        $this->productService->createProduct($data);

        return back();
    }

    public function show(Product $product)
    {
        $product = $product->load(['prizes', 'category']);
        return Inertia::render('Product/Show', [
            'product' => $product
        ]);
    }

    public function edit(Product $product)
    {
        return Inertia::render('Products/Edit', [
            'product' => [
                'id' => $product->id,
                'title' => $product->title,
                'price' => $product->price,
                'draw_date' => $this->getEffectiveDrawDate($product)->format('Y-m-d'),
                'draw_time' => $product->draw_time,
                'image_url' => $product->image_url,
                'pick_number' => $product->pick_number,
                'showing_type' => $product->showing_type,
                'type_number' => $product->type_number,
                'prizes' => $product->prizes,
                'is_active' => $product->is_active,
                'is_daily' => $product->is_daily
            ]
        ]);
    }

    public function update(ProductUpdateRequest $request, Product $product)
    {
        $data = [
            'title' => $request->title,
            'category_id' => $request->category_id,
            'price' => $request->price,
            'draw_type' => $request->draw_type === 'once' ? $request->draw_type : $request->regular_type,
            'draw_date' => $request->draw_date,
            'draw_time' => $request->draw_time,
            'pick_number' => $request->pick_number,
            'type_number' => $request->type_number,
            'image' => $request->image,
            'prize_type' => $request->prize_type,
            'prizes' => $request->prize_type === 'bet' ? $request->bet_prizes : $request->number_prizes
        ];

        $this->productService->updateProduct($product, $data);

        return back();
    }


    public function destroy(Product $product)
    {
        // Delete image if exists
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted successfully!');
    }

    // API endpoint for Flutter app
    public function apiIndex()
    {
        $products = Product::active()
            ->byType('product')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'price' => (float) $product->price,
                    'drawDate' => $product->draw_date->format('Y-m-d'),
                    'drawTime' => $product->draw_time->format('H:i:s'),
                    'prizes' => $product->prizes,
                    'image' => $product->image_url ? url($product->image_url) : 'https://picsum.photos/60/60?random=' . $product->id,
                    'type' => $product->type,
                    'pick_number' => $product->pick_number,
                    'showing_type' => $product->showing_type,
                    'type_number' => $product->type_number,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    public function apiShow(Product $product)
    {
        if (!$product->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'title' => $product->title,
                'price' => (float) $product->price,
                'drawDate' => $product->draw_date->format('Y-m-d'),
                'drawTime' => $product->draw_time->format('H:i:s'),
                'prizes' => $product->prizes,
                'image' => $product->image_url ? url($product->image_url) : 'https://picsum.photos/60/60?random=' . $product->id,
                'type' => $product->type,
                'pick_number' => $product->pick_number,
                'showing_type' => $product->showing_type,
                'type_number' => $product->type_number,
            ]
        ]);
    }


    private function getEffectiveDrawDate(Product $product)
    {
        if (!$product->is_daily) {
            return Carbon::parse($product->draw_date);
        }

        $today = Carbon::today();

        return $today;
    }

    public function status_change(Product $product){
        $this->productService->statusChange($product);
        return back();
    }
}
