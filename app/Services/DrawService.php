<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderTicket;
use App\Models\Product;
use App\Models\Win;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class DrawService
{
    public function createWin(array $data): string
    {
        if (is_array($data['products'])) {
            foreach ($data['products'] as $product) {
                $maxDrawNumber = Win::where('product_id', $product['id'])->max('draw_number');
                $productData = Product::find($product['id']);

                if ($productData->draw_type === 'daily') {
                    $exists_draw = Win::where('product_id', $product['id'])->whereDate('to_time', Carbon::parse($data['to_time'])->toDateString())->first();
                    if (!$exists_draw) {
                        $fromTime = Carbon::parse($data['to_time'])->startOfDay();
                        $win = Win::create([
                            'product_id' => $product['id'],
                            'from_time' => $fromTime,
                            'to_time' => $data['to_time'],
                            'draw_number' => ($maxDrawNumber + 1),
                            'draw_time' =>  $data['to_time'],
                            'win_number' => $product['numbers']
                        ]);
                    } else {
                        $win = $exists_draw;
                    }
                } else if($productData->draw_type === 'hourly') {
                    $exists_draw = Win::where('product_id', $product['id'])->whereDate('to_time', Carbon::parse($data['to_time'])->toDateTimeString())->first();
                    if(!$exists_draw){
                        $win = Win::create([
                            'product_id' => $product['id'],
                            'from_time' => Carbon::parse($data['to_time'])->startOfHour(),
                            'to_time' => $data['to_time'],
                            'draw_number' => ($maxDrawNumber + 1),
                            'draw_time' =>  $data['to_time'],
                            'win_number' => $product['numbers']
                        ]);
                    }
                    else{
                        $win = $exists_draw;
                    }
                }
                else if($productData->draw_type === 'once'){
                    $draw_times = json_decode($productData->draw_time, true);
                    $toTime = Carbon::parse($data['to_time']);
                    $toOnlyTimeStr = $toTime->format('H:i');

                    if (!in_array($toOnlyTimeStr, $draw_times)) {
                        throw ValidationException::withMessages([
                            'products' => ["The draw time ({$toOnlyTimeStr}) is not valid for product '{$productData->title} {$productData->product_number}'. Available times: " . implode(', ', $draw_times)]
                        ]);
                    }

                    $existingWinsCount = Win::where('product_id', $product['id'])
                            ->whereDate('to_time', $toTime->toDateString())
                            ->count();

                    if ($existingWinsCount >= count($draw_times)) {
                        throw ValidationException::withMessages([
                            'products' => ["Maximum draw limit reached for product '{$productData->title} {$productData->product_number}' today."]
                        ]);
                    }

                    $exists_draw = Win::where('product_id', $product['id'])->where('to_time', $toTime->toDateTimeString())->whereDate('to_time', $toTime->toDateString())->first();

                    if (!$exists_draw) {
                        $fromTime = $toTime->copy()->startOfDay();
                        Win::create([
                            'product_id' => $product['id'],
                            'from_time' => $fromTime,
                            'to_time' => $data['to_time'],
                            'draw_number' => ($maxDrawNumber + 1),
                            'draw_time' =>  $data['to_time'],
                            'win_number' => $product['numbers']
                        ]);
                    }
                    else{
                        $win = $exists_draw;
                    }
                }
            }
        }
        return 'Win created successfully';
    }

    public function updateWinner(Win $win)
    {
        $product = $win->product;

        $numbersStraight = collect($win->win_number)->values();
        $numbersChance = collect($win->win_number)->reverse()->values();
        $numbersSorted   = collect($win->win_number)->sort()->values();
        $len             = $numbersStraight->count();

        $from_time = $win->from_time;
        if($product->category?->draw_type == 'once'){
            $from_time = Carbon::parse($win->from_time)->copy()->startOfDay();
        }

        $orders = OrderTicket::query()
            ->withoutRiskHold()
            ->whereHas('order', function ($q) use ($product) {
                $q->where('status', 'Printed')->where('is_claimed', 0)->where('is_winner', 0)->where('product_id', $product->id);
            })
            ->whereBetween('created_at', [$from_time, $win->to_time])
            ->get()
            ->map(function ($order) use ($numbersStraight, $numbersSorted, $len, $product, $numbersChance) {
                $data = ['id' => $order->id, 'selected_numbers' => $order->selected_numbers, 'order_id' => $order->order_id];
                $isStraightWinner = false;
                $isRumbleWinner = false;
                $isChanceWinner = false;
                $isNumberWinner = false;
                $ticketTypes   = is_array($order->selected_play_types)
                    ? $order->selected_play_types
                    : (array) $order->selected_play_types;

                $ticketNumbers = collect($order->selected_numbers)->values();
                if ($product->prize_type === 'bet') {
                    foreach ($product->prizes->whereIn('name', ['Straight', 'Rumble']) as $type) {
                        if ($type->name == 'Straight' & in_array('Straight', $ticketTypes, true)) {
                            $isStraightWinner =
                                $ticketNumbers->count() == $len &&
                                $ticketNumbers->all() == $numbersStraight->all();
                            $data[$type->name] = $isStraightWinner;
                        } else if ($type->name == 'Rumble' && in_array('Rumble', $ticketTypes, true)) {
                            if ($isStraightWinner == false) {
                                $isRumbleWinner =
                                    $ticketNumbers->count() == $len &&
                                    $ticketNumbers->sort()->values()->all() == $numbersSorted->all();
                            }
                            $data[$type->name] = $isRumbleWinner;
                        }
                    }
                    if (in_array('Chance', $ticketTypes, true)) {
                        $matchCount = $ticketNumbers->reverse()
                            ->values()
                            ->zip($numbersChance)
                            ->takeWhile(fn($pair) => (string)$pair[0] == (string)$pair[1])
                            ->count();

                        $chancePrizes = $product->prizes
                            ->where('name', 'Chance')
                            ->sortByDesc('chance_number')
                            ->values();


                        foreach ($chancePrizes as $chanceType) {
                            $key = $chanceType->name . ' ' . $chanceType->chance_number;
                            $data[$key] = false;

                            if ($isStraightWinner || $isRumbleWinner || $isChanceWinner) {
                                continue;
                            }

                            if ($matchCount == (int) $chanceType->chance_number) {
                                $data[$key] = true;
                                $isChanceWinner = true;
                            }
                        }
                    }
                } else {
                    if ((int)$product->product_number === 9) {
                        $matchCount = $ticketNumbers->zip($numbersStraight)
                            ->takeWhile(fn($pair) => (string)$pair[0] === (string)$pair[1])
                            ->count();
                        $matchedNumbers = $ticketNumbers->take($matchCount);
                    } else if ((int)$product->product_number === 2) {
                        $matchCount = $ticketNumbers->reverse()->values()
                            ->zip($numbersStraight->reverse()->values())
                            ->takeWhile(fn($pair) => (string)$pair[0] === (string)$pair[1])
                            ->count();
                        $matchedNumbers = $ticketNumbers->reverse()->take($matchCount);
                    } else {
                        $matchedNumbers = $ticketNumbers->intersect($numbersStraight);
                        $matchCount = $matchedNumbers->count();
                    }
                    $numberPrizes = $product->prizes
                        ->sortByDesc('name');


                    foreach ($numberPrizes as $prize) {
                        $key = is_numeric($prize->name) ? 'Number ' . $prize->name : $prize->name;
                        $data[$key] = false;

                        if ($isNumberWinner) continue;

                        $prizeNameLower = strtolower($prize->name);
                        if ($prizeNameLower === 'platinum') {
                            if ($matchCount === 1 && in_array($matchedNumbers->first(), [25, 26, 27])) {
                                $data[$key] = true;
                                $isNumberWinner = true;
                            }
                        } else if ($prizeNameLower === 'golden') {
                            if ($matchCount === 1 && in_array($matchedNumbers->first(), [5, 6, 7])) {
                                $data[$key] = true;
                                $isNumberWinner = true;
                            }
                        } else if ($prizeNameLower === 'normal') {
                            if ($matchCount === 1 && !in_array($matchedNumbers->first(), [25, 26, 27, 5, 6, 7])) {
                                $data[$key] = true;
                                $isNumberWinner = true;
                            }
                        } else {
                            if ($matchCount === (int) $prize->name) {
                                $data[$key] = true;
                                $isNumberWinner = true;
                            }
                        }
                    }
                }
                $orderHasWon = $isStraightWinner || $isRumbleWinner || $isChanceWinner || $isNumberWinner;

                if ($orderHasWon) {
                    return $data;
                }

                return null;
            })->filter();
        $orders = app(RiskCapService::class)->applyToWinnerRows($orders, $product);
        foreach ($orders as $order) {
            OrderTicket::find($order['id'])->update(['is_winner' => 1]);
            Order::find($order['order_id'])->update(['is_winner' => 1]);
        }

        return 'Winner updated successfully';
    }
}
