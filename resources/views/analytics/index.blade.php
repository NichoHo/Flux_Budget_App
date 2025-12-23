@extends('app')

@section('title', __('analytics_title') . ' - Flux')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/analytics.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')

<div class="analytics-header">
    <div class="header-content">
        <h1>{{ __('analytics_title') }}</h1>
        <p>{{ __('analytics_subtitle') }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('currency.switch', $currentCurrency == 'USD' ? 'IDR' : 'USD') }}" class="btn-secondary-custom">
            <i class="fas fa-coins"></i> 
            <span>{{ $currentCurrency == 'USD' ? 'USD ($)' : 'IDR (Rp)' }}</span>
        </a>
        
        <a href="{{ route('analytics.export') }}" class="btn-primary-custom">
            <i class="fas fa-file-download"></i>
            <span>{{ __('analytics_export_report') }}</span>
        </a>
    </div>
</div>

@if($totalIncome == 0 && $totalExpense == 0)
<div class="empty-state">
    <div class="empty-icon">
        <i class="fas fa-chart-pie"></i>
    </div>
    <h3 class="empty-title">{{ __('analytics_no_data_title') }}</h3>
    <p class="empty-message">{{ __('analytics_no_data_message') }}</p>
    <a href="{{ route('transactions.create') }}" class="btn-primary-custom">
        <i class="fas fa-plus"></i>
        <span>{{ __('analytics_add_first_transaction') }}</span>
    </a>
