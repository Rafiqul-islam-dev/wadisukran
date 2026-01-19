<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Win;
use App\Services\DrawService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DrawController extends Controller
{
    protected $drawService;

    public function __construct(DrawService $drawService)
    {
        $this->drawService = $drawService;
    }

    public function index(Request $request)
    {
        $categories = Category::active()->get();
        $products = Product::where('category_id', $request->category_id)->orderBy('pick_number', 'asc')->get();
        return Inertia::render('Product/Draws/Index', [
            'categories' => $categories,
            'products' => $products,
            'filters' => request()->only(['category_id'])
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],

            'products' => ['required', 'array', 'min:1'],

            'products.*.id' => ['required', 'integer', 'exists:products,id'],
            'products.*.numbers' => ['required', 'array'],
            'products.*.numbers.*' => ['integer']
        ]);
        // return $validated;
        $this->drawService->createWin($validated);
        return back();
    }

    public function histories(){
        $wins = Win::latest()->paginate(10);
        return Inertia::render('Product/Draws/History', [
            'wins' => $wins
        ]);
    }
}
