@extends('app')

@section('title', __('budget_meta_title_index'))

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/budget.css') }}">
@endsection

@section('content')

<div class="budget-header">
    <div class="header-content">
        <h1>{{ __('budget_management_title') }}</h1>
        <p>{{ __('budget_management_subtitle') }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('currency.switch', $currentCurrency == 'USD' ? 'IDR' : 'USD') }}" class="btn-secondary-custom">
            <i class="fas fa-coins"></i> 
            <span>{{ $currentCurrency == 'USD' ? 'USD ($)' : 'IDR (Rp)' }}</span>
        </a>
        
        <a href="{{ route('budget.create') }}" class="btn-primary-custom">
            <i class="fas fa-plus"></i>
            <span>{{ __('budget_btn_add') }}</span>
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert-success">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

<div class="budget-section">
    @if($budgetData->count() > 0)
    @php
        $totalBudget = $budgetData->sum('budget_limit');
        $totalSpent = $budgetData->sum('total_used');
        $totalActual = $budgetData->sum('spent');
        $totalRecurring = $budgetData->sum('recurring');
    @endphp
    
    <div class="budget-overview">
        <div class="overview-card">
            <div class="overview-icon total">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="overview-content">
                <p class="overview-label">{{ __('budget_overview_total') }}</p>
                <h3 class="overview-value">
                    @if($currentCurrency == 'IDR')
                        Rp {{ number_format($totalBudget, 0, ',', '.') }}
                    @else
                        $ {{ number_format($totalBudget / $exchangeRate['rate'], 2, '.', ',') }}
                    @endif
                </h3>
            </div>
        </div>

        <div class="overview-card">
            <div class="overview-icon spent">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="overview-content">
                <p class="overview-label">{{ __('budget_overview_used_committed') }}</p>
                <h3 class="overview-value text-danger">
                    @if($currentCurrency == 'IDR')
                        Rp {{ number_format($totalSpent, 0, ',', '.') }}
                    @else
                        $ {{ number_format($totalSpent / $exchangeRate['rate'], 2, '.', ',') }}
                    @endif
                </h3>
                <small class="text-muted" style="font-size: 0.8em; opacity: 0.8;">
                    ({{ __('budget_overview_act') }}: 
                    @if($currentCurrency == 'IDR')
                         {{ number_format($totalActual / 1000, 0) }}k
                    @else
                         {{ number_format($totalActual / $exchangeRate['rate'], 0) }}
                    @endif
                    + {{ __('budget_overview_rec') }}: 
                    @if($currentCurrency == 'IDR')
                         {{ number_format($totalRecurring / 1000, 0) }}k
                    @else
                         {{ number_format($totalRecurring / $exchangeRate['rate'], 0) }}
                    @endif
                    )
                </small>
            </div>
        </div>

        <div class="overview-card">
            <div class="overview-icon remaining">
                <i class="fas fa-piggy-bank"></i>
            </div>
            <div class="overview-content">
                <p class="overview-label">{{ __('budget_overview_remaining') }}</p>
                <h3 class="overview-value {{ ($totalBudget - $totalSpent) >= 0 ? 'text-success' : 'text-danger' }}">
                    @if($currentCurrency == 'IDR')
                        Rp {{ number_format($totalBudget - $totalSpent, 0, ',', '.') }}
                    @else
                        $ {{ number_format(($totalBudget - $totalSpent) / $exchangeRate['rate'], 2, '.', ',') }}
                    @endif
                </h3>
            </div>
        </div>
    </div>

    <div class="budget-grid">
        @foreach($budgetData as $budget)
        @php
            if ($budget->percentage >= 100 || $budget->is_over_budget) {
                $statusClass = 'over-budget';
                $statusText = __('budget_status_over');
            } elseif ($budget->percentage >= 80) {
                $statusClass = 'warning';
                $statusText = __('budget_status_warning');
            } else {
                $statusClass = 'on-track';
                $statusText = __('budget_status_on_track');
            }
        @endphp
        
        <div class="budget-card">
            <div class="budget-card-header">
                <div class="budget-category">
                    <div class="category-icon">
                        <i class="fas fa-{{ 
                            $budget->category == 'Food' ? 'utensils' : 
                            ($budget->category == 'Shopping' ? 'shopping-bag' : 
                            ($budget->category == 'Transportation' ? 'car' : 
                            ($budget->category == 'Entertainment' ? 'film' : 
                            ($budget->category == 'Bills and Utilities' ? 'file-invoice-dollar' : 
                            ($budget->category == 'Healthcare' ? 'heartbeat' : 
                            ($budget->category == 'Education' ? 'graduation-cap' : 
                            ($budget->category == 'Travel' ? 'plane' : 'tag'))))))) 
                        }}"></i>
                    </div>
                    <h3>{{ __($budget->category) }}</h3>
                </div>
                <span class="budget-status {{ $statusClass }}">{{ $statusText }}</span>
            </div>

            <div class="budget-amounts">
                <div class="amount-row">
                    <span class="amount-label">{{ __('budget_card_budget') }}</span>
                    <span class="amount-value">
                        @if($currentCurrency == 'IDR')
                            Rp {{ number_format($budget->budget_limit, 0, ',', '.') }}
                        @else
                            $ {{ number_format($budget->budget_limit / $exchangeRate['rate'], 2, '.', ',') }}
                        @endif
                    </span>
                </div>
                
                <div class="amount-row">
                    <span class="amount-label">{{ __('budget_card_spent') }}</span>
                    <span class="amount-value text-danger">
                        @if($currentCurrency == 'IDR')
                            Rp {{ number_format($budget->spent, 0, ',', '.') }}
                        @else
                            $ {{ number_format($budget->spent / $exchangeRate['rate'], 2, '.', ',') }}
                        @endif
                    </span>
                </div>

                @if($budget->recurring > 0)
                <div class="amount-row" style="font-size: 0.9em; opacity: 0.8;">
                    <span class="amount-label"><i class="fas fa-calendar-alt"></i> {{ __('budget_card_upcoming') }}</span>
                    <span class="amount-value text-warning">
                        @if($currentCurrency == 'IDR')
                            + Rp {{ number_format($budget->recurring, 0, ',', '.') }}
                        @else
                            + $ {{ number_format($budget->recurring / $exchangeRate['rate'], 2, '.', ',') }}
                        @endif
                    </span>
                </div>
                @endif

                <div class="amount-row pt-2 mt-2">
                    <span class="amount-label font-weight-bold">{{ __('budget_card_remaining') }}</span>
                    <span class="amount-value font-weight-bold {{ $budget->remaining >= 0 ? 'text-success' : 'text-danger' }}">
                        @if($currentCurrency == 'IDR')
                            Rp {{ number_format($budget->remaining, 0, ',', '.') }}
                        @else
                            $ {{ number_format($budget->remaining / $exchangeRate['rate'], 2, '.', ',') }}
                        @endif
                    </span>
                </div>
            </div>

            <div class="budget-progress">
                <div class="progress-bar">
                    <div class="progress-fill {{ $statusClass }}" style="width: {{ $budget->percentage }}%"></div>
                </div>
                <span class="progress-text">{{ number_format($budget->percentage, 1) }}{{ __('budget_progress_used') }}</span>
            </div>

            <div class="budget-actions">
                <a href="{{ route('budget.edit', $budget->id) }}" class="btn-edit">
                    <i class="fas fa-pencil-alt"></i> {{ __('budget_btn_edit') }}
                </a>
                <form action="{{ route('budget.destroy', $budget->id) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-delete" onclick="return confirm('{{ __('budget_delete_confirm') }}')">
                        <i class="fas fa-trash-alt"></i> {{ __('budget_btn_delete') }}
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    @else
    <div class="no-data">
        <i class="fas fa-chart-pie"></i>
        <p>{{ __('budget_no_data') }}</p>
    </div>
    @endif
</div>

<div class="text-center mt-4">
    <a href="{{ route('dashboard') }}" class="btn-secondary-custom" style="width: 100%; justify-content: center;">
        <i class="fas fa-arrow-left"></i>
        <span>{{ __('budget_btn_back') }}</span>
    </a>
</div>

@endsection