<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'totalUsers' => User::count(),
            'totalTransactions' => Transaction::count(),
            'recentUsers' => User::latest()->take(5)->get(),
        ]);
    }

    public function users()
    {
        $users = User::paginate(10);
        return view('admin.users', compact('users'));
    }
}