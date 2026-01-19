<?php

namespace App\Services;

use App\Models\Win;

class DrawService
{
    public function createWin(array $data): string
    {
        if (is_array($data['products'])) {
            foreach ($data['products'] as $product) {
                $existingDraw = Win::where('product_id', $product['id'])
                    ->where('win_date', $data['date'])
                    ->where('win_time', $data['time'])
                    ->first();

                if ($existingDraw) {
                    $drawNumber = $existingDraw->draw_number;
                } else {
                    $maxDrawNumber = Win::where('product_id', $product['id'])->max('draw_number') ?? 0;
                    $drawNumber = $maxDrawNumber + 1;
                }
                Win::updateOrCreate([
                    'product_id'  => $product['id'],
                    'win_date'    => $data['date'],
                    'win_time'    => $data['time'],
                    'draw_number' => $drawNumber
                ], [
                    'win_number' => $product['numbers']
                ]);
            }
        }
        return 'Win created successfully';
    }
}
