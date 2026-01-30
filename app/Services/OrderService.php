<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Order;
use App\Models\OrderTicket;
use App\Models\Win;
use Illuminate\Support\Facades\Auth;

class OrderService
{
    protected $qrCodeService;
    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }
    public function createOrder(array $data): Order
    {
        $invoiceNumber = now()->format('YmdH') . random_int(100000, 999999);

        $agent = Agent::where('user_id', $data['user_id'])->first();
        $qr_code = $this->qrCodeService->generateQrCodeWithInvoice($invoiceNumber);
        $draw_number = Win::where('product_id', $data['product_id'])
            ->max('draw_number') + 1;

        $order = Order::create([
            'user_id' => $data['user_id'],
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
            'total_price' => $data['total_price'],
            'invoice_no' => $invoiceNumber,
            'qr_code' => $qr_code,
            'sales_date' => today()->toDateString(),
            'draw_number' => $draw_number,
            'vat_percentage' => company_setting()?->vat,
            'commission_percentage' => ($agent->commission ? $agent->commission : 0),
            'vat' => (company_setting() ? ($data['total_price'] * company_setting()->vat) / 100 : 0),
            'commission' => ($agent->commission ? ($data['total_price'] * $agent->commission) / 100 : 0)
        ]);

        foreach ($data['game_cards'] as $card) {
            $selected_numbers = array_map('intval', $card['selected_numbers']);
            OrderTicket::create([
                'order_id' => $order->id,
                'selected_numbers' => $selected_numbers,
                'selected_play_types' => $card['selected_play_types'] ?? null
            ]);
        }
        return $order;
    }
}
