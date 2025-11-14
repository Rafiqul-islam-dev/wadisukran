<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('created_at', 'desc')->get()->map(function ($product) {

            $drawTime = $product->draw_time;

            if ($drawTime instanceof Carbon) {
                // If it's a Carbon object, format directly
                $formattedDrawTime = $drawTime->format('H:i');
            } elseif (is_string($drawTime)) {
                // If it's a string, try to parse it or extract HH:mm
                try {
                    $formattedDrawTime = Carbon::parse($drawTime)->format('H:i');
                } catch (\Exception $e) {
                    // If parsing fails, assume it's already in HH:mm:ss or HH:mm format
                    $formattedDrawTime = preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $drawTime)
                        ? substr($drawTime, 0, 5) // Extract HH:mm
                        : '00:00'; // Fallback if invalid
                }
            } else {
                $formattedDrawTime = '00:00'; // Fallback for unexpected cases
            }

            return [
                'id' => $product->id,
                'title' => $product->title,
                'price' => $product->price,
                'draw_date' => $this->getEffectiveDrawDate($product)->format('Y-m-d'),
                'draw_time' => $formattedDrawTime,
                'image_url' => $product->image_url,
                'pick_number' => $product->pick_number,
                'showing_type' => $product->showing_type,
                'type_number' => $product->type_number,
                'prizes' => $product->prizes,
                'is_active' => $product->is_active,
                'is_daily' => $product->is_daily
            ];
        });

        // dd( $products);
        return Inertia::render('Product/Index', [
            'products' => $products
        ]);
    }

    public function create()
    {
        return Inertia::render('Products/Create');
    }

    public function store(Request $request)
    {
        if (is_string($request->prizes)) {
            $request->merge([
                'prizes' => json_decode($request->prizes, true)
            ]);
        }

        if ($request->has('is_active')) {
            $request->merge([
                'is_active' => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        if ($request->has('is_daily')) {
            $request->merge([
                'is_daily' => filter_var($request->is_daily, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
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
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $validated['type'] = 'product';
        $validated['is_active'] = $request->has('is_active') ? true : false;
        $validated['is_daily'] = $request->has('is_daily') ? true : false;

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Product created successfully!');
    }

    public function show(Product $product)
    {
        return Inertia::render('Product/Show', [
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

    public function update(Request $request, Product $product)
    {
        if (is_string($request->prizes)) {
            $request->merge([
                'prizes' => json_decode($request->prizes, true)
            ]);
        }

        if ($request->has('is_active')) {
            $request->merge([
                'is_active' => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        if ($request->has('is_daily')) {
            $request->merge([
                'is_daily' => filter_var($request->is_daily, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
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
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $validated['is_active'] = $request->has('is_active') ? true : false;
        $validated['is_daily'] = $request->has('is_daily') ? true : false;

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Product updated successfully!');
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
}
