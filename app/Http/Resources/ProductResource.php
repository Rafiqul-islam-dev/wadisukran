<?php

namespace App\Http\Resources;

use App\Models\Win;
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
        $draw_date = Carbon::today();
        $draw_time = Carbon::today()->endOfDay();

        if ($this->draw_type === 'daily') {
            $exists_win = Win::where('product_id', $this->id)
                ->whereDate('to_time', Carbon::today())
                ->first();

            if ($exists_win) {
                $draw_time = Carbon::parse($exists_win->to_time);
            } else {
                $draw_time = Carbon::today()->endOfDay();
            }
        } elseif ($this->draw_type === 'hourly') {
            $startOfHour = Carbon::now()->startOfHour();
            $endOfHour   = Carbon::now()->endOfHour();

            $exists_win = Win::where('product_id', $this->id)
                ->whereBetween('to_time', [$startOfHour, $endOfHour])
                ->first();

            if ($exists_win) {
                $draw_time = Carbon::parse($exists_win->to_time);
            } else {
                $draw_time = $endOfHour;
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'price' => (float) $this->price,
            'drawDate' => $draw_date ? $draw_date->format('Y-m-d') : '',
            'drawTime' => $draw_time ? Carbon::parse($draw_time)->format('H:i:s') : '',
            'prizes' => $this->formatPrizes($this->prizes),
            'image' => $this->image_url ? url($this->image_url) : 'https://picsum.photos/60/60?random=' . $this->id,
            'type' => $this->draw_type,
            'pick_number' => $this->pick_number,
            'showing_type' => ($this->prize_type == 'bet' ? 'prizes' : 'number'),
            'type_number' => $this->type_number,
        ];
    }

    public function formatPrizes($prizes)
    {
        $prize_array = [];
        $chancePrizes = [];

        foreach ($prizes as $prize) {

            // chance গুলো একসাথে দেখানোর জন্য
            if ($prize->name === 'chance') {
                $chancePrizes[] = (float) $prize->prize;
                continue;
            }

            $formattedPrize = number_format((float) $prize->prize, 2, '.', '') . ' AED';

            if ($prize->type == 'bet') {
                $prize_array[$prize->name] = $formattedPrize;
                continue;
            }

            if ($prize->type == 'number') {
                $label = trim($prize->name) . ' Number';
                $prize_array[$label] = $formattedPrize;
            } else {
                $label = trim($prize->name) . ' ' . ucfirst($prize->type);
                $prize_array[$label] = $formattedPrize;
            }
        }

        rsort($chancePrizes);

        if (!empty($chancePrizes)) {
            $formatted = array_map(
                fn($p) => number_format($p, 2, '.', '') . ' AED',
                $chancePrizes
            );

            $prize_array['chance'] = implode(' , ', $formatted);
        }

        return $prize_array;
    }
}
