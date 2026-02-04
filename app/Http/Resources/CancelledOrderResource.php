<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CancelledOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'status' => $this->status,
            'product_title' => $this->product->title ?? 'N/A',
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d') : 'N/A',
        ];
    }
}
