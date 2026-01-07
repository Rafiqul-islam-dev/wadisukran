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
        })->with(['user', 'product', 'user.agent'])->limit(10)->get();
        $users = User::select('id', 'name')->get();
        $company = CompannySetting::firstOrFail();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'users' => $users,
            'company' => $company,
            'categories' => $this->categoryService->activeCategories(),
            'filters' => request()->only([
                'user_id',
                'date_from',
                'time_from',
                'date_to',
                'time_to',
                'match_type',
            ]),
        ]);
    }
}
