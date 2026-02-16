<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Win;
use App\Services\DrawService;
use Carbon\Carbon;
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
        $toTime = Carbon::createFromFormat('Y-m-d H:i', $validated['date'] . ' ' . $validated['time']);
        $toTime->second(59);
        $data['to_time'] = $toTime->toDateTimeString(); // Format it as a string 'Y-m-d H:i:s'
        $data['products'] = $validated['products'];
        $this->drawService->createWin($data);
        return back();
    }

    public function histories()
    {
        $wins = Win::latest()->with('product')->paginate(10);
        return Inertia::render('Product/Draws/History', [
            'wins' => $wins
        ]);
    }

    public function histories_delete(Win $win)
    {
        if ($win->claims->count() > 0) {
            return back()->with('error', 'This draw already claimed some user.');
        }
        $win->delete();
        return back();
    }
}
