<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckWinController extends Controller
{
    public function index(){
        return Inertia::render('CheckWin/Index');
    }
}
