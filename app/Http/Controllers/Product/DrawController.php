<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CompannySetting;
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
            'products.*.numbers.*' => ['string']
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
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $wins = Win::latest()
            ->when($request->product_id, function ($query, $product_id) {
                $query->where('product_id', $product_id);
            })
            ->when($startDate || $request->start_time, function ($query) use ($startDate, $request) {
                $from = null;
                if ($startDate) {
                    $from = Carbon::parse(
                        $startDate . ' ' . ($request->start_time ?? '00:00:00')
                    );
                }
                if ($from) {
                    $query->where('from_time', '>=', $from);
                }
            })
            ->when($endDate || $request->end_time, function ($query) use ($endDate, $request) {
                $to = null;
                if ($endDate) {
                    $to = Carbon::parse(
                        $endDate . ' ' . ($request->end_time ?? '23:59:59')
                    );
                }
                if ($to) {
                    $query->where('to_time', '<=', $to);
                }
            })
            ->with('product')
            ->paginate(10);
        
        $products = Product::orderBy('title')->get();
        $company = CompannySetting::first();
        

        return Inertia::render('Product/Draws/History', [
            'wins' => $wins,
            'products' => $products,
            'logoUrl' => $company->getLogoUrlAttribute(),
            'cupIcon' => static_asset('asset/icon-cup.png'),
            'filters' => [
                'product_id' => $request->product_id,
                'start_date' => $startDate,
                'start_time' => $request->start_time,
                'end_date' => $endDate,
                'end_time' => $request->end_time,
            ]
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

    public function histories_daily(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $dailyCategory = Category::where('draw_type', 'daily')->first();
        $products = Product::where('category_id', $dailyCategory?->id)->orderBy('pick_number', 'asc')->get();

        $histories = [];

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            
            $wins = Win::whereIn('product_id', $products->pluck('id'))
                ->whereDate('to_time', '>=', $start)
                ->whereDate('to_time', '<=', $end)
                ->when($request->start_time, function ($query, $startTime) {
                    $query->whereTime('to_time', '>=', $startTime);
                })
                ->when($request->end_time, function ($query, $endTime) {
                    $query->whereTime('to_time', '<=', $endTime);
                })
                ->get()
                ->groupBy(function($win) {
                    return Carbon::parse($win->to_time)->format('Y-m-d');
                });

            for ($date = $end->copy(); $date->gte($start); $date->subDay()) {
                $dateStr = $date->format('Y-m-d');
                $row = [
                    'date' => $dateStr,
                    'results' => []
                ];
                
                $dateWins = $wins->get($dateStr, collect());
                
                foreach ($products as $product) {
                    $win = $dateWins->where('product_id', $product->id)->first();
                    $row['results'][$product->id] = $win ? [
                        'numbers' => $win->win_number,
                        'time' => $win->to_time,
                    ] : null;
                }
                
                $histories[] = $row;
            }
        }

        return Inertia::render('Product/Draws/DailyHistory', [
            'products' => $products,
            'histories' => $histories,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
            ]
        ]);
    }
}
