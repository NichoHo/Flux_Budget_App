<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\RecurringBill; // Added
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon; // Added

class BudgetController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $endOfMonth = Carbon::now()->endOfMonth();
        
        // 1. Get all budgets for the user
        $budgets = Budget::where('user_id', $userId)->get();
        
        // 2. Calculate actual spending for THIS MONTH per category
        $spending = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->selectRaw('category, SUM(amount) as total_spent')
            ->groupBy('category')
            ->pluck('total_spent', 'category');

        // 3. Calculate Upcoming Recurring Bills for THIS MONTH
        // We fetch active expense bills and project them to the end of the month
        $recurringBills = RecurringBill::where('user_id', $userId)
            ->where('is_active', true)
            ->where('type', 'expense')
            ->get();

        $recurringForecast = [];

        foreach ($recurringBills as $bill) {
            // Parse the next payment date
            $nextDate = Carbon::parse($bill->next_payment_date);

            // Loop to catch multiple occurrences (e.g. weekly bills) within this month
            // We loop while the date is still in the current month (and not in the past relative to the month start, though query handles that)
            // Note: We only care if it falls within the *remaining* part of the month or just 'in this month' generally?
            // Usually a budget is for the whole month.
            // If the bill was already paid (Transaction created), next_payment_date is in the future.
            // If next_payment_date is still in THIS month, it's a pending expense.
            
            $tempDate = $nextDate->copy();
            
            while ($tempDate->lte($endOfMonth)) {
                // Ensure we are looking at dates strictly in the current month/year 
                // (Though the loop condition $tempDate->lte($endOfMonth) mostly handles it, 
                // we ensure we don't pick up old overdue ones unless you want them to count)
                if ($tempDate->month == $currentMonth && $tempDate->year == $currentYear) {
                    if (!isset($recurringForecast[$bill->category])) {
                        $recurringForecast[$bill->category] = 0;
                    }
                    $recurringForecast[$bill->category] += $bill->amount;
                }

                // Advance date based on frequency
                if ($bill->frequency === 'weekly') {
                    $tempDate->addWeek();
                } elseif ($bill->frequency === 'monthly') {
                    $tempDate->addMonth();
                } elseif ($bill->frequency === 'yearly') {
                    $tempDate->addYear();
                } else {
                    break; 
                }
            }
        }

        // 4. Merge data for the view
        $budgetData = $budgets->map(function ($budget) use ($spending, $recurringForecast) {
            $spent = $spending[$budget->category] ?? 0;
            $recurring = $recurringForecast[$budget->category] ?? 0;
            
            $totalUsed = $spent + $recurring;
            $percentage = $budget->amount > 0 ? ($totalUsed / $budget->amount) * 100 : 0;
            
            return (object) [
                'id' => $budget->id,
                'category' => $budget->category,
                'budget_limit' => $budget->amount,
                'spent' => $spent,
                'recurring' => $recurring, // Passed to view
                'total_used' => $totalUsed,
                'remaining' => $budget->amount - $totalUsed,
                'percentage' => min($percentage, 100),
                'is_over_budget' => $totalUsed > $budget->amount
            ];
        });

        // Get currency settings
        $currentCurrency = session('currency', 'IDR');
        $exchangeRate = $this->getExchangeRate();

        return view('budget.index', compact('budgetData', 'currentCurrency', 'exchangeRate'));
    }

    public function create()
    {
        $userId = Auth::id();
        
        // Get categories that don't have a budget yet
        $existingCategories = Budget::where('user_id', $userId)->pluck('category')->toArray();
        $allCategories = ['Food', 'Shopping', 'Transportation', 'Entertainment', 'Bills & Utilities', 'Healthcare', 'Education', 'Travel', 'Other'];
        $availableCategories = array_diff($allCategories, $existingCategories);

        $currentCurrency = session('currency', 'IDR');
        $exchangeRate = $this->getExchangeRate();

        return view('budget.create', compact('availableCategories', 'currentCurrency', 'exchangeRate'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $exists = Budget::where('user_id', Auth::id())
                       ->where('category', $request->category)
                       ->exists();
        
        if ($exists) {
            // UPDATED: Use localized error message
            return back()->withErrors(['category' => __('budget_error_exists')])->withInput();
        }

        $amount = $request->amount;
        $currentCurrency = session('currency', 'IDR');
        
        if ($currentCurrency === 'USD') {
            $exchangeRate = $this->getExchangeRate();
            $amount = $amount * $exchangeRate['rate'];
        }

        Budget::create([
            'user_id' => Auth::id(),
            'category' => $request->category,
            'amount' => $amount
        ]);

        // UPDATED: Use localized success message
        return redirect()->route('budget.index')->with('success', __('budget_success_created'));
    }

    public function edit(Budget $budget)
    {
        if ($budget->user_id !== Auth::id()) {
            abort(403);
        }

        $userId = Auth::id();

        // Calculate current spending for this category
        $spent = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->where('category', $budget->category)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $currentCurrency = session('currency', 'IDR');
        $exchangeRate = $this->getExchangeRate();

        return view('budget.edit', compact('budget', 'spent', 'currentCurrency', 'exchangeRate'));
    }

    public function update(Request $request, Budget $budget)
    {
        if ($budget->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $amount = $request->amount;
        $currentCurrency = session('currency', 'IDR');
        
        if ($currentCurrency === 'USD') {
            $exchangeRate = $this->getExchangeRate();
            $amount = $amount * $exchangeRate['rate'];
        }

        $budget->update(['amount' => $amount]);

        // UPDATED: Use localized success message
        return redirect()->route('budget.index')->with('success', __('budget_success_updated'));
    }

    public function destroy(Budget $budget)
    {
        if ($budget->user_id !== Auth::id()) {
            abort(403);
        }
        
        $budget->delete();
        
        // UPDATED: Use localized success message
        return redirect()->route('budget.index')->with('success', __('budget_success_deleted'));
    }

    private function getExchangeRate()
    {
        try {
            $response = Http::timeout(3)->get('https://api.exchangerate-api.com/v4/latest/USD');
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'rate' => $data['rates']['IDR'] ?? 16000,
                    'is_live' => true
                ];
            }
        } catch (\Exception $e) {}
        
        return ['rate' => 16000, 'is_live' => false];
    }
}