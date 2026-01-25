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
use Illuminate\Validation\Rule;
use App\Http\Resources\ProductOrderResource;
use App\Models\OrderTicket;
use App\Services\OrderService;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }


    public function orderStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid product',
                'errors' => $validator->errors(),
            ], 422);
        }
        $productData = $validator->validated();

        $product = Product::find($request->product_id);

        $validator = Validator::make($request->all(), [
            'game_cards' => 'required|array',
            'game_cards.*.selected_numbers' => [
                'required',
                'array'
            ],
            'game_cards.*.selected_numbers.*' => [
                'required',
                'numeric',
                'min:0',
                'max:' . $product->type_number,
            ],
            'game_cards.*.selected_play_types' => [
                Rule::requiredIf($product->prize_type === 'bet'),
                'array',
            ],
            'game_cards.*.selected_play_types.*' => [
                'required',
                'string',
                Rule::in(['Straight', 'Rumble', 'Chance']),
            ],
            'quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid game card data',
                'errors' => $validator->errors(),
            ], 422);
        }
        $gameData = $validator->validated();

        $validated = array_merge($productData, $gameData);

        $validated['user_id'] = Auth::id();

        $order = $this->orderService->createOrder($validated);

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
            $order->qr_code = static_asset($order->qr_code);

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
            if ($request->filled('from') && $request->filled('to')) {
                $from = Carbon::parse($request->from)->format('Y-m-d');
                $to   = Carbon::parse($request->to)->format('Y-m-d');

                $query->whereDate('sales_date', '>=', $from)
                    ->whereDate('sales_date', '<=', $to);
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
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        // Check if remarks is provided (for cancellation)
        if (isset($request->remarks) && !empty($request->remarks)) {
            $order->status = 'Cancel';
            $order->remarks = $request->remarks;
        } else {
            // If no remarks, set status to Printed
            $order->status = 'Printed';
        }

        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => $order,
        ], 200);
    }
}
