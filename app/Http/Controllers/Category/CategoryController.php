<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Category;
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
        $categories = Category::paginate(10);

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
            'description' => 'nullable|string'
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
