<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPrize;
use App\Models\Win;
use Carbon\Carbon;

class ProductService
{
    public function createProduct(array $data): string
    {
        $imagePath = null;

        if (!empty($data['image'])) {
            $image = $data['image'];

            $fileName = str()->slug($data['title']) . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

            $imagePath = $image->storeAs(
                'uploads/products',
                $fileName
            );
        }

        $product = Product::create([
            'title'        => $data['title'],
            'category_id'  => $data['category_id'],
            'price'        => $data['price'],
            'draw_type'    => $data['draw_type'],
            'draw_date'    => $data['draw_date'] ?? null,
            'draw_time'    => $data['draw_time'] ? $data['draw_time'] : null,
            'pick_number'  => $data['pick_number'],
            'type_number'  => $data['type_number'],
            'prize_type'   => $data['prize_type'],
            'image'        => $imagePath,
            'is_active'    => $data['is_active'] ?? false
        ]);

        foreach ($data['prizes'] as $prize) {
            if ($prize['prize'] > 0) {
                ProductPrize::create([
                    'product_id' => $product->id,
                    'type' => $prize['type'],
                    'name' => $prize['name'],
                    'chance_number' => $prize['chance_number'] ?? null,
                    'prize' => $prize['prize']
                ]);
            }
        }

        return 'Product created successfully.';
    }

    function statusChange(Product $product): string
    {
        $product->is_active = $product->is_active ? false : true;
        $product->save();
        return 'Product status changed';
    }

    public function updateProduct(Product $product, array $data): string
    {
        if (!empty($data['image'])) {
            if ($product->image && file_exists(public_path($product->image))) {
                unlink(public_path($product->image));
            }

            $image = $data['image'];
            $fileName = str()->slug($data['title']) . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('uploads/products', $fileName);
            $product->image = $imagePath;
        }

        $product->title       = $data['title'];
        $product->category_id = $data['category_id'];
        $product->price       = $data['price'];
        $product->draw_type   = $data['draw_type'];
        $product->draw_date   = $data['draw_date'] ?? null;
        $product->draw_time   = $data['draw_time'] ?? null;
        $product->pick_number = $data['pick_number'];
        $product->type_number = $data['type_number'];
        $product->prize_type  = $data['prize_type'];
        $product->save();

        $product->prizes()->delete();

        foreach ($data['prizes'] as $prize) {
            if ($prize['prize'] > 0) {
                ProductPrize::create([
                    'product_id' => $product->id,
                    'type'       => $prize['type'],
                    'name'       => $prize['name'],
                    'chance_number'       => $prize['chance_number'],
                    'prize'      => $prize['prize']
                ]);
            }
        }

        return 'Product updated successfully.';
    }
    public function productPermanentDelete($id): string
    {
        $product = Product::withTrashed()->find($id);
        if ($product) {
            if ($product->image && file_exists(public_path($product->image))) {
                unlink(public_path($product->image));
            }
            $product->prizes()->delete();
            $product->forceDelete();

            return 'Product permanently deleted successfully.';
        }
        return 'Product not found.';
    }

    public function checkProductAvailability(Product $product): bool
    {
        if ($product->draw_type === 'daily') {
            $startOfToday = Carbon::today();
            $fifteenMin   = Carbon::today()->addMinute(15);

            $yesterdayStart = Carbon::yesterday()->startOfDay();
            $yesterdayEnd   = Carbon::yesterday()->endOfDay();

            $prev_win = Win::where('product_id', $product->id)
                ->whereBetween('to_time', [$yesterdayStart, $yesterdayEnd])
                ->exists();

            if (!$prev_win && Carbon::now()->between($startOfToday, $fifteenMin)) {
                return false;
            }
        }
        if ($product->draw_type === 'hourly') {
            $startOfHour = Carbon::now()->startOfHour();
            $fifteenMin  = Carbon::now()->startOfHour()->addMinutes(15);

            $prevHourStart = Carbon::now()->subHour()->startOfHour();
            $prevHourEnd   = Carbon::now()->subHour()->endOfHour();

            $prev_win = Win::where('product_id', $product->id)
                ->whereBetween('to_time', [$prevHourStart, $prevHourEnd])
                ->exists();

            if (!$prev_win && Carbon::now()->between($startOfHour, $fifteenMin)) {
                return false;
            }
        }

        return true;
    }

    public function getDrawTimeFromOrder(Order $order): array
    {
        $product = $order->product;
        $draw_date = Carbon::parse($order->created_at)->startOfDay();
        $draw_time = Carbon::parse($order->created_at)->endOfDay();

        if ($product->draw_type === 'hourly') {
            $draw_time   = Carbon::parse($order->created_at)->endOfHour();
        } else if ($product->draw_type === 'once') {
            $draw_times = json_decode($product->draw_time, true);
            $now = Carbon::parse($order->created_at);
            $next_time = null;

            foreach ($draw_times as $time) {
                $timeCarbon = Carbon::createFromFormat('H:i', $time)
                    ->setDate($now->year, $now->month, $now->day);

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
            $draw_time = $next_time->subSecond();
        }

        return [
            'draw_date' => $draw_date,
            'draw_time' => $draw_time
        ];
    }
}
