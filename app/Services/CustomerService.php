<?php

namespace App\Services;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class CustomerService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return Customer::latest()
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->paginate(10)
            ->withQueryString();
    }

    public function createCustomer(array $data): Customer
    {
        return Customer::create($data);
    }

    public function topTen(){
        return Customer::select('customers.*')
            ->leftJoin('orders', 'orders.customer_id', '=', 'customers.id')
            ->whereMonth('orders.created_at', now()->month)
            ->whereYear('orders.created_at', now()->year)
            ->selectRaw('SUM(orders.total_price) as total_sale')
            ->selectRaw('COUNT(orders.id) as orders_count')
            ->groupBy('customers.id')
            ->orderByDesc('total_sale')
            ->limit(10)
            ->get();
    }
}
