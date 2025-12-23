<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

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
        
        // --- SUMMARY CALCULATION (New) ---
        $summaryQuery = clone $query;
        $totalCount = $summaryQuery->count();
        $totalSum = $summaryQuery->sum('amount');
        // ---------------------------------

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $transactions = $query->paginate(10);
        
        $categories = [
            'Income' => ['Salary', 'Freelance', 'Investment', 'Business', 'Other Income'],
            'Expense' => ['Food', 'Shopping', 'Transportation', 'Entertainment', 'Bills & Utilities', 'Healthcare', 'Education', 'Travel', 'Other']
        ];
        
        // Pass totalCount and totalSum to view
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

        Transaction::create([
            'user_id' => Auth::id(),
            'amount' => $amount, // Stored as IDR
            'type' => $request->type,
            'description' => $request->description,
            'category' => $request->category,
            'receipt_image_url' => $path
        ]);

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction added successfully!');
    }

    // DELETE: Remove transaction
    public function destroy(Transaction $transaction)
    {
        // AUTHORIZATION: Ensure user owns this transaction before deleting [cite: 9]
        if ($transaction->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Delete the image file if it exists to save space
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

        $currentCurrency = session('currency', 'IDR'); // Changed default from 'USD' to 'IDR'
        $exchangeRate = $this->getExchangeRate(); // Fixed: use $this->getExchangeRate() instead of getExchangeRate()
        
        // Convert amount for display based on current currency
        if ($currentCurrency === 'IDR') {
            $transaction->display_amount = $transaction->amount; // Already stored as IDR
        } else {
            // If displaying in USD, convert from stored IDR to USD
            $transaction->display_amount = $transaction->amount / $exchangeRate['rate'];
        }

        return view('transactions.edit', compact('transaction', 'currentCurrency', 'exchangeRate'));
    }

    // UPDATE: Save the changes to the DB
    public function update(Request $request, Transaction $transaction)
    {
        // DATA ISOLATION Check
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

        // 1. Handle File Upload (If a new file is provided)
        if ($request->hasFile('receipt_image')) {
            // Delete old image if it exists
            if ($transaction->receipt_image_url) {
                Storage::disk('public')->delete($transaction->receipt_image_url);
            }

            // Store new image and update the path
            $path = $request->file('receipt_image')->store('receipts', 'public');
            $transaction->receipt_image_url = $path;
        }

        // 2. Update other text fields (amount is stored as IDR)
        $transaction->category = $request->category;
        $transaction->amount = $amount; // Store as IDR
        $transaction->type = $request->type;
        $transaction->description = $request->description;
        
        // 3. Save
        $transaction->save();

        return redirect()->route('transactions.index')->with('success', 'Transaction updated!');
    }

    // Helper method to get exchange rate
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
        $expenseCategories = ['Food', 'Shopping', 'Transportation', 'Entertainment', 'Bills & Utilities', 'Healthcare', 'Education', 'Travel', 'Other'];
        
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
        // Get month from request or use current month
        $monthString = $request->get('month', now()->format('Y-m'));
        $currentMonth = \Carbon\Carbon::parse($monthString . '-01');
        
        // Get previous and next months for navigation
        $prevMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();
        
        // Get start and end of month
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        
        // Get all transactions for the month
        $transactions = Transaction::where('user_id', Auth::id())
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Group transactions by date
        $transactionsByDate = [];
        foreach ($transactions as $transaction) {
            $date = $transaction->created_at->format('Y-m-d');
            if (!isset($transactionsByDate[$date])) {
                $transactionsByDate[$date] = [];
            }
            $transactionsByDate[$date][] = $transaction;
        }
        
        // Get calendar data
        $daysInMonth = $currentMonth->daysInMonth;
        $startDayOfWeek = $currentMonth->copy()->startOfMonth()->dayOfWeek;
        
        // Get current currency and exchange rate
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