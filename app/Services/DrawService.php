<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Win;
use Carbon\Carbon;

class DrawService
{
    public function createWin(array $data): string
    {
        if (is_array($data['products'])) {
            foreach ($data['products'] as $product) {
                $last_draw = Win::where('product_id', $product['id'])->latest('to_time')->first();
                $maxDrawNumber = Win::max('draw_number');

                if ($last_draw) {
                    if (Carbon::parse($data['to_time'])->gt(Carbon::parse($last_draw->to_time))) {
                        Win::create([
                            'product_id' => $product['id'],
                            'from_time' => Carbon::parse($last_draw->to_time)->addSecond(),
                            'to_time' => $data['to_time'],
                            'draw_number' => ($maxDrawNumber + 1),
                            'draw_time' => Carbon::parse($data['to_time'])->addMinute(),
                            'win_number' => $product['numbers']
                        ]);
                    }
                } else {
                    $productRaw = Product::find($product['id']);
                    if ($productRaw->draw_type == 'daily') {
                        $fromTime = Carbon::parse($data['to_time'])->subDay()->addSecond();
                    } else {
                        $fromTime = Carbon::parse($data['to_time'])->subHour()->addSecond();
                    }
                    Win::create([
                        'product_id' => $product['id'],
                        'from_time' => $fromTime,
                        'to_time' => $data['to_time'],
                        'draw_number' => ($maxDrawNumber + 1),
                        'draw_time' => Carbon::parse($data['to_time'])->addMinute(),
                        'win_number' => $product['numbers']
                    ]);
                }
            }
        }
        return 'Win created successfully';
    }
}
