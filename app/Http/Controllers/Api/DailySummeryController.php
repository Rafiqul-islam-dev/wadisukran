<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductWiseSalesService;
use Carbon\Carbon;
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
        $request->validate([
            'from_date' => 'required|string',
            'to_date' => 'required|string'
        ]);
        $from = $request->input('from_date'); // YYYY-MM-DD
        $to   = $request->input('to_date');   // YYYY-MM-DD

        // if($from && $to) {
        //     $from = Carbon::parse($from)->startOfDay();
        //     $to   = Carbon::parse($to)->endOfDay();
        // }

        $data = $this->productWiseSalesService->getUserDailySalesSummery(Auth::id(), $from, $to);
        return response()->json($data);
    }
}
