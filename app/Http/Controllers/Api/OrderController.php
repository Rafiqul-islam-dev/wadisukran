<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\ProductOrderResource;

class OrderController extends Controller
{
    public function orderStore(Request $request)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'game_cards' => 'required|array',
            'game_cards.*.selected_numbers' => 'required|array',
            'game_cards.*.selected_numbers.*' => 'required|string',
            'game_cards.*.selected_play_types' => 'nullable|array', // Changed to array
            'game_cards.*.selected_play_types.*' => 'nullable|string|in:Straight,Ramble,Chance', // Validate each play type
            'quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = Product::find($request->product_id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found',
            ], 404);
        }

        $date = Carbon::parse($request->draw_date)->toDateString(); // "2025-08-02"
        $time = date('H:i:s', strtotime($request->draw_time)); // "17:00:00"
        $drawDateTimeString = $date . ' ' . $time; // "2025-08-02 17:00:00"
        $drawDateTime = Carbon::parse($drawDateTimeString);

        if (now()->gt($drawDateTime)) {
            return response()->json([
                'message' => 'Ticket not available.',
            ], 403);
        }

        // Verify selected_numbers count matches pick_number
        foreach ($request->game_cards as $card) {
            if (count($card['selected_numbers']) != $product->pick_number) {
                return response()->json([
                    'message' => 'Invalid number of selected numbers',
                ], 422);
            }
            // Verify selected_play_types for prizes type
            if ($product->showing_type == 'prizes' && empty($card['selected_play_types'])) {
                return response()->json([
                    'message' => 'At least one play type is required for prizes type',
                ], 422);
            }
        }

        $invoiceNumber = now()->format('YmdH') . random_int(1000, 9999);

        // Before creating the order
        $today = today()->toDateString();

        // Step 1: Check today's draw_number using created_at
        $todayDraw = Order::whereDate('created_at', today())
            ->whereNotNull('draw_number')
            ->orderBy('draw_number', 'desc')
            ->value('draw_number');

        // dd($todayDraw);

        if ($todayDraw) {
            // Use same number for all today's orders
            $drawNumber = $todayDraw;
        } else {
            // Step 2: No draw today, get last draw from previous days
            $lastPrevious = Order::orderBy('sales_date', 'desc')
                ->orderBy('draw_number', 'desc')
                ->value('draw_number');

            if ($lastPrevious) {
                $drawNumber = $lastPrevious + 1;
            } else {
                // Step 3: No orders at all
                $drawNumber = 1;
            }
        }


        $order = Order::create([
            'user_id' => Auth::id(),
            'product_id' => $request->product_id,
            'game_cards' => $request->game_cards,
            'quantity' => $request->quantity,
            'total_price' => $request->total_price,
            'invoice_no' => $invoiceNumber,
            'sales_date' => today()->toDateString(),
            'draw_number' => $drawNumber,
        ]);

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order,
        ], 201);
    }


    public function orderInfo($id)
    {
        try {
            $order = Order::leftJoin('products', 'products.id', '=', 'orders.product_id')
                ->leftJoin('users', 'users.id', '=', 'orders.user_id')
                ->select(
                    'orders.*',
                    'products.title as product_title',
                    'products.price as product_price',
                    'products.image',
                    'products.draw_date',
                    'products.draw_time',
                    'users.name as vendor_name',
                    'users.email as vendor_email',
                    'users.phone as trn'
                )
                ->where('orders.id', $id)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            // Append full image URL
            $order->image = $order->image ? url('storage/' . $order->image) : null;

            return response()->json([
                'success' => true,
                'data' => $order
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }



    /**
     * Get orders by user ID (API).
     *
     * @param int $user_id
     * @return JsonResponse
     */
    public function apiOrdersByUser(Request $request)
    {
        // dd($request->all());
        // Ensure the user is authenticated
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 401);
        }

        try {
            $query = Order::with(['user', 'product'])
                ->where('user_id', auth()->id());

            // Optional date filtering
            if ($request->has('from') && $request->has('to')) {
                $from = Carbon::parse($request->input('from'))->startOfDay();
                $to = Carbon::parse($request->input('to'))->endOfDay();

                $query->whereBetween('sales_date', [$from, $to]);
            }

            $orders = $query->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No orders found for this user',
                    'data' => [],
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Orders retrieved successfully',
                'data' => ProductOrderResource::collection($orders),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching orders',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function orderUpdate(Request $request, $id)
    {
       
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found',
            ], 404);
        }

        $order->status = 'Printed';
        $order->save();

        return response()->json([
            'message' => 'Order updated successfully',
            'order' => $order,
        ], 200);
    }


}
