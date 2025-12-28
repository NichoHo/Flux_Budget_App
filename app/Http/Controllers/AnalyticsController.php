<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\RecurringBill; // Added
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $currentCurrency = session('currency', 'IDR');
        
        // Get all user transactions
        $transactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate basic stats (in IDR from database)
        $totalIncomeIDR = $transactions->where('type', 'income')->sum('amount');
        $totalExpenseIDR = $transactions->where('type', 'expense')->sum('amount');
        $netBalanceIDR = $totalIncomeIDR - $totalExpenseIDR;
        
        // --- NEW: Calculate Recurring Bills (Normalized to Monthly) ---
        $recurringBills = RecurringBill::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();
            
        $totalRecurringMonthlyIDR = 0;
        foreach ($recurringBills as $bill) {
            $amount = $bill->amount;
            
            // Normalize frequency to monthly cost
            if ($bill->type == 'expense') {
                switch ($bill->frequency) {
                    case 'weekly':
                        $amount *= 4.33; // Average weeks in a month
                        break;
                    case 'monthly':
                        // already monthly
                        break;
                    case 'yearly':
                        $amount /= 12;
                        break;
                }
                $totalRecurringMonthlyIDR += $amount;
            }
        }
        // -----------------------------------------------------------

        // Convert to display currency if needed
        $exchangeRate = $this->getExchangeRate();
        
        if ($currentCurrency === 'USD') {
            $totalIncome = $totalIncomeIDR / $exchangeRate['rate'];
            $totalExpense = $totalExpenseIDR / $exchangeRate['rate'];
            $netBalance = $netBalanceIDR / $exchangeRate['rate'];
            $totalRecurringMonthly = $totalRecurringMonthlyIDR / $exchangeRate['rate'];
        } else {
            $totalIncome = $totalIncomeIDR;
            $totalExpense = $totalExpenseIDR;
            $netBalance = $netBalanceIDR;
            $totalRecurringMonthly = $totalRecurringMonthlyIDR;
        }
        
        // Monthly breakdown for the last 6 months
        $monthlyData = $this->getMonthlyBreakdown($transactions, $currentCurrency, $exchangeRate);
        
        // Weekly spending pattern
        $weeklyPattern = $this->getWeeklyPattern($transactions, $currentCurrency, $exchangeRate);
        
        // Top expense categories (from actual category column)
        $expenseCategories = $this->analyzeExpenseCategories($transactions, $currentCurrency, $exchangeRate);
        
        // Income sources analysis (from actual category column)
        $incomeSources = $this->analyzeIncomeSources($transactions, $currentCurrency, $exchangeRate);
        
        // Spending trends
        $spendingTrends = $this->calculateSpendingTrends($transactions, $currentCurrency, $exchangeRate);
        
        // Category-based monthly trends
        $categoryMonthlyTrends = $this->getCategoryMonthlyTrends($transactions, $currentCurrency, $exchangeRate);
        
        // Personalized recommendations (Updated with Recurring info)
        $recommendations = $this->generateRecommendations($transactions, $totalIncomeIDR, $totalExpenseIDR, $totalRecurringMonthlyIDR);
        
        // Prepare data for charts
        $chartData = [
            'monthlyLabels' => $monthlyData->pluck('month')->toArray(),
            'monthlyIncome' => $monthlyData->pluck('income')->toArray(),
            'monthlyExpense' => $monthlyData->pluck('expense')->toArray(),
            'weeklyLabels' => $weeklyPattern->pluck('day')->toArray(),
            'weeklyAmounts' => $weeklyPattern->pluck('amount')->toArray(),
            'categoryLabels' => $expenseCategories->pluck('category')->toArray(),
            'categoryAmounts' => $expenseCategories->pluck('amount')->toArray(),
            'incomeSourceLabels' => $incomeSources->pluck('source')->toArray(),
            'incomeSourceAmounts' => $incomeSources->pluck('amount')->toArray(),
        ];

        return view('analytics.index', [
            'currentCurrency' => $currentCurrency,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netBalance' => $netBalance,
            'totalRecurringMonthly' => $totalRecurringMonthly, // Passed to view
            'monthlyData' => $monthlyData,
            'weeklyPattern' => $weeklyPattern,
            'expenseCategories' => $expenseCategories,
            'incomeSources' => $incomeSources,
            'spendingTrends' => $spendingTrends,
            'categoryMonthlyTrends' => $categoryMonthlyTrends,
            'recommendations' => $recommendations,
            'chartData' => $chartData,
            'exchangeRate' => $exchangeRate,
        ]);
    }

    private function getMonthlyBreakdown($transactions, $currency, $exchangeRate)
    {
        $months = collect();
        
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthName = $month->format('M Y');
            
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            
            $monthTransactions = $transactions->filter(function ($transaction) use ($monthStart, $monthEnd) {
                return $transaction->created_at->between($monthStart, $monthEnd);
            });
            
            $monthIncome = $monthTransactions->where('type', 'income')->sum('amount');
            $monthExpense = $monthTransactions->where('type', 'expense')->sum('amount');
            
            // Convert to display currency if needed
            if ($currency === 'USD') {
                $monthIncome = $monthIncome / $exchangeRate['rate'];
                $monthExpense = $monthExpense / $exchangeRate['rate'];
            }
            
            $months->push([
                'month' => $monthName,
                'income' => $monthIncome,
                'expense' => $monthExpense,
                'savings' => $monthIncome - $monthExpense,
            ]);
        }
        
        return $months;
    }

    private function getWeeklyPattern($transactions, $currency, $exchangeRate)
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $pattern = collect();
        
        foreach ($days as $day) {
            $dayTransactions = $transactions->filter(function ($transaction) use ($day) {
                return $transaction->created_at->format('l') === $day && $transaction->type === 'expense';
            });
            
            $dayAmount = $dayTransactions->sum('amount');
            
            // Convert to display currency if needed
            if ($currency === 'USD') {
                $dayAmount = $dayAmount / $exchangeRate['rate'];
            }
            
            $pattern->push([
                'day' => $day,
                'amount' => $dayAmount,
                'count' => $dayTransactions->count(),
            ]);
        }
        
        return $pattern;
    }

    private function analyzeExpenseCategories($transactions, $currency, $exchangeRate)
    {
        $expenseTransactions = $transactions->where('type', 'expense');
        $groupedCategories = $expenseTransactions->groupBy('category');
        $categoryTotals = collect();
        
        foreach ($groupedCategories as $category => $categoryTransactions) {
            $categoryAmount = $categoryTransactions->sum('amount');
            
            if ($currency === 'USD') {
                $categoryAmount = $categoryAmount / $exchangeRate['rate'];
            }
            
            $displayCategory = $category ?: __('analytics_uncategorized');
            
            $categoryTotals->push([
                'category' => $displayCategory,
                'amount' => $categoryAmount,
                'count' => $categoryTransactions->count(),
                'percentage' => 0,
            ]);
        }
        
        $totalExpense = $expenseTransactions->sum('amount');
        if ($currency === 'USD') {
            $totalExpense = $totalExpense / $exchangeRate['rate'];
        }
        
        return $categoryTotals->map(function ($cat) use ($totalExpense) {
            $cat['percentage'] = $totalExpense > 0 ? round(($cat['amount'] / $totalExpense) * 100, 1) : 0;
            return $cat;
        })->sortByDesc('amount');
    }

    private function analyzeIncomeSources($transactions, $currency, $exchangeRate)
    {
        $incomeTransactions = $transactions->where('type', 'income');
        $groupedSources = $incomeTransactions->groupBy('category');
        $sourceTotals = collect();
        
        foreach ($groupedSources as $source => $sourceTransactions) {
            $sourceAmount = $sourceTransactions->sum('amount');
            
            if ($currency === 'USD') {
                $sourceAmount = $sourceAmount / $exchangeRate['rate'];
            }
            
            $displaySource = $source ?: __('analytics_uncategorized');
            
            $sourceTotals->push([
                'source' => $displaySource,
                'amount' => $sourceAmount,
                'count' => $sourceTransactions->count(),
            ]);
        }
        
        return $sourceTotals->sortByDesc('amount');
    }

    private function getCategoryMonthlyTrends($transactions, $currency, $exchangeRate)
    {
        $months = collect();
        $topCategories = $this->analyzeExpenseCategories($transactions, $currency, $exchangeRate)
            ->take(5)
            ->pluck('category');
        
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthName = $month->format('M Y');
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            
            $monthTransactions = $transactions->filter(function ($transaction) use ($monthStart, $monthEnd) {
                return $transaction->created_at->between($monthStart, $monthEnd) && $transaction->type === 'expense';
            });
            
            $monthData = ['month' => $monthName];
            
            foreach ($topCategories as $category) {
                $categoryAmount = $monthTransactions
                    ->where('category', $category === 'Uncategorized' ? null : $category)
                    ->sum('amount');
                
                if ($currency === 'USD') {
                    $categoryAmount = $categoryAmount / $exchangeRate['rate'];
                }
                
                $monthData[$category] = $categoryAmount;
            }
            
            $months->push($monthData);
        }
        
        return ['categories' => $topCategories, 'months' => $months];
    }

    private function calculateSpendingTrends($transactions, $currency, $exchangeRate)
    {
        $lastMonth = Carbon::now()->subMonth();
        $twoMonthsAgo = Carbon::now()->subMonths(2);
        
        $currentMonthExpense = $transactions
            ->where('type', 'expense')
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('amount');
            
        $lastMonthExpense = $transactions
            ->where('type', 'expense')
            ->whereBetween('created_at', [$lastMonth->startOfMonth(), $lastMonth->endOfMonth()])
            ->sum('amount');
            
        $twoMonthsAgoExpense = $transactions
            ->where('type', 'expense')
            ->whereBetween('created_at', [$twoMonthsAgo->startOfMonth(), $twoMonthsAgo->endOfMonth()])
            ->sum('amount');
        
        if ($currency === 'USD') {
            $currentMonthExpense = $currentMonthExpense / $exchangeRate['rate'];
            $lastMonthExpense = $lastMonthExpense / $exchangeRate['rate'];
            $twoMonthsAgoExpense = $twoMonthsAgoExpense / $exchangeRate['rate'];
        }
        
        $monthOverMonthChange = $lastMonthExpense > 0 
            ? (($currentMonthExpense - $lastMonthExpense) / $lastMonthExpense) * 100 
            : 0;
        
        return [
            'current_month' => $currentMonthExpense,
            'last_month' => $lastMonthExpense,
            'two_months_ago' => $twoMonthsAgoExpense,
            'month_over_month_change' => round($monthOverMonthChange, 1),
            'trend' => $monthOverMonthChange > 0 ? 'up' : ($monthOverMonthChange < 0 ? 'down' : 'stable'),
        ];
    }

    private function generateRecommendations($transactions, $totalIncomeIDR, $totalExpenseIDR, $totalRecurringMonthlyIDR = 0)
    {
        $recommendations = collect();
        
        // 1. Calculate savings rate
        $savingsRate = $totalIncomeIDR > 0 ? (($totalIncomeIDR - $totalExpenseIDR) / $totalIncomeIDR) * 100 : 0;
        
        if ($savingsRate < 10) {
            $recommendations->push([
                'type' => 'warning',
                'title' => __('analytics_low_savings'),
                'message' => __('analytics_low_savings_msg'),
                'action' => __('analytics_low_savings_action'),
                'icon' => 'piggy-bank',
            ]);
        }
        
        // 2. NEW Check: Fixed Costs Ratio
        // We calculate roughly annual income vs annual recurring for a fair comparison, or just monthly average
        // Simplification: Assume totalIncomeIDR is "all time" or "year to date"? 
        // Actually $transactions->sum is ALL time. This is a bit flawed in the original code if the user has years of data.
        // For accurate ratio, let's use the Monthly Average Income from the last 3 months.
        
        $recentIncome = $transactions
            ->where('type', 'income')
            ->where('created_at', '>=', Carbon::now()->subMonths(3))
            ->sum('amount');
        $avgMonthlyIncome = $recentIncome / 3; // Rough estimate
        
        if ($avgMonthlyIncome > 0) {
            $fixedCostRatio = ($totalRecurringMonthlyIDR / $avgMonthlyIncome) * 100;
            
            if ($fixedCostRatio > 50) {
                $recommendations->push([
                    'type' => 'danger',
                    'title' => __('analytics_high_fixed_costs'), // UPDATED
                    'message' => __('analytics_high_fixed_costs_msg', ['ratio' => round($fixedCostRatio)]), // UPDATED
                    'action' => __('analytics_high_fixed_costs_action'), // UPDATED
                    'icon' => 'receipt',
                ]);
            }
        }

        // 3. Check for large expense transactions
        $largeExpenses = $transactions->where('type', 'expense')
            ->sortByDesc('amount')
            ->take(3);
            
        if ($largeExpenses->isNotEmpty() && $largeExpenses->first()->amount > ($totalIncomeIDR * 0.3)) {
            $recommendations->push([
                'type' => 'danger',
                'title' => __('analytics_large_expense'),
                'message' => __('analytics_large_expense_msg'),
                'action' => __('analytics_large_expense_action'),
                'icon' => 'exclamation-triangle',
            ]);
        }
        
        // 4. Check for frequent small expenses
        $smallExpensesCount = $transactions->where('type', 'expense')
            ->where('amount', '<', 100000) 
            ->count();
            
        if ($smallExpensesCount > 20) {
            $recommendations->push([
                'type' => 'info',
                'title' => __('analytics_small_expenses'),
                'message' => __('analytics_small_expenses_msg'),
                'action' => __('analytics_small_expenses_action'),
                'icon' => 'coins',
            ]);
        }
        
        // 5. Check income diversity
        $incomeCategoriesCount = $transactions->where('type', 'income')
            ->whereNotNull('category')
            ->groupBy('category')
            ->count();
            
        if ($incomeCategoriesCount < 2) {
            $recommendations->push([
                'type' => 'success',
                'title' => __('analytics_diversify_income'),
                'message' => __('analytics_diversify_income_msg'),
                'action' => __('analytics_diversify_income_action'),
                'icon' => 'chart-line',
            ]);
        }
        
        // If no recommendations, add general ones
        if ($recommendations->isEmpty()) {
            $recommendations->push([
                'type' => 'success',
                'title' => __('analytics_good_health'),
                'message' => __('analytics_good_health_msg'),
                'action' => __('analytics_good_health_action'),
                'icon' => 'check-circle',
            ]);
        }
        
        return $recommendations;
    }
    
    private function getExchangeRate()
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(3)->get('https://api.exchangerate-api.com/v4/latest/USD');
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

    public function export(Request $request)
    {
        $user = Auth::user();
        $currentCurrency = session('currency', 'IDR');
        $exchangeRate = $this->getExchangeRate();
        
        $transactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $fileName = 'expense_report_' . date('Y-m-d') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($transactions, $currentCurrency, $exchangeRate) {
            $file = fopen('php://output', 'w');
            
            // UPDATED HEADERS
            fputcsv($file, [
                __('export_date'), 
                __('export_type'), 
                __('export_category'), 
                __('export_description'), 
                __('export_amount') . ' (' . $currentCurrency . ')', 
                __('export_original_amount'), 
                __('export_original_currency')
            ]);

            foreach ($transactions as $transaction) {
                $displayAmount = $transaction->amount;
                if ($currentCurrency === 'USD') {
                    $displayAmount = $transaction->amount / $exchangeRate['rate'];
                }

                fputcsv($file, [
                    $transaction->created_at->format('Y-m-d H:i'), 
                    ucfirst($transaction->type),                  
                    $transaction->category ?? __('analytics_uncategorized'), // UPDATED
                    $transaction->description,                     
                    number_format($displayAmount, 2, '.', ''),    
                    $transaction->amount,                          
                    'IDR'                                         
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}