<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
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
        
        // Convert to display currency if needed
        $exchangeRate = $this->getExchangeRate();
        
        if ($currentCurrency === 'USD') {
            $totalIncome = $totalIncomeIDR / $exchangeRate['rate'];
            $totalExpense = $totalExpenseIDR / $exchangeRate['rate'];
            $netBalance = $netBalanceIDR / $exchangeRate['rate'];
        } else {
            $totalIncome = $totalIncomeIDR;
            $totalExpense = $totalExpenseIDR;
            $netBalance = $netBalanceIDR;
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
        
        // Personalized recommendations
        $recommendations = $this->generateRecommendations($transactions, $totalIncomeIDR, $totalExpenseIDR);
        
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
        // Get all expense transactions with categories
        $expenseTransactions = $transactions->where('type', 'expense');
        
        // Group by category
        $groupedCategories = $expenseTransactions->groupBy('category');
        
        $categoryTotals = collect();
        
        foreach ($groupedCategories as $category => $categoryTransactions) {
            $categoryAmount = $categoryTransactions->sum('amount');
            
            // Convert to display currency if needed
            if ($currency === 'USD') {
                $categoryAmount = $categoryAmount / $exchangeRate['rate'];
            }
            
            // Handle null/empty categories
            $displayCategory = $category ?: 'Uncategorized';
            
            $categoryTotals->push([
                'category' => $displayCategory,
                'amount' => $categoryAmount,
                'count' => $categoryTransactions->count(),
                'percentage' => 0,
            ]);
        }
        
        // Calculate percentages
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
        // Get all income transactions with categories
        $incomeTransactions = $transactions->where('type', 'income');
        
        // Group by category
        $groupedSources = $incomeTransactions->groupBy('category');
        
        $sourceTotals = collect();
        
        foreach ($groupedSources as $source => $sourceTransactions) {
            $sourceAmount = $sourceTransactions->sum('amount');
            
            // Convert to display currency if needed
            if ($currency === 'USD') {
                $sourceAmount = $sourceAmount / $exchangeRate['rate'];
            }
            
            // Handle null/empty categories
            $displaySource = $source ?: 'Uncategorized';
            
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
        
        // Get top 5 expense categories
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
            
            $monthData = [
                'month' => $monthName,
            ];
            
            // Get amount for each top category
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
        
        return [
            'categories' => $topCategories,
            'months' => $months,
        ];
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
        
        // Convert to display currency if needed
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

    private function generateRecommendations($transactions, $totalIncomeIDR, $totalExpenseIDR)
    {
        $recommendations = collect();
        
        // Calculate savings rate
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
        
        // Check for large expense transactions
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
        
        // Check for frequent small expenses
        $smallExpensesCount = $transactions->where('type', 'expense')
            ->where('amount', '<', 100000) // Less than 100k IDR
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
        
        // Check income diversity using actual categories
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
        
        // Check for uncategorized expenses
        $uncategorizedExpenses = $transactions->where('type', 'expense')
            ->where(function($query) {
                $query->whereNull('category')
                      ->orWhere('category', '')
                      ->orWhere('category', 'Uncategorized');
            })
            ->count();
            
        if ($uncategorizedExpenses > 0) {
            $recommendations->push([
                'type' => 'info',
                'title' => __('analytics_uncategorized_expenses'),
                'message' => __('analytics_uncategorized_expenses_msg', ['count' => $uncategorizedExpenses]),
                'action' => __('analytics_uncategorized_expenses_action'),
                'icon' => 'tag',
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
        // Use the same method as TransactionController
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(3)->get('https://api.exchangerate-api.com/v4/latest/USD');
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
}