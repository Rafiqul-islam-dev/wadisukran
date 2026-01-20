<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderTicket;
use App\Models\Product;
use App\Models\Win;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckWinController extends Controller
{
    public function index()
    {
        $wins = OrderTicket::get();
        return Inertia::render('CheckWin/Index');
    }

    public function check_win(Request $request)
    {
        $request->validate([
            'invoice_no' => 'required|string|exists:orders,invoice_no'
        ]);

        $invoice = Order::where('invoice_no', $request->invoice_no)->first();

        $wins = Win::where('product_id', $invoice->product_id)
            // ->whereRaw('? BETWEEN CONCAT(from_win_date, " ", from_win_time) AND CONCAT(to_win_date, " ", to_win_time)', [$invoice->created_at])
            ->get();

        return $wins;


        // $invoice = Order::where('invoice_no', $request->invoice_no)->first();
        // $product = Product::find($invoice->product_id);

        // $orders = OrderTicket::query()
        //     ->where('order_id', $invoice->id)
        //     ->get()
        //     ->map(function ($order) use ($numbersStraight, $numbersSorted, $len, $types, $product, $numbersChance) {
        //         $data = ['id' => $order->id, 'selected_numbers' => $order->selected_numbers];
        //         $isStraightWinner = false;
        //         $isRumbleWinner = false;
        //         $isChanceWinner = false;
        //         $ticketTypes   = is_array($order->selected_play_types)
        //             ? $order->selected_play_types
        //             : (array) $order->selected_play_types;

        //         $ticketNumbers = collect($order->selected_numbers)->values();
        //         if ($product->prize_type === 'bet') {
        //             foreach ($product->prizes->whereIn('name', ['Straight', 'Rumble']) as $type) {
        //                 if ($type->name === 'Straight' & in_array('Straight', $ticketTypes, true)) {
        //                     $isStraightWinner =
        //                         $ticketNumbers->count() === $len &&
        //                         $ticketNumbers->all() === $numbersStraight->all();
        //                     $data[$type->name] = $isStraightWinner;
        //                 } else if ($type->name === 'Rumble' && in_array('Rumble', $ticketTypes, true)) {
        //                     if ($isStraightWinner == false) {
        //                         $isRumbleWinner =
        //                             $ticketNumbers->count() === $len &&
        //                             $ticketNumbers->sort()->values()->all() === $numbersSorted->all();
        //                     }
        //                     $data[$type->name] = $isRumbleWinner;
        //                 }
        //             }
        //             if (in_array('Chance', $ticketTypes, true)) {
        //                 $matchCount = $ticketNumbers->reverse()
        //                     ->values()
        //                     ->zip($numbersChance)
        //                     ->takeWhile(fn($pair) => (string)$pair[0] === (string)$pair[1])
        //                     ->count();

        //                 $chancePrizes = $product->prizes
        //                     ->where('name', 'Chance')
        //                     ->sortByDesc('chance_number')
        //                     ->values();


        //                 foreach ($chancePrizes as $chanceType) {
        //                     $key = $chanceType->name . ' ' . $chanceType->chance_number;
        //                     $data[$key] = false;

        //                     if ($isStraightWinner || $isRumbleWinner || $isChanceWinner) {
        //                         continue;
        //                     }

        //                     if ($matchCount == (int) $chanceType->chance_number) {
        //                         $data[$key] = true;
        //                         $isChanceWinner = true;
        //                     }
        //                 }
        //             }
        //         } else {
        //             $matchCount = $ticketNumbers->intersect($numbersStraight)->count();
        //             $numberPrizes = $product->prizes
        //                 ->sortByDesc('name');

        //             $isNumberWinner = false;

        //             foreach ($numberPrizes as $prize) {
        //                 $key = 'Number ' . (int) $prize->name;
        //                 $data[$key] = false;

        //                 if ($isNumberWinner) continue;

        //                 if ($matchCount === (int) $prize->name) {
        //                     $data[$key] = true;
        //                     $isNumberWinner = true;
        //                 }
        //             }
        //         }
        //         return $data;
        //     });

        // foreach ($types as $type) {
        //     if (is_numeric($type->name)) {
        //         $name = 'Number ' . $type->name;
        //         if ($orders->where($name, true)->count() > 0) {
        //             $summery[$name] = [
        //                 'match_type' => $name,
        //                 'winners' =>  $orders->where($name, true)->count(),
        //                 'prize_per_winner' => $type->prize,
        //                 'tickets' => $orders->where($name, true)->values(),
        //                 'total_amount' => ($orders->where($name, true)->count() * $type->prize)
        //             ];
        //         }
        //     } else if ($type->name === 'Chance') {
        //         $name = $type->name . ' ' . $type->chance_number;
        //         if ($orders->where($name, true)->count() > 0) {
        //             $summery[$name] = [
        //                 'match_type' => $name,
        //                 'winners' =>  $orders->where($name, true)->count(),
        //                 'prize_per_winner' => $type->prize,
        //                 'tickets' => $orders->where($name, true)->values(),
        //                 'total_amount' => ($orders->where($name, true)->count() * $type->prize)
        //             ];
        //         }
        //     } else {
        //         if ($orders->where($type->name, true)->count() > 0) {
        //             $summery[$type->name] = [
        //                 'match_type' => $type->name,
        //                 'winners' =>  $orders->where($type->name, true)->count(),
        //                 'prize_per_winner' => $type->prize,
        //                 'tickets' => $orders->where($type->name, true)->values(),
        //                 'total_amount' => ($orders->where($type->name, true)->count() * $type->prize)
        //             ];
        //         }
        //     }
        // }
        return back();
    }
}
