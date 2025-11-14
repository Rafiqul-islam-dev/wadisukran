<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function getBanner()
    {
        $banners = Banner::get();

        if ($banners->isEmpty()) {
            return response()->json([
                'message' => 'No banner found',
            ], 404);
        }

        // $banners->transform(function ($banner) {
        //     $path = $banner->getRawOriginal('image_url'); // raw DB path
        //     $banner->image_url = url('storage/' . ltrim($path, '/'));
        //     return $banner;
        // });

        return response()->json([
            'message' => 'Banner retrieved successfully',
            'data' => $banners,
        ], 200);
    }
}
