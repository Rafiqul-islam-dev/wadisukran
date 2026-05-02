<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    protected $categoryService;

    function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }


    public function index()
    {
        $categories = Category::latest()->paginate(10);

        return Inertia::render('Category/Index', [
            'categories' => $categories
        ]);
    }


    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:categories,name',
            'description' => 'nullable|string',
            'draw_type' => 'required|in:once,daily,hourly'
        ]);
        $this->categoryService->createCategory($validated);
        return back();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|unique:categories,name,' . $id,
            'description' => 'nullable|string',
            'draw_type' => 'required|in:once,daily,hourly'
        ]);

        Category::find($id)->update([
            'name' => $request->name,
            'description' => $request->description,
            'draw_type' => $request->draw_type
        ]);

        $products = Product::where('category_id', $id)->get();
        foreach($products as $product){
            $product->draw_type = $request->draw_type;
            $product->save();
        }

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::find($id);
        if($category->products->count() > 0){
            return redirect()->back()->withErrors([
                'message' => 'This category cannot be deleted. Because it has products.'
            ]);
        }
        $category->delete();
        return back();
    }

    public function status_change(Category $category)
    {
        // dd($category);
        $category->status = $category->status === 1 ? 0 : 1;
        $category->save();

        // Update products' is_active based on new category status
        // If category is now Inactive (0), products become Active (1)
        // If category is now Active (1), products become Inactive (0)
        $productIsActive = $category->status === 1 ? 1 : 0;

        // dd($productIsActive);
        Product::where('category_id', $category->id)->update(['is_active' => $productIsActive]);

        return back();
    }
}
