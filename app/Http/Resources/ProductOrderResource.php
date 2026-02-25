<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'user_id' => $this->user_id,
            'user_name' => $this->user->name ?? 'N/A',
            'user_phone' => $this->user->phone ?? 'N/A',
            'product_id' => $this->product_id,
            'product_title' => $this->product->title ?? 'N/A',
            'product_image' => $this->product->image ? asset('storage/' . $this->product->image) : null,
            'product_price' => $this->product->price ?? 'N/A',
            'draw_date' => $this->product->draw_date ? \Carbon\Carbon::parse($this->product->draw_date)->format('Y-m-d') : 'N/A',
            'draw_date' => $this->product->draw_time ? \Carbon\Carbon::parse($this->product->draw_time)->format('h:i A') : 'N/A',
            'quantity' => $this->quantity,
            'status' => $this->status,
            'total_price' => number_format($this->total_price, 2) . ' AED',
            'sales_date' => $this->sales_date ? \Carbon\Carbon::parse($this->sales_date)->format('Y-m-d') : 'N/A',
            'game_cards' => $this->game_cards ?? [],
            'big_prize' => $this->product?->prizes ? $this->product->prizes->max('prize') : null
        ];
    }
}
