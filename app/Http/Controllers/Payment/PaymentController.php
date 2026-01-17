<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    
    public function allPayment()
    {
       return Inertia::render('Payment/Allpayment');
    }

}
