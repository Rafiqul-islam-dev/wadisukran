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

        if ($this->draw_type === 'hourly') {
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
        } else if ($this->draw_type === 'once') {
            $draw_times = json_decode($this->draw_time, true);
            $now = Carbon::now();
            $next_time = null;

            foreach ($draw_times as $time) {
                if ($time === '00:00') {
                    $timeCarbon = Carbon::today()->endOfDay();
                } else {
                    $timeCarbon = Carbon::createFromFormat('H:i', $time)
                        ->setDate($now->year, $now->month, $now->day);
                }

                if ($timeCarbon->greaterThan($now)) {
                    if (!$next_time || $timeCarbon->lessThan($next_time)) {
                        $next_time = $timeCarbon;
                    }
                }
            }

            if (!$next_time) {
                $next_time = Carbon::createFromFormat('H:i', $draw_times[0])
                    ->setDate($now->year, $now->month, $now->day)
                    ->addDay();
            }
            $next_time = $next_time->subSecond();

            $format_next_time = $next_time->format('H:i:s');
            $format_now_time = $now->format('H:i:s');

            if ($format_next_time < $format_now_time) {
                $draw_date = $now->copy()->addDay();
            }

            $draw_time = $next_time->format('H:i:s');
        }

        $numberImage = null;

        if (!empty($this->product_number)) {
            $imagePath = 'asset/number-' . $this->product_number . '.png';

            if (file_exists(public_path($imagePath))) {
                $numberImage = asset($imagePath);
            }
        }


        return [
            'id' => $this->id,
            'title' => $this->title,
            'product_number' => $this->product_number,
            'number_image' => $numberImage,
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
        // Chance + bet হলে আলাদা format হবে
        if (trim($prize->name) === 'Chance' && $prize->type === 'bet') {
            $chancePrizes[] = [
                'chance_number' => (int) ($prize->chance_number ?? 0),
                'prize' => (float) $prize->prize,
            ];
            continue;
        }

        $formattedPrize = number_format((float) $prize->prize, 2, '.', '') . ' AED';

        if ($prize->type === 'bet') {
            $prize_array[$prize->name] = $formattedPrize;
            continue;
        }

        if ($prize->type === 'number') {
            $label = trim($prize->name) . ' Digit Match';
            $prize_array[$label] = $formattedPrize;
        } else {
            $label = trim($prize->name) . ' ' . ucfirst($prize->type);
            $prize_array[$label] = $formattedPrize;
        }
    }

    // Chance prizes format by chance_number
    if (!empty($chancePrizes)) {
        usort($chancePrizes, function ($a, $b) {
            return $b['chance_number'] <=> $a['chance_number']; // high to low
        });

        $chanceTexts = array_map(function ($item) {
            return $item['chance_number'] . ' Digit Match ' .
                number_format($item['prize'], 2, '.', '') . ' AED';
        }, $chancePrizes);

        $prize_array['Chance'] = implode(', ', $chanceTexts);
    }

    return $prize_array;
}
}
