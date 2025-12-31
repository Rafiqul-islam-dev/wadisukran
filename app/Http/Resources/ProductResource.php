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

            if ($prize->name === 'chance') {
                $chancePrizes[] = (float) $prize->prize;
                continue;
            }
            $prize_array[$prize->name] =
                number_format((float) $prize->prize, 2, '.', '') . ' AED';
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
