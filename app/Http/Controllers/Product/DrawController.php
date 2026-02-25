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

    public function histories(Request $request)
    {
        $wins = Win::latest()
            ->when($request->product_id, function ($query, $product_id) {
                $query->where('product_id', $product_id);
            })
            ->when($request->start_date || $request->start_time, function ($query) use ($request) {
                $from = null;
                if ($request->start_date) {
                    $from = Carbon::parse(
                        $request->start_date . ' ' . ($request->start_time ?? '00:00:00')
                    );
                }
                if ($from) {
                    $query->where('from_time', '>=', $from);
                }
            })
            ->when($request->end_date || $request->end_time, function ($query) use ($request) {
                $to = null;
                if ($request->end_date) {
                    $to = Carbon::parse(
                        $request->end_date . ' ' . ($request->end_time ?? '23:59:59')
                    );
                }
                if ($to) {
                    $query->where('to_time', '<=', $to);
                }
            })
            ->with('product')
            ->paginate(10);

        $products = Product::orderBy('title')->get();

        return Inertia::render('Product/Draws/History', [
            'wins' => $wins,
            'products' => $products
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
