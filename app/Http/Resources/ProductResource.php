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
        $draw_date = $this->draw_date;
        $draw_time = $this->draw_time;
        if ($this->draw_type == 'daily') {
            $draw_date = Carbon::today();
        } else if ($this->draw_type == 'hourly') {
            $draw_date = Carbon::today();
            $draw_time = Carbon::parse($this->draw_time)->addHour();
        } else {
            $productdraw = Carbon::parse($this->draw_date);
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'price' => (float) $this->price,
            'drawDate' => $draw_date ? $draw_date->format('Y-m-d') :'',
            'drawTime' => $draw_time ? Carbon::parse($draw_time)->format('H:i:s') : '', // Parse draw_time to Carbon
            'prizes' => $this->prizes,
            'image' => $this->image_url ? url($this->image_url) : 'https://picsum.photos/60/60?random=' . $this->id,
            'type' => $this->draw_type,
            'pick_number' => $this->pick_number,
            'showing_type' => $this->showing_type,
            'type_number' => $this->type_number,
        ];
    }
}
