<?php

namespace App\Services;

use App\Models\AgentAccount;
use App\Models\Claim;
use App\Models\Order;
use App\Models\OrderTicket;
use App\Models\Product;
use App\Models\Win;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CheckWinService
{
    public function CheckWinByInvoice(string $invoice): array
    {
        $invoice = Order::where('invoice_no', $invoice)->first();

        $product = Product::find($invoice->product_id);

        $win = Win::where('product_id', $invoice->product_id)
            ->whereRaw('? BETWEEN from_time AND to_time', [$invoice->created_at])
            ->first();

        $summery = [];
        $total_prize = 0;
        if ($win) {
            $types = $product->prizes;

            $numbersStraight = collect($win->win_number)->values();
            $numbersChance = collect($win->win_number)->reverse()->values();
            $numbersSorted   = collect($win->win_number)->sort()->values();
            $len             = $numbersStraight->count();
            $orders = OrderTicket::query()
                ->where('order_id', $invoice->id)
                ->whereHas('order', function ($q) {
                    $q->where('status', 'Printed')->where('is_claimed', 0);
                })
                ->get()
                ->map(function ($order) use ($numbersStraight, $numbersSorted, $len, $product, $numbersChance) {
                    $data = ['id' => $order->id, 'selected_numbers' => $order->selected_numbers];
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
                            if ($type->name === 'Straight' & in_array('Straight', $ticketTypes, true)) {
                                $isStraightWinner =
                                    $ticketNumbers->count() === $len &&
                                    $ticketNumbers->all() === $numbersStraight->all();
                                $data[$type->name] = $isStraightWinner;
                            } else if ($type->name === 'Rumble' && in_array('Rumble', $ticketTypes, true)) {
                                if ($isStraightWinner == false) {
                                    $isRumbleWinner =
                                        $ticketNumbers->count() === $len &&
                                        $ticketNumbers->sort()->values()->all() === $numbersSorted->all();
                                }
                                $data[$type->name] = $isRumbleWinner;
                            }
                        }
                        if (in_array('Chance', $ticketTypes, true)) {
                            $matchCount = $ticketNumbers->reverse()
                                ->values()
                                ->zip($numbersChance)
                                ->takeWhile(fn($pair) => (string)$pair[0] === (string)$pair[1])
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
                        $matchCount = $ticketNumbers->intersect($numbersStraight)->count();
                        $numberPrizes = $product->prizes
                            ->sortByDesc('name');


                        foreach ($numberPrizes as $prize) {
                            $key = 'Number ' . (int) $prize->name;
                            $data[$key] = false;

                            if ($isNumberWinner) continue;

                            if ($matchCount === (int) $prize->name) {
                                $data[$key] = true;
                                $isNumberWinner = true;
                            }
                        }
                    }
                    $orderHasWon = $isStraightWinner || $isRumbleWinner || $isChanceWinner || $isNumberWinner;

                    if ($orderHasWon) {
                        return $data;
                    }

                    return null;
                })->filter();
            foreach ($types as $type) {
                if (is_numeric($type->name)) {
                    $name = 'Number ' . $type->name;
                    if ($orders->where($name, true)->count() > 0) {
                        $summery[] = [
                            'match_type' => $name,
                            'number_of_ticket' =>  $orders->where($name, true)->count(),
                            'prize_per_winner' => $type->prize,
                            'tickets' => $orders->where($name, true)->pluck('selected_numbers')->map(fn ($numbers) => implode(',', $numbers))->values()->toArray()
                        ];
                        $total_prize += ($orders->where($name, true)->count() * $type->prize);
                    }
                } else if ($type->name === 'Chance') {
                    $name = $type->name . ' ' . $type->chance_number;
                    if ($orders->where($name, true)->count() > 0) {
                        $summery[] = [
                            'match_type' => $name,
                            'number_of_ticket' =>  $orders->where($name, true)->count(),
                            'prize_per_winner' => $type->prize,
                            'tickets' => $orders->where($name, true)->pluck('selected_numbers')->map(fn ($numbers) => implode(',', $numbers))->values()->toArray()
                        ];
                        $total_prize += ($orders->where($name, true)->count() * $type->prize);
                    }
                } else {
                    if ($orders->where($type->name, true)->count() > 0) {
                        $summery[] = [
                            'match_type' => $type->name,
                            'number_of_ticket' =>  $orders->where($type->name, true)->count(),
                            'prize_per_winner' => $type->prize,
                            'tickets' => $orders->where($type->name, true)->pluck('selected_numbers')->map(fn ($numbers) => implode(',', $numbers))->values()->toArray()
                        ];
                        $total_prize += ($orders->where($type->name, true)->count() * $type->prize);
                    }
                }
            }
        }

        if ($summery) {
            $invoice->is_winner = 1;
            $exists = AgentAccount::where('order_id', $invoice->id)->exists();
            if(!$exists){
                $accountService = new AgentAccountService();
                $accountService->store([
                    'user_id' => $invoice->user_id,
                    'type' => 'win',
                    'amount' => $total_prize,
                    'order_id' => $invoice->id
                ]);
            }
            $invoice->save();
        }
        return ['summery' => $summery, 'total_prize' => $total_prize];
    }

    public function CheckWinByInvoiceOnce(string $invoiceNo): array{
        $invoice = Order::where('invoice_no', $invoiceNo)->firstOrFail();
        $product = Product::find($invoice->product_id);
        $invoiceDate = Carbon::parse($invoice->created_at)->toDateString();
        $wins = Win::where('product_id', $invoice->product_id)
            ->whereDate('from_time', $invoiceDate)
            ->orderBy('to_time')
            ->get();

        $summery = [];
        $total_prize = 0;

        foreach ($wins as $win) {
            $types = $product->prizes;

            $numbersStraight = collect($win->win_number)->values();
            $numbersChance = collect($win->win_number)->reverse()->values();
            $numbersSorted   = collect($win->win_number)->sort()->values();
            $len             = $numbersStraight->count();
            $orders = OrderTicket::query()
                ->where('order_id', $invoice->id)
                ->whereHas('order', function ($q) {
                    $q->where('status', 'Printed')->where('is_claimed', 0);
                })
                ->get()
                ->map(function ($order) use ($numbersStraight, $numbersSorted, $len, $product, $numbersChance) {
                    $data = ['id' => $order->id, 'selected_numbers' => $order->selected_numbers];
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
                            if ($type->name === 'Straight' & in_array('Straight', $ticketTypes, true)) {
                                $isStraightWinner =
                                    $ticketNumbers->count() === $len &&
                                    $ticketNumbers->all() === $numbersStraight->all();
                                $data[$type->name] = $isStraightWinner;
                            } else if ($type->name === 'Rumble' && in_array('Rumble', $ticketTypes, true)) {
                                if ($isStraightWinner == false) {
                                    $isRumbleWinner =
                                        $ticketNumbers->count() === $len &&
                                        $ticketNumbers->sort()->values()->all() === $numbersSorted->all();
                                }
                                $data[$type->name] = $isRumbleWinner;
                            }
                        }
                        if (in_array('Chance', $ticketTypes, true)) {
                            $matchCount = $ticketNumbers->reverse()
                                ->values()
                                ->zip($numbersChance)
                                ->takeWhile(fn($pair) => (string)$pair[0] === (string)$pair[1])
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
                        $matchCount = $ticketNumbers->intersect($numbersStraight)->count();
                        $numberPrizes = $product->prizes
                            ->sortByDesc('name');


                        foreach ($numberPrizes as $prize) {
                            $key = 'Number ' . (int) $prize->name;
                            $data[$key] = false;

                            if ($isNumberWinner) continue;

                            if ($matchCount === (int) $prize->name) {
                                $data[$key] = true;
                                $isNumberWinner = true;
                            }
                        }
                    }
                    $orderHasWon = $isStraightWinner || $isRumbleWinner || $isChanceWinner || $isNumberWinner;

                    if ($orderHasWon) {
                        return $data;
                    }

                    return null;
                })->filter();
            foreach ($types as $type) {
                if (is_numeric($type->name)) {
                    $name = 'Number ' . $type->name;
                    if ($orders->where($name, true)->count() > 0) {
                        $summery[] = [
                            'match_type' => $name,
                            'number_of_ticket' =>  $orders->where($name, true)->count(),
                            'prize_per_winner' => $type->prize,
                            'tickets' => $orders->where($name, true)->pluck('selected_numbers')->map(fn ($numbers) => implode(',', $numbers))->values()->toArray()
                        ];
                        $total_prize += ($orders->where($name, true)->count() * $type->prize);
                    }
                } else if ($type->name === 'Chance') {
                    $name = $type->name . ' ' . $type->chance_number;
                    if ($orders->where($name, true)->count() > 0) {
                        $summery[] = [
                            'match_type' => $name,
                            'number_of_ticket' =>  $orders->where($name, true)->count(),
                            'prize_per_winner' => $type->prize,
                            'tickets' => $orders->where($name, true)->pluck('selected_numbers')->map(fn ($numbers) => implode(',', $numbers))->values()->toArray()
                        ];
                        $total_prize += ($orders->where($name, true)->count() * $type->prize);
                    }
                } else {
                    if ($orders->where($type->name, true)->count() > 0) {
                        $summery[] = [
                            'match_type' => $type->name,
                            'number_of_ticket' =>  $orders->where($type->name, true)->count(),
                            'prize_per_winner' => $type->prize,
                            'tickets' => $orders->where($type->name, true)->pluck('selected_numbers')->map(fn ($numbers) => implode(',', $numbers))->values()->toArray()
                        ];
                        $total_prize += ($orders->where($type->name, true)->count() * $type->prize);
                    }
                }
            }

            if ($summery) {
                break;
            }
        }

        if ($summery) {
            $invoice->is_winner = 1;

            $exists = AgentAccount::where('order_id', $invoice->id)->exists();

            if (!$exists) {
                $accountService = new AgentAccountService();
                $accountService->store([
                    'user_id' => $invoice->user_id,
                    'type'    => 'win',
                    'amount'  => $total_prize,
                    'order_id'=> $invoice->id
                ]);
            }

            $invoice->save();
        }

        return ['summery' => $summery, 'total_prize' => $total_prize];
    }

    public function ClaimWin(string $invoice) : string
    {
        $invoice = Order::where('invoice_no', $invoice)->first();
        $win = Win::where('product_id', $invoice->product_id)->whereRaw('? BETWEEN from_time AND to_time', [$invoice->created_at])->first();
        $check_summery = $this->CheckWinByInvoice($invoice->invoice_no);
        if($check_summery['total_prize'] <= 0){
            if($invoice->product->draw_type === 'once'){
                $check_summery = $this->CheckWinByInvoiceOnce($invoice->invoice_no);
            }
        }
        $totalWonAmount = $check_summery['total_prize'];

        Claim::create([
            'user_id' => $invoice->user_id,
            'win_id' => $win->id,
            'invoice_no' => $invoice->invoice_no,
            'amount' => $totalWonAmount,
            'claimed_by' => Auth::id()
        ]);

        $invoice->is_claimed = 1;
        $accountService = new AgentAccountService();
        $accountService->store([
            'user_id' => $invoice->user_id,
            'type' => 'claim',
            'amount' => $totalWonAmount
        ]);
        $invoice->save();

        return 'Win Claimed successfully.';
    }

    public function checkWinOrderTicketsByInvoice(string $invoice)
    {
        $invoice = Order::where('invoice_no', $invoice)->first();

        $win = Win::where('product_id', $invoice->product_id)
            ->whereRaw('? BETWEEN from_time AND to_time', [$invoice->created_at])
            ->first();
        $summery = [];

        if ($win) {
            $product = Product::withTrashed()->find($invoice->product_id);

            $types = $product->prizes;

            $numbersStraight = collect($win->win_number)->values();
            $numbersChance = collect($win->win_number)->reverse()->values();
            $numbersSorted   = collect($win->win_number)->sort()->values();
            $len             = $numbersStraight->count();

            $orders = OrderTicket::query()
                ->where('order_id', $invoice->id)
                ->get()
                ->map(function ($order) use ($numbersStraight, $numbersSorted, $len, $product, $numbersChance) {
                    $data = ['id' => $order->id, 'selected_numbers' => $order->selected_numbers];
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
                            if ($type->name === 'Straight' & in_array('Straight', $ticketTypes, true)) {
                                $isStraightWinner =
                                    $ticketNumbers->count() === $len &&
                                    $ticketNumbers->all() === $numbersStraight->all();
                                $data[$type->name] = $isStraightWinner;
                            } else if ($type->name === 'Rumble' && in_array('Rumble', $ticketTypes, true)) {
                                if ($isStraightWinner == false) {
                                    $isRumbleWinner =
                                        $ticketNumbers->count() === $len &&
                                        $ticketNumbers->sort()->values()->all() === $numbersSorted->all();
                                }
                                $data[$type->name] = $isRumbleWinner;
                            }
                        }
                        if (in_array('Chance', $ticketTypes, true)) {
                            $matchCount = $ticketNumbers->reverse()
                                ->values()
                                ->zip($numbersChance)
                                ->takeWhile(fn($pair) => (string)$pair[0] === (string)$pair[1])
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
                        $matchCount = $ticketNumbers->intersect($numbersStraight)->count();
                        $numberPrizes = $product->prizes
                            ->sortByDesc('name');


                        foreach ($numberPrizes as $prize) {
                            $key = 'Number ' . (int) $prize->name;
                            $data[$key] = false;

                            if ($isNumberWinner) continue;

                            if ($matchCount === (int) $prize->name) {
                                $data[$key] = true;
                                $isNumberWinner = true;
                            }
                        }
                    }
                    $orderHasWon = $isStraightWinner || $isRumbleWinner || $isChanceWinner || $isNumberWinner;

                    if ($orderHasWon) {
                        return $data;
                    }

                    return null;
                })->filter();

            $summery['tickets'] = $orders;
            foreach ($types as $type) {
                if (is_numeric($type->name)) {
                    $name = 'Number ' . $type->name;
                    if ($orders->where($name, true)->count() > 0) {
                        $summery[$name] = [
                            'match_type' => $name,
                            'number_of_ticket' =>  $orders->where($name, true)->count(),
                            'prize_per_winner' => $type->prize,
                            'tickets' => $orders->where($name, true)->pluck('id')->implode(','),
                            'total_amount' => ($orders->where($name, true)->count() * $type->prize)
                        ];
                    }
                } else if ($type->name === 'Chance') {
                    $name = $type->name . ' ' . $type->chance_number;
                    if ($orders->where($name, true)->count() > 0) {
                        $summery[$name] = [
                            'match_type' => $name,
                            'number_of_ticket' =>  $orders->where($name, true)->count(),
                            'prize_per_winner' => $type->prize,
                            'tickets' => $orders->where($name, true)->pluck('id')->implode(','),
                            'total_amount' => ($orders->where($name, true)->count() * $type->prize)
                        ];
                    }
                } else {
                    if ($orders->where($type->name, true)->count() > 0) {
                        $summery[$type->name] = [
                            'match_type' => $type->name,
                            'number_of_ticket' =>  $orders->where($type->name, true)->count(),
                            'prize_per_winner' => $type->prize,
                            'tickets' => $orders->where($type->name, true)->pluck('id')->implode(','),
                            'total_amount' => ($orders->where($type->name, true)->count() * $type->prize)
                        ];
                    }
                }
            }

            return [
                'total_prize' => array_sum(array_column($summery, 'total_amount')),
                'tickets' => $orders->pluck('selected_numbers')->toArray()
            ];
        }
    }

    public function checkAndClaimAvailability(Order $order): bool
    {
        $ticket_buy_time = $order->created_at;

        $product = Product::find($order->product_id);
        if ($product->draw_type === 'daily') {
            $startOfToday = Carbon::today()->startOfDay();
            $endOfToday   = Carbon::today()->endOfDay();

            if ($ticket_buy_time->between($startOfToday, $endOfToday)) {
                return false;
            }
        }
        else if ($product->draw_type === 'hourly') {
            $startOfHour = Carbon::now()->startOfHour();
            $endOfHour   = Carbon::now()->endOfHour();

            if ($ticket_buy_time->between($startOfHour, $endOfHour)) {
                return false;
            }
        }
        else if($product->draw_type === 'once'){
            $draw_times = json_decode($product->draw_time, true);
            $ticketTime = Carbon::parse($ticket_buy_time);
            $next_time = null;

            foreach ($draw_times as $time) {
                $timeCarbon = Carbon::createFromFormat('H:i', $time)
                    ->setDate($ticketTime->year, $ticketTime->month, $ticketTime->day);

                if ($timeCarbon->greaterThan($ticketTime)) {
                    if (!$next_time || $timeCarbon->lessThan($next_time)) {
                        $next_time = $timeCarbon;
                    }
                }
            }

            if (!$next_time) {
                $next_time = Carbon::createFromFormat('H:i', $draw_times[0])
                    ->setDate($ticketTime->year, $ticketTime->month, $ticketTime->day)
                    ->addDay();
            }

            $next_time = $next_time->subSecond();

            if (Carbon::now()->lt($next_time)) {
                return false;
            }
        }

        return true;
    }
}
