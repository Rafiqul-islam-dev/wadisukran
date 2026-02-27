<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductWiseSalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DailySummeryController extends Controller
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
        $data = $this->productWiseSalesService->getUserDailySalesSummery(Auth::id(), $from, $to);
        return response()->json($data);
    }
}
