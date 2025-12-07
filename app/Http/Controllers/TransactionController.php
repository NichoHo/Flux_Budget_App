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
        // Start with base query for current user
        $query = Transaction::where('user_id', Auth::id());
        
        // Apply search filter
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('description', 'like', "%{$search}%");
        }
        
        // Apply type filter
        if ($request->has('type') && $request->type != 'all') {
            $query->where('type', $request->type);
        }
        
        // Apply date filter
        if ($request->has('date') && $request->date != '') {
            $query->whereDate('created_at', $request->date);
        }
        
        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Get filtered transactions
        $transactions = $query->paginate(10);
        
        // Get current currency and exchange rate
        $currentCurrency = session('currency', 'IDR');
        $exchangeRate = $this->getExchangeRate();
        
        return view('transactions.index', compact('transactions', 'currentCurrency', 'exchangeRate'));
    }

    // CREATE: Show the form
    public function create()
    {
        // Currency is now initialized in app.blade.php, but let's make sure
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
        // Try to get live rate, fallback to default
        try {
            $response = Http::timeout(3)->get('https://api.exchangerate-api.com/v4/latest/USD');
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'rate' => $data['rates']['IDR'] ?? 16000,
                    'is_live' => true
                ];
            }
        } catch (\Exception $e) {
            // Fallback to default
        }
        
        return [
            'rate' => 16000,
            'is_live' => false
        ];
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
}