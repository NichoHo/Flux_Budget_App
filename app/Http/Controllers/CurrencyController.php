<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CurrencyController extends Controller
{
    public function switch($currency)
    {
        if (!in_array($currency, ['USD', 'IDR'])) {
            abort(400, 'Invalid currency');
        }
        
        // Set session currency
        Session::put('currency', $currency);
        
        return redirect()->back();
    }
}