</div>
@else
<div class="stats-cards">
    <div class="stat-card">
        <div class="stat-icon income">
            <i class="fas fa-arrow-up"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">
                @if($currentCurrency == 'IDR')
                    Rp {{ number_format($totalIncome, 0, ',', '.') }}
                @else
                    $ {{ number_format($totalIncome, 2, '.', ',') }}
                @endif
            </div>
            <div class="stat-label">{{ __('analytics_total_income') }}</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon expense">
            <i class="fas fa-arrow-down"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">
                @if($currentCurrency == 'IDR')
                    Rp {{ number_format($totalExpense, 0, ',', '.') }}
                @else
                    $ {{ number_format($totalExpense, 2, '.', ',') }}
                @endif
            </div>
            <div class="stat-label">{{ __('analytics_total_expense') }}</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon balance">
            <i class="fas fa-wallet"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">
                @if($currentCurrency == 'IDR')
                    Rp {{ number_format($netBalance, 0, ',', '.') }}
                @else
                    $ {{ number_format($netBalance, 2, '.', ',') }}
                @endif
            </div>
            <div class="stat-label">{{ __('analytics_net_balance') }}</div>
            @if($totalIncome > 0)
            <div class="stat-trend {{ $netBalance >= 0 ? 'trend-up' : 'trend-down' }}">
                <i class="fas fa-{{ $netBalance >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                <span>{{ round(($netBalance / $totalIncome) * 100, 1) }}% {{ __('analytics_of_income') }}</span>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="charts-section">
    <h2 style="margin-bottom: 1rem;">{{ __('analytics_visualizations') }}</h2>
    <p style="color: var(--text-secondary-light); font-size: 0.875rem; margin-bottom: 1rem;">
        {{ __('analytics_visualizations_subtitle') }}
    </p>
    
    <div class="charts-grid">
        <div class="chart-container">
            <div class="chart-header">
                <h3 class="chart-title">{{ __('analytics_monthly_trend') }}</h3>
            </div>
            <div class="chart-wrapper">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>
        
        <div class="chart-container">
            <div class="chart-header">
                <h3 class="chart-title">{{ __('analytics_expense_categories') }}</h3>
            </div>
            <div class="chart-wrapper">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="insights-grid">
    <div class="insight-card">
        <div class="insight-header">
            <div class="insight-icon category">
                <i class="fas fa-tags"></i>
            </div>
            <h3 class="insight-title">{{ __('analytics_expense_breakdown') }}</h3>
        </div>
        <ul class="insight-list">
            @forelse($expenseCategories as $category)
            <li class="insight-item">
                <span class="insight-label">{{ $category['category'] }}</span>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span class="insight-value">
                        @if($currentCurrency == 'IDR')
                            Rp {{ number_format($category['amount'], 0, ',', '.') }}
                        @else
                            $ {{ number_format($category['amount'], 2, '.', ',') }}
                        @endif
                    </span>
                    <span class="insight-percentage">{{ $category['percentage'] }}%</span>
                </div>
            </li>
            @empty
            <li class="insight-item">
                <span class="insight-label">{{ __('analytics_no_categories') }}</span>
                <span class="insight-value text-muted">-</span>
            </li>
            @endforelse
        </ul>
    </div>
    
    <div class="insight-card">
        <div class="insight-header">
            <div class="insight-icon sources">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <h3 class="insight-title">{{ __('analytics_income_sources') }}</h3>
        </div>
        <ul class="insight-list">
            @forelse($incomeSources as $source)
            <li class="insight-item">
                <span class="insight-label">{{ $source['source'] }}</span>
                <span class="insight-value">
                    @if($currentCurrency == 'IDR')
                        Rp {{ number_format($source['amount'], 0, ',', '.') }}
                    @else
                        $ {{ number_format($source['amount'], 2, '.', ',') }}
                    @endif
                </span>
            </li>
            @empty
            <li class="insight-item">
                <span class="insight-label">{{ __('analytics_no_income_sources') }}</span>
                <span class="insight-value text-muted">-</span>
            </li>
            @endforelse
        </ul>
    </div>
    
    <div class="insight-card">
        <div class="insight-header">
            <div class="insight-icon" style="background-color: #e0e7ff; color: #4338ca;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <h3 class="insight-title">{{ __('analytics_recurring_obligations') }}</h3>
        </div>
        <div style="margin-bottom: 1rem;">
            <p class="text-muted" style="font-size: 0.9em; margin-bottom: 0.5rem;">{{ __('analytics_est_monthly_fixed_cost') }}</p>
            <h2 style="font-size: 1.5rem; color: #1f2937;">
                @if($currentCurrency == 'IDR')
                    Rp {{ number_format($totalRecurringMonthly, 0, ',', '.') }}
                @else
                    $ {{ number_format($totalRecurringMonthly, 2, '.', ',') }}
                @endif
            </h2>
        </div>
        <ul class="insight-list">
            <li class="insight-item">
                <span class="insight-label">{{ __('analytics_yearly_projection') }}</span>
                <span class="insight-value">
                    @if($currentCurrency == 'IDR')
                        Rp {{ number_format($totalRecurringMonthly * 12, 0, ',', '.') }}
                    @else
                        $ {{ number_format($totalRecurringMonthly * 12, 2, '.', ',') }}
                    @endif
                </span>
            </li>
            @if(count($monthlyData) > 0)
            <li class="insight-item">
                <span class="insight-label">{{ __('analytics_discretionary_avg') }}</span>
                @php 
                    $avgIncome = collect($monthlyData)->avg('income'); 
                @endphp
                <span class="insight-value text-success">
                    @if($currentCurrency == 'IDR')
                        Rp {{ number_format(max(0, $avgIncome - $totalRecurringMonthly), 0, ',', '.') }}
                    @else
                        $ {{ number_format(max(0, $avgIncome - $totalRecurringMonthly), 2, '.', ',') }}
                    @endif
                </span>
            </li>
            @endif
        </ul>
    </div>

    <div class="insight-card">
        <div class="insight-header">
            <div class="insight-icon trends">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3 class="insight-title">{{ __('analytics_spending_trends') }}</h3>
        </div>
        <ul class="insight-list">
            <li class="insight-item">
                <span class="insight-label">{{ __('analytics_current_month') }}</span>
                <span class="insight-value">
                    @if($currentCurrency == 'IDR')
                        Rp {{ number_format($spendingTrends['current_month'], 0, ',', '.') }}
                    @else
                        $ {{ number_format($spendingTrends['current_month'], 2, '.', ',') }}
                    @endif
                </span>
            </li>
            <li class="insight-item">
                <span class="insight-label">{{ __('analytics_last_month') }}</span>
                <span class="insight-value">
                    @if($currentCurrency == 'IDR')
                        Rp {{ number_format($spendingTrends['last_month'], 0, ',', '.') }}
                    @else
                        $ {{ number_format($spendingTrends['last_month'], 2, '.', ',') }}
                    @endif
                </span>
            </li>
            <li class="insight-item">
                <span class="insight-label">{{ __('analytics_month_over_month') }}</span>
                <span class="insight-value {{ $spendingTrends['month_over_month_change'] >= 0 ? 'trend-up' : 'trend-down' }}">
                    <i class="fas fa-{{ $spendingTrends['month_over_month_change'] >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                    {{ abs($spendingTrends['month_over_month_change']) }}%
                </span>
            </li>
        </ul>
    </div>
</div>

<div class="recommendations-section">
    <h2 style="margin-bottom: 1rem;">{{ __('analytics_recommendations') }}</h2>
    <p style="color: var(--text-secondary-light); font-size: 0.875rem; margin-bottom: 1rem;">
        {{ __('analytics_recommendations_subtitle') }}
    </p>
    
    <div class="recommendations-grid">
        @foreach($recommendations as $rec)
        <div class="recommendation-card {{ $rec['type'] }}">
            <div class="recommendation-header">
                <i class="fas fa-{{ $rec['icon'] }} recommendation-icon {{ $rec['type'] }}"></i>
                <h4 class="recommendation-title">{{ $rec['title'] }}</h4>
            </div>
            <p class="recommendation-message">{{ $rec['message'] }}</p>
            <div class="recommendation-action">
                <i class="fas fa-lightbulb"></i>
                <span>{{ $rec['action'] }}</span>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    @if($totalIncome > 0 || $totalExpense > 0)
    // Monthly Trend Chart
    const monthlyCtx = document.getElementById('monthlyTrendChart').getContext('2d');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: @json($chartData['monthlyLabels']),
                datasets: [
                    {
                        label: '{{ __("analytics_income") }}',
                        data: @json($chartData['monthlyIncome']),
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: '{{ __("analytics_expense") }}',
                        data: @json($chartData['monthlyExpense']),
                        borderColor: '#EF4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('id-ID', {
                                        style: 'currency',
                                        currency: '{{ $currentCurrency }}',
                                        minimumFractionDigits: {{ $currentCurrency == 'IDR' ? 0 : 2 }}
                                    }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('id-ID', {
                                    style: 'currency',
                                    currency: '{{ $currentCurrency }}',
                                    minimumFractionDigits: {{ $currentCurrency == 'IDR' ? 0 : 2 }}
                                }).format(value);
                            }
                        }
                    }
                }
            }
        });
    }

    // Category Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    if (categoryCtx) {
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: @json($chartData['categoryLabels']),
                datasets: [{
                    data: @json($chartData['categoryAmounts']),
                    backgroundColor: [
                        '#8B5CF6', '#F59E0B', '#10B981', '#3B82F6', 
                        '#EC4899', '#6B7280', '#EF4444', '#F97316',
                        '#0EA5E9', '#84CC16'
                    ],
                    borderWidth: 2,
                    borderColor: 'var(--surface-light)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${new Intl.NumberFormat('id-ID', {
                                    style: 'currency',
                                    currency: '{{ $currentCurrency }}',
                                    minimumFractionDigits: {{ $currentCurrency == 'IDR' ? 0 : 2 }}
                                }).format(value)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Income Sources Chart
    const incomeSourcesCtx = document.getElementById('incomeSourcesChart')?.getContext('2d');
    if (incomeSourcesCtx) {
        new Chart(incomeSourcesCtx, {
            type: 'pie',
            data: {
                labels: @json($chartData['incomeSourceLabels']),
                datasets: [{
                    data: @json($chartData['incomeSourceAmounts']),
                    backgroundColor: [
                        '#10B981', '#34D399', '#065F46', '#059669', '#047857'
                    ],
                    borderWidth: 2,
                    borderColor: 'var(--surface-light)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${new Intl.NumberFormat('id-ID', {
                                    style: 'currency',
                                    currency: '{{ $currentCurrency }}',
                                    minimumFractionDigits: {{ $currentCurrency == 'IDR' ? 0 : 2 }}
                                }).format(value)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    @endif
});
</script>
@endsection