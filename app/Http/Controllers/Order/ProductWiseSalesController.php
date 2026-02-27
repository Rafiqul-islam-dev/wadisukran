<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ProductWiseSalesService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductWiseSalesController extends Controller
{
    protected $productWiseSalesService;
    public function __construct(ProductWiseSalesService $productWiseSalesService)
    {
        $this->productWiseSalesService = $productWiseSalesService;
    }

    public function index(Request $request)
    {
        $from = $request->input('from_date'); // YYYY-MM-DD
        $to   = $request->input('to_date');   // YYYY-MM-DD

        $data = [];
        if($from && $to) {
            $data = $this->productWiseSalesService->getUserDailySalesSummery($request->agent_id, $from, $to);
        }

        $agents = User::where('status', 'active')->where('user_type', 'agent')->whereHas('agent')->get();

        return Inertia::render('Orders/ProductWiseSales', [
            'data' => $data,
            'agents' => $agents
        ]);
    }
}
