<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\CompannySetting;
use App\Services\CategoryService;
use Inertia\Inertia;

class OrderController extends Controller
{
    protected $categoryService;
    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }
    public function index(Request $request)
    {
        $orders = Order::when($request->user_id, function ($query, $userId) {
            $query->where('user_id', $userId);
        })
        ->when($request->date_from, function ($query, $dateFrom) use ($request) {
            $timeFrom = $request->time_from ?? '00:00:00';
            $query->where('created_at', '>=', "$dateFrom $timeFrom");
        })
        ->when($request->date_to, function ($query, $dateTo) use ($request) {
            $timeTo = $request->time_to ?? '23:59:59';
            $query->where('created_at', '<=', "$dateTo $timeTo");
        })
        ->when($request->category_id, function ($query, $categoryId) {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        })
        ->when($request->match_type, function ($query, $matchType) {
            $query->whereHas('product', function ($q) use ($matchType) {
                $q->where('prize_type', $matchType);
            });
        })
        ->with(['user', 'product', 'user.agent'])->limit(10)->get();
        $users = User::select('id', 'name')->get();
        $company = CompannySetting::firstOrFail();
        $categories = $this->categoryService->activeCategories();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'users' => $users,
            'company' => $company,
            'categories' => $categories,
            'filters' => request()->only([
                'user_id',
                'date_from',
                'time_from',
                'date_to',
                'time_to',
                'match_type',
                'category_id',
            ]),
        ]);
    }
}
