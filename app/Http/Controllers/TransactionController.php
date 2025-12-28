<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class TransactionController extends Controller
{
    // READ: Show list of transactions
    public function index(Request $request)
    {
        $query = Transaction::where('user_id', Auth::id());
        
        // 1. Search
        if ($request->filled('search')) {
            $query->where('description', 'like', "%{$request->search}%");
        }
        
        // 2. Date Range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // 3. Type
        if ($request->filled('type') && $request->type != 'all') {
            $query->where('type', $request->type);
        }

        // 4. Category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $currentCurrency = session('currency', 'IDR');
        $exchangeRate = $this->getExchangeRate();
        $rate = $exchangeRate['rate'];

        // 5. Amount Filters
        if ($request->filled('min_amount')) {
            $min = $request->min_amount;
            if ($currentCurrency === 'USD') $min *= $rate;
            $query->where('amount', '>=', $min);
        }

        if ($request->filled('max_amount')) {
            $max = $request->max_amount;
            if ($currentCurrency === 'USD') $max *= $rate;
            $query->where('amount', '<=', $max);
        }
        
        // --- SUMMARY CALCULATION ---
        $summaryQuery = clone $query;
        $totalCount = $summaryQuery->count();
        $totalSum = $summaryQuery->sum('amount');
        // ---------------------------

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $transactions = $query->paginate(10);
        
        $categories = [
            'Income' => ['Salary', 'Freelance', 'Investment', 'Business', 'Other Income'],
            'Expense' => ['Food', 'Shopping', 'Transportation', 'Entertainment', 'Bills and Utilities', 'Healthcare', 'Education', 'Travel', 'Other']
        ];
        
        return view('transactions.index', compact('transactions', 'currentCurrency', 'exchangeRate', 'categories', 'totalCount', 'totalSum'));
    }

    // CREATE: Show the form
    public function create()
    {
        $currentCurrency = session('currency', 'IDR');
        $exchangeRate = $this->getExchangeRate();
        return view('transactions.create', compact('currentCurrency', 'exchangeRate'));
    }

    // STORE: Save new transaction to DB
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:income,expense',
            'description' => 'required|string',
            'category' => 'nullable|string|max:255',
            'receipt_image' => 'nullable|image|max:2048'
        ]);

        // Validate category matches type
        if (!$this->validateCategoryBasedOnType($request->type, $request->category)) {
            return redirect()->back()
                ->withErrors(['category' => 'Selected category does not match transaction type.'])
                ->withInput();
        }

        // Get current currency from session
        $currentCurrency = session('currency', 'IDR');
        $amount = $request->amount;
        
        // Convert to IDR if input is in USD
        if ($currentCurrency === 'USD') {
            $exchangeRate = $this->getExchangeRate();
            $amount = $amount * $exchangeRate['rate'];
        }
        
        // Always store in IDR
        $amount = round($amount, 2);

        $path = null;
        
        // FILE UPLOAD LOGIC 
        if ($request->hasFile('receipt_image')) {
            $path = $request->file('receipt_image')->store('receipts', 'public');
        }

        $transaction = Transaction::create([
            'user_id' => Auth::id(),
            'amount' => $amount, // Stored as IDR
            'type' => $request->type,
            'description' => $request->description,
            'category' => $request->category,
            'receipt_image_url' => $path
        ]);

        // --- UPDATED ALERT LOGIC ---
        // Pass alert as a separate session key for better UI handling
        $alert = $this->checkBudgetStatus($transaction);

        return redirect()->route('transactions.index')
            ->with('success', __('create_success_message'))
            ->with('budget_alert', $alert); // Pass separately
    }

    // DELETE: Remove transaction
    public function destroy(Transaction $transaction)
    {
        if ($transaction->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($transaction->receipt_image_url) {
            Storage::disk('public')->delete($transaction->receipt_image_url);
        }

        $transaction->delete();
        return redirect()->route('transactions.index');
    }

    // EDIT: Show the form with existing data
    public function edit(Transaction $transaction)
    {
        if ($transaction->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $currentCurrency = session('currency', 'IDR'); 
        $exchangeRate = $this->getExchangeRate(); 
        
        if ($currentCurrency === 'IDR') {
            $transaction->display_amount = $transaction->amount; 
        } else {
            $transaction->display_amount = $transaction->amount / $exchangeRate['rate'];
        }

        return view('transactions.edit', compact('transaction', 'currentCurrency', 'exchangeRate'));
    }

    // UPDATE: Save the changes to the DB
    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:income,expense',
            'description' => 'required|string',
            'category' => 'nullable|string|max:255',
            'receipt_image' => 'nullable|image|max:2048'
        ]);

        if (!$this->validateCategoryBasedOnType($request->type, $request->category)) {
            return redirect()->back()
                ->withErrors(['category' => 'Selected category does not match transaction type.'])
                ->withInput();
        }

        $currentCurrency = session('currency', 'IDR');
        $amount = $request->amount;
        
        if ($currentCurrency === 'USD') {
            $exchangeRate = $this->getExchangeRate();
            $amount = $amount * $exchangeRate['rate'];
        }
        
        $amount = round($amount, 2);

        if ($request->hasFile('receipt_image')) {
            if ($transaction->receipt_image_url) {
                Storage::disk('public')->delete($transaction->receipt_image_url);
            }
            $path = $request->file('receipt_image')->store('receipts', 'public');
            $transaction->receipt_image_url = $path;
        }

        $transaction->category = $request->category;
        $transaction->amount = $amount; 
        $transaction->type = $request->type;
        $transaction->description = $request->description;
        
        $transaction->save();

        // --- UPDATED ALERT LOGIC ---
        $alert = $this->checkBudgetStatus($transaction);

        return redirect()->route('transactions.index')
            ->with('success', __('edit_success_message'))
            ->with('budget_alert', $alert); // Pass separately
    }

    // --- HELPER METHOD FOR ALERTS ---
    private function checkBudgetStatus(Transaction $transaction)
    {
        // Only check expenses with a category
        if ($transaction->type !== 'expense' || !$transaction->category) {
            return null;
        }

        // Only check for current month transactions
        if (!$transaction->created_at->isCurrentMonth()) {
            return null;
        }

        $userId = Auth::id();

        // Find the budget for this category
        $budget = Budget::where('user_id', $userId)
            ->where('category', $transaction->category)
            ->first();

        // If no budget exists, no alert is needed
        if (!$budget || $budget->amount <= 0) {
            return null;
        }

        // Calculate total actual spent for this category in the current month
        $totalSpent = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->where('category', $transaction->category)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('amount');

        // Calculate percentage used
        $percentage = ($totalSpent / $budget->amount) * 100;

        // Return localized alert messages based on thresholds
        if ($percentage >= 100) {
            return __('budget_alert_exceeded', [
                'category' => $transaction->category,
                'percentage' => round($percentage)
            ]);
        } elseif ($percentage >= 90) { // Keep the 90% threshold
            return __('budget_alert_warning', [
                'category' => $transaction->category,
                'percentage' => round($percentage)
            ]);
        }

        return null;
    }

    private function getExchangeRate()
    {
        try {
            $response = Http::timeout(3)->get('https://api.exchangerate-api.com/v4/latest/USD');
            if ($response->successful()) {
                $data = $response->json();
                return ['rate' => $data['rates']['IDR'] ?? 16000, 'is_live' => true];
            }
        } catch (\Exception $e) {}
        return ['rate' => 16000, 'is_live' => false];
    }

    private function validateCategoryBasedOnType($type, $category)
    {
        $incomeCategories = ['Salary', 'Freelance', 'Investment', 'Business', 'Other Income'];
        $expenseCategories = ['Food', 'Shopping', 'Transportation', 'Entertainment', 'Bills and Utilities', 'Healthcare', 'Education', 'Travel', 'Other'];
        
        if ($category) {
            if ($type === 'income' && !in_array($category, $incomeCategories)) {
                return false;
            }
            if ($type === 'expense' && !in_array($category, $expenseCategories)) {
                return false;
            }
        }
        
        return true;
    }

    public function calendar(Request $request)
    {
        $monthString = $request->get('month', now()->format('Y-m'));
        $currentMonth = \Carbon\Carbon::parse($monthString . '-01');
        
        $prevMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();
        
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        
        $transactions = Transaction::where('user_id', Auth::id())
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->orderBy('created_at', 'asc')
            ->get();
        
        $transactionsByDate = [];
        foreach ($transactions as $transaction) {
            $date = $transaction->created_at->format('Y-m-d');
            if (!isset($transactionsByDate[$date])) {
                $transactionsByDate[$date] = [];
            }
            $transactionsByDate[$date][] = $transaction;
        }
        
        $daysInMonth = $currentMonth->daysInMonth;
        $startDayOfWeek = $currentMonth->copy()->startOfMonth()->dayOfWeek;
        
        $currentCurrency = session('currency', 'IDR');
        $exchangeRate = $this->getExchangeRate();
        
        return view('transactions.calendar', compact(
            'currentMonth',
            'prevMonth',
            'nextMonth',
            'daysInMonth',
            'startDayOfWeek',
            'transactionsByDate',
            'currentCurrency',
            'exchangeRate'
        ));
    }
}