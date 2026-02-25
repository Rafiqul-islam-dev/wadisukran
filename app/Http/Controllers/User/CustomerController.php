<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public $customerService;
    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function customer_list(Request $request){
        return Inertia::render('Customer/Index', [
            'customers' => $this->customerService->list($request)
        ]);
    }

    public function orderCustomerUpdate(Order $order, CustomerRequest $request)
    {
        $validated = $request->validated();
        $customer = Customer::where('phone', $validated['phone'])->first();

        if (!$customer) {
            $customer = $this->customerService->createCustomer($validated);
        }

        $order->customer_id = $customer->id;
        $order->save();

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'message' => 'Customer updated successfully'
        ]);
    }

    public function top_ten_customers(){
        return Inertia::render('Customer/TopTen', [
            'customers' => $this->customerService->topTen()
        ]);
    }
}
