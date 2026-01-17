<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WinnerReportController extends Controller
{
    
    public function winnerReport()
    {
        return Inertia::render('Report/WinnerReport');
    }

}
