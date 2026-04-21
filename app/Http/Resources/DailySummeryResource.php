<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailySummeryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product_title' => $this->product_title.' '.$this->product_number,
            'total_sell' => (float) ($this->total_sell + $this->cancel_sell),
            'cancel_sell' => (float) $this->cancel_sell,
            'net_sell' => (float) $this->total_sell,
            'commission' => (float) $this->total_commission,
            'prize_paid' => (float) $this->total_prize_paid,
        ];
    }
}
