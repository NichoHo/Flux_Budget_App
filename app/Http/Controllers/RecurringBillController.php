<?php

namespace App\Http\Controllers;

use App\Models\RecurringBill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class RecurringBillController extends Controller
{
    public function index()
    {
        $bills = RecurringBill::where('user_id', Auth::id())
            ->orderBy('next_payment_date', 'asc')
            ->get();
            
        $currentCurrency = session('currency', 'IDR');
        $exchangeRate = $this->getExchangeRate();

        return view('recurring.index', compact('bills', 'currentCurrency', 'exchangeRate'));
    }

    public function create()
    {
        $currentCurrency = session('currency', 'IDR');
        return view('recurring.create', compact('currentCurrency'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'frequency' => 'required|in:weekly,monthly,yearly',
            'type' => 'required|in:income,expense',
            'category' => 'nullable|string',
            'start_date' => 'required|date',
        ]);

        $amount = $request->amount;
        // Convert USD input to IDR storage if needed
        if (session('currency') === 'USD') {
            $rates = $this->getExchangeRate();
            $amount = $amount * $rates['rate'];
        }

        RecurringBill::create([
            'user_id' => Auth::id(),
            'description' => $request->description,
            'amount' => $amount, 
            'type' => $request->type,
            'category' => $request->category,
            'frequency' => $request->frequency,
            'start_date' => $request->start_date,
            'next_payment_date' => $request->start_date, 
            'is_active' => true
        ]);

        return redirect()->route('recurring.index')->with('success', 'Recurring bill created!');
    }

    public function edit(RecurringBill $recurringBill)
    {
        if ($recurringBill->user_id !== Auth::id()) abort(403);
        
        $currentCurrency = session('currency', 'IDR');
        $exchangeRate = $this->getExchangeRate();
        
        $recurringBill->display_amount = ($currentCurrency === 'USD') 
            ? $recurringBill->amount / $exchangeRate['rate'] 
            : $recurringBill->amount;

        return view('recurring.edit', compact('recurringBill', 'currentCurrency'));
    }

    public function update(Request $request, RecurringBill $recurringBill)
    {
        if ($recurringBill->user_id !== Auth::id()) abort(403);

        $request->validate([
            'description' => 'required|string',
            'amount' => 'required|numeric',
            'frequency' => 'required|in:weekly,monthly,yearly',
            'category' => 'nullable|string',
        ]);

        $amount = $request->amount;
        if (session('currency') === 'USD') {
            $rates = $this->getExchangeRate();
            $amount = $amount * $rates['rate'];
        }

        // Updating this ONLY changes the rule for the NEXT generation.
        // Past transactions are safe in the 'transactions' table.
        $recurringBill->update([
            'description' => $request->description,
            'amount' => $amount,
            'category' => $request->category,
            'frequency' => $request->frequency,
        ]);

        return redirect()->route('recurring.index')->with('success', 'Future bills updated.');
    }

    public function destroy(RecurringBill $recurringBill)
    {
        if ($recurringBill->user_id !== Auth::id()) abort(403);
        $recurringBill->delete();
        return redirect()->route('recurring.index')->with('success', 'Recurring bill stopped.');
    }

    private function getExchangeRate()
    {
        // Simple reuse of your existing logic
        try {
            $response = Http::timeout(3)->get('https://api.exchangerate-api.com/v4/latest/USD');
            if ($response->successful()) return ['rate' => $response->json()['rates']['IDR'] ?? 16000];
        } catch (\Exception $e) {}
        return ['rate' => 16000];
    }
}