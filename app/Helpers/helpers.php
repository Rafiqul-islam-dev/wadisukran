<?php

use App\Models\CompannySetting;

if (! function_exists('static_asset')) {
    function static_asset($path): ?string
    {
        if (env('APP_ENV') === 'production') {
            return asset('public/' . $path);
        } else {
            return asset($path);
        }
    }
}

if (! function_exists('company_setting')) {
    function company_setting()
    {
        return cache()->remember(
            "company_setting",
            now()->addMinutes(30),
            fn() =>
            CompannySetting::firstOrFail() ?? null
        );
    }
}
