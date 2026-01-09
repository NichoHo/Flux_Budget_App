<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Show Register Form
    public function showRegister() {
        return view('auth.register');
    }

    // Handle Registration
    public function register(Request $request) {
        // 1. Validate Input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'preferred_language' => 'required|in:en,id' 
        ]);

        // 2. Create User (Hash the password!)
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Encryption
            'preferred_language' => $request->preferred_language
        ]);

        // 3. Login automatically and redirect
        Auth::login($user);
        $request->session()->regenerate();
        
        // Set locale immediately based on preference
        session(['locale' => $user->preferred_language]);

        return redirect()->route('dashboard');
    }

    // Show Login Form
    public function showLogin() {
        return view('auth.login');
    }

    // Handle Login
    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            session(['locale' => Auth::user()->preferred_language]);

            // CHANGED: Redirect to dashboard
            return redirect()->intended('dashboard');
        }

        return back()->withErrors(['email' => 'Invalid credentials.']);
    }

    // Handle Logout
    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}