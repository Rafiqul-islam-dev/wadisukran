<?php

if (! function_exists('static_asset')) {
    function static_asset($path): ?string
    {
        if(env('APP_ENV') === 'production'){
            return asset('public/'.$path);
        }
        else{
            return asset($path);
        }
    }
}
