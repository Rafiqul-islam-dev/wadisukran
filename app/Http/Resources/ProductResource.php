<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        if ($this->is_daily == 1) {
            $productdraw = Carbon::today();
        } else {
            // Convert to Carbon instance explicitly
            $productdraw = Carbon::parse($this->draw_date);
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'price' => (float) $this->price,
            'drawDate' => $productdraw->format('Y-m-d'),
            'drawTime' => Carbon::parse($this->draw_time)->format('H:i:s'), // Parse draw_time to Carbon
            'prizes' => $this->prizes,
            'image' => $this->image_url ? url($this->image_url) : 'https://picsum.photos/60/60?random=' . $this->id,
            'type' => $this->type,
            'pick_number' => $this->pick_number,
            'showing_type' => $this->showing_type,
            'type_number' => $this->type_number,
        ];
    }


}
