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
        if (company_setting()->order_status != 1) {
            return response()->json([
                'message' => 'Order placement is currently disabled.',
            ], 403);
        }
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
        if ($product->is_active != 1) {
            return response()->json([
                'message' => 'Product is not active',
            ], 403);
        }
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
            $order = Order::find($id);
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }
            $draw_time = null;

            if ($order->product?->draw_type === 'daily') {
                $draw_time = Carbon::parse($order->created_at)
                    ->addDay()
                    ->startOfDay()
                    ->format('h:i A');
            } else if ($order->product?->draw_type === 'hourly') {
                $draw_time = Carbon::parse($order->created_at)
                    ->addHour()
                    ->startOfHour()
                    ->format('h:i A');
            } else if ($order->product?->draw_type === 'once') {
                $createdAt = Carbon::parse($order->created_at);

                $midDay = $createdAt->copy()->setTime(12, 0, 0);     // 12:00:00 PM
                $endDay = $createdAt->copy()->addDay()->startOfDay();          // 23:59:59

                // compare order created time with midday boundary
                if ($createdAt->lt($createdAt->copy()->startOfDay()->addHours(12))) {
                    // before 12:00 PM
                    $draw_time = $midDay->format('h:i A');
                } else {
                    // 12:00 PM or after
                    $draw_time = $endDay->format('h:i A');
                }
            }



            $data = [
                'id' => $order->id,
                'product_id' => $order->product_id,
                'invoice_no' => $order->invoice_no,
                'quantity' => $order->quantity,
                'total_price' => $order->total_price,
                'vat' => $order->vat,
                'vat_percentage' => $order->vat_percentage,
                'sales_date' => $order->sales_date,
                'draw_number' => $order->draw_number,
                'product_title' => $order->product ? $order->product->title : null,
                'product_price' => $order->product ? $order->product->price : null,
                'image' => $order->product ? static_asset($order->product->image) : null,
                'draw_date' => $order->created_at ? Carbon::parse($order->created_at)->format('d M, Y') : null,
                'draw_time' => $draw_time,
                'vendor_name' => $order->user ? $order->user->name : null,
                'trn' => $order->user ? $order->user->trn : null,
                'qr_url' => $order->qr_url,
                'company_name' => company_setting() ? company_setting()->name : null,
                'company_address' => company_setting() ? company_setting()->address : null,
                'company_email' => company_setting() ? company_setting()->email : null,
                'big_prize' => $order->product?->prizes ? $order->product->prizes->max('prize') : null,
                'tickets' => $order->tickets->map(function ($ticket) {
                    return [
                        'selected_numbers' =>  implode(',', $ticket->selected_numbers),
                        'selected_play_types' => $ticket->selected_play_types,
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $data
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

    public function cancelOrder(Order $order)
    {
        abort_if($order->user_id !== Auth::id(), 403, 'Unauthorized action');

        if ($order->status === 'Printed') {
            return response()->json([
                'success' => false,
                'message' => 'Printed orders cannot be cancelled',
            ], 400);
        }

        if($order->status === 'Cancel') {
            return response()->json([
                'success' => false,
                'message' => 'Order is already cancelled',
            ], 400);
        }
        $order->update([
            'status' => 'Cancel',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully'
        ]);
    }
}
