<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\In;
use Inertia\Inertia;

class DrawController extends Controller
{
    public function index()
    {
        return Inertia::render('Product/Draws/Index');
    }
}
