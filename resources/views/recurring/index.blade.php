@extends('app')

@section('title', __('recurring_title') . ' - Flux')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/recurring.css') }}">
@endsection

@section('content')

<div class="recurring-header">
    <div class="header-content">
        <h1>{{ __('recurring_title') }}</h1>
        <p>{{ __('recurring_subtitle') }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('currency.switch', $currentCurrency == 'USD' ? 'IDR' : 'USD') }}" class="btn-secondary-custom">
            <i class="fas fa-coins"></i> 
            <span>{{ $currentCurrency == 'USD' ? 'USD ($)' : 'IDR (Rp)' }}</span>
        </a>
        
        <a href="{{ route('recurring.create') }}" class="btn-primary-custom">
            <i class="fas fa-plus"></i>
            <span>{{ __('recurring_add_btn') }}</span>
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert-success">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

<div class="recurring-stats">
    <div class="stat-card stat-income">
        <div class="stat-icon">
            <i class="fas fa-arrow-down"></i>
        </div>
        <div class="stat-content">
            <p class="stat-label">{{ __('recurring_monthly_income') }}</p>
            <p class="stat-value">
                @php
                    $monthlyIncome = $bills->where('type', 'income')->where('frequency', 'monthly')->sum('amount');
                    $weeklyIncome = $bills->where('type', 'income')->where('frequency', 'weekly')->sum('amount') * 4.33;
                    $yearlyIncome = $bills->where('type', 'income')->where('frequency', 'yearly')->sum('amount') / 12;
                    $totalIncome = $monthlyIncome + $weeklyIncome + $yearlyIncome;
                @endphp
                @if($currentCurrency == 'IDR')
                    Rp {{ number_format($totalIncome, 0, ',', '.') }}
                @else
                    $ {{ number_format($totalIncome / $exchangeRate['rate'], 2, '.', ',') }}
                @endif
            </p>
        </div>
    </div>
    
    <div class="stat-card stat-expense">
        <div class="stat-icon">
            <i class="fas fa-arrow-up"></i>
        </div>
        <div class="stat-content">
            <p class="stat-label">{{ __('recurring_monthly_expenses') }}</p>
            <p class="stat-value">
                @php
                    $monthlyExpense = $bills->where('type', 'expense')->where('frequency', 'monthly')->sum('amount');
                    $weeklyExpense = $bills->where('type', 'expense')->where('frequency', 'weekly')->sum('amount') * 4.33;
                    $yearlyExpense = $bills->where('type', 'expense')->where('frequency', 'yearly')->sum('amount') / 12;
                    $totalExpense = $monthlyExpense + $weeklyExpense + $yearlyExpense;
                @endphp
                @if($currentCurrency == 'IDR')
                    Rp {{ number_format($totalExpense, 0, ',', '.') }}
                @else
                    $ {{ number_format($totalExpense / $exchangeRate['rate'], 2, '.', ',') }}
                @endif
            </p>
        </div>
    </div>
    
    <div class="stat-card stat-total">
        <div class="stat-icon">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-content">
            <p class="stat-label">{{ __('recurring_active_bills') }}</p>
            <p class="stat-value">{{ $bills->where('is_active', true)->count() }}</p>
        </div>
    </div>
</div>

<div class="recurring-section">
    @if($bills->count() > 0)
    <div class="table-responsive">
        <table class="recurring-table">
            <thead>
                <tr>
                    <th width="20%">{{ __('table_description') }}</th>
                    <th width="15%">{{ __('table_category') }}</th>
                    <th width="10%">{{ __('recurring_table_frequency') }}</th>
                    <th width="15%">{{ __('recurring_table_next_due') }}</th>
                    <th width="10%">{{ __('recurring_table_due_in') }}</th>
                    <th width="15%">{{ __('table_amount') }}</th>
                    <th width="10%">{{ __('recurring_table_status') }}</th>
                    <th width="5%"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($bills as $bill)
                <tr class="{{ !$bill->is_active ? 'inactive-row' : '' }}">
                    <td class="td-desc">
                        <div class="bill-description">
                            <span class="fw-bold">{{ $bill->description }}</span>
                        </div>
                    </td>
                    <td class="td-cat">
                        @if($bill->category)
                            <span class="badge badge-category">
                                {{ __($bill->category) != $bill->category ? __($bill->category) : $bill->category }}
                            </span>
                        @else
                            <span class="text-secondary opacity-50">-</span>
                        @endif
                    </td>
                    <td class="td-freq">
                        <span class="frequency-badge frequency-{{ $bill->frequency }}">
                            <i class="fas fa-{{ $bill->frequency == 'weekly' ? 'calendar-week' : ($bill->frequency == 'monthly' ? 'calendar-alt' : 'calendar') }}"></i>
                            {{ ucfirst($bill->frequency) }}
                        </span>
                    </td>
                    <td class="td-next">
                        <span class="fw-bold date">
                            {{ \Carbon\Carbon::parse($bill->next_payment_date)->format('M d, Y') }}
                        </span>
                    </td>
                    <td class="td-due">
                        @php
                            $daysUntil = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($bill->next_payment_date), false);
                            $daysUntil = (int)$daysUntil;
                        @endphp
                        @if($daysUntil < 0)
                            <span class="days-badge overdue">{{ __('recurring_status_overdue') }}</span>
                        @elseif($daysUntil == 0)
                            <span class="days-badge today">{{ __('recurring_status_today') }}</span>
                        @elseif($daysUntil <= 7)
                            <span class="days-badge soon">{{ $daysUntil }}d</span>
                        @else
                            <span class="days-badge normal">{{ $daysUntil }}d</span>
                        @endif
                    </td>
                    <td class="td-amount {{ $bill->type == 'income' ? 'text-success' : 'text-danger' }} fw-bold">
                        @if($currentCurrency == 'IDR')
                            Rp {{ number_format($bill->amount, 0, ',', '.') }}
                        @else
                            $ {{ number_format($bill->amount / $exchangeRate['rate'], 2, '.', ',') }}
                        @endif
                    </td>
                    <td class="td-status">
                        @if($bill->is_active)
                            <span class="fw-bold text-success" style="font-size: 0.85rem;">{{ __('recurring_status_active') }}</span>
                        @else
                            <span class="fw-bold text-secondary" style="font-size: 0.85rem;">{{ __('recurring_status_inactive') }}</span>
                        @endif
                    </td>
                    <td class="td-actions text-end">
                        <div class="action-buttons">
                            <a href="{{ route('recurring.edit', $bill->id) }}" class="btn-edit" title="{{ __('index_btn_edit') }}">
                                <i class="fas fa-pencil-alt"></i>
                            </a>
                            <form action="{{ route('recurring.destroy', $bill->id) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-delete" onclick="return confirm('{{ __('recurring_delete_confirm') }}')" title="{{ __('index_btn_delete') }}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="no-data">
        <i class="fas fa-calendar-times"></i>
        <p>{{ __('recurring_no_data') }}</p>
    </div>
    @endif
</div>

@if($bills->count() > 0)
<div class="text-center mt-4">
    <a href="{{ route('dashboard') }}" class="btn-secondary-custom" style="width: 100%; justify-content: center;">
        <i class="fas fa-arrow-left"></i>
        <span>{{ __('index_btn_back') }}</span>
    </a>
</div>
@endif

@endsection