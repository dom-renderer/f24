<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function index() 
    {
        return redirect()->route('orders.dashboard');
    }

    public function logout()
    {
        \Auth::logout();
        session()->flush();
        
        return redirect()->route('login.show');
    }

}