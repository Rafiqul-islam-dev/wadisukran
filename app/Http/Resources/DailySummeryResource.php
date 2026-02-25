<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailySummeryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product_title' => $this->product_title,
            'total_price' => (float) $this->total_price,
            'total_commission' => (float) $this->total_commission,
            'cancel_orders' => (int) $this->cancel_orders,
            'total_prize_paid' => (float) $this->total_prize_paid,
        ];
    }
}
