@extends('app')

@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;
    
    $hasAdvancedFilters = request()->anyFilled(['category', 'min_amount', 'max_amount']) || 
                          (request('type') && request('type') !== 'all');
@endphp

@section('title', __('menu_transactions') . ' - Flux')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/transactions.css') }}">
@endsection

@section('content')

<div class="transactions-header">
    <div class="header-content">
        <h1>{{ __('menu_transactions') }}</h1>
        <p>{{ __('dashboard_subtitle') }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('currency.switch', $currentCurrency == 'USD' ? 'IDR' : 'USD') }}" class="btn-secondary-custom">
            <i class="fas fa-coins"></i> 
            <span>{{ $currentCurrency == 'USD' ? 'USD ($)' : 'IDR (Rp)' }}</span>
        </a>
        <a href="{{ route('transactions.calendar') }}" class="btn-secondary-custom">
            <i class="fas fa-calendar-alt"></i>
            <span>{{ __('menu_transactions') }} Calendar</span> </a>
        <a href="{{ route('transactions.create') }}" class="btn-primary-custom">
            <i class="fas fa-plus"></i>
            <span>{{ __('index_add_transaction') }}</span>
        </a>
    </div>
</div>

<form action="{{ route('transactions.index') }}" method="GET" class="filter-container">
    
    <div class="filter-row">
        <div class="form-group" style="flex: 2; min-width: 250px;">
            <label>{{ __('table_description') }}</label>
            <input type="text" name="search" class="form-control" 
                   placeholder="{{ __('filter_search_placeholder') }}" 
                   value="{{ request('search') }}">
        </div>

        <div class="form-group">
            <label>{{ __('filter_date_from') }}</label>
            <input type="date" name="date_from" class="form-control" 
                   value="{{ request('date_from') }}">
        </div>

        <div class="form-group">
            <label>{{ __('filter_date_to') }}</label>
            <input type="date" name="date_to" class="form-control" 
                   value="{{ request('date_to') }}">
        </div>

        <div class="form-group" style="display: flex; gap: 10px;">
            <button type="button" class="btn-secondary-custom" id="toggleFiltersBtn" style="height: 42px; width: 120px; justify-content: center;">
                @if($hasAdvancedFilters)
                    <i class="fas fa-chevron-up"></i> {{ __('filter_toggle_less') }}
                @else
                    <i class="fas fa-sliders-h"></i> {{ __('filter_toggle_more') }}
                @endif
            </button>
            <button type="submit" class="btn-primary-custom" style="height: 42px; width: 100px; justify-content: center;">
                {{ __('filter_apply') }}
            </button>
        </div>
    </div>

    <div class="filter-row" id="advancedFilters" 
         style="display: {{ $hasAdvancedFilters ? 'grid' : 'none' }}; border-top: 1px solid var(--border-light); padding-top: 1rem;">
        
        <div class="form-group">
            <label>{{ __('index_filter_type') }}</label>
            <select class="form-select" name="type">
                <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>{{ __('index_filter_all') }}</option>
                <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>{{ __('index_filter_income') }}</option>
                <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>{{ __('index_filter_expense') }}</option>
            </select>
        </div>

        <div class="form-group">
            <label>{{ __('table_category') }}</label>
            <select class="form-select" name="category">
                <option value="">{{ __('filter_all_categories') }}</option>
                @foreach($categories as $group => $items)
                    <optgroup label="{{ $group == 'Income' ? __('index_filter_income') : __('index_filter_expense') }}">
                        @foreach($items as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
                                {{-- Try to translate the specific category if a key exists, otherwise show raw --}}
                                {{ __($cat) != $cat ? __($cat) : $cat }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>{{ __('filter_min_amount') }} ({{ $currentCurrency == 'USD' ? '$' : 'Rp' }})</label>
            <input type="number" name="min_amount" class="form-control" step="0.01" 
                   value="{{ request('min_amount') }}" placeholder="0">
        </div>

        <div class="form-group">
            <label>{{ __('filter_max_amount') }} ({{ $currentCurrency == 'USD' ? '$' : 'Rp' }})</label>
            <input type="number" name="max_amount" class="form-control" step="0.01" 
                   value="{{ request('max_amount') }}" placeholder="No Limit">
        </div>
    </div>

    @if(request()->anyFilled(['search', 'date_from', 'date_to', 'type', 'category', 'min_amount', 'max_amount']) && request('type') != 'all')
    <div class="text-center mt-2">
        <a href="{{ route('transactions.index') }}" class="btn-link text-secondary" style="font-size: 0.9rem;">
            <i class="fas fa-times"></i> {{ __('filter_clear_all') }}
        </a>
    </div>
    @endif
</form>

<div class="summary-container">
    <div class="summary-pill">
        <div class="summary-item">
            <span class="summary-label">{{ __('summary_transactions') }}</span>
            <span class="summary-value">{{ $totalCount }}</span>
        </div>
        
        <div class="summary-divider"></div>
        
        <div class="summary-item">
            <span class="summary-label">{{ __('summary_volume') }}</span>
            <span class="summary-value {{ request('type') == 'expense' ? 'text-danger' : (request('type') == 'income' ? 'text-success' : '') }}">
                @if($currentCurrency == 'IDR')
                    Rp {{ number_format($totalSum, 0, ',', '.') }}
                @else
                    $ {{ number_format($totalSum / $exchangeRate['rate'], 2, '.', ',') }}
                @endif
            </span>
        </div>
    </div>
</div>

<div class="transactions-section">
    @if($transactions->count() > 0)
    <div class="table-responsive">
        <table class="transactions-table">
            <thead>
                <tr>
                    <th width="12%">{{ __('table_date') }}</th>
                    <th width="25%">{{ __('table_description') }}</th>
                    <th width="10%">{{ __('table_type') }}</th>
                    <th width="12%">{{ __('table_category') }}</th>
                    <th width="10%">{{ __('table_receipt') }}</th>
                    <th width="30%" class="text-end">{{ __('table_amount') }}</th>
                    <th width="11%"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->created_at->format('Y-m-d') }}</td>
                    <td><div class="fw-bold">{{ $transaction->description }}</div></td>
                    <td>
                        <span class="badge {{ $transaction->type == 'income' ? 'badge-income' : 'badge-expense' }}">
                            {{ $transaction->type == 'income' ? __('index_type_income') : __('index_type_expense') }}
                        </span>
                    </td>
                    <td>
                        @if($transaction->category)
                            <span class="badge {{ $transaction->type == 'income' ? 'badge-income-category' : 'badge-expense-category' }}">
                                {{-- Translate Category --}}
                                {{ __($transaction->category) != $transaction->category ? __($transaction->category) : $transaction->category }}
                            </span>
                        @else <span class="text-secondary opacity-50">-</span> @endif
                    </td>
                    <td>
                        @if($transaction->receipt_image_url)
                            @php
                                $receiptPath = Str::startsWith($transaction->receipt_image_url, 'http') 
                                    ? $transaction->receipt_image_url 
                                    : Storage::url($transaction->receipt_image_url);
                            @endphp
                            <a href="{{ $receiptPath }}" target="_blank" class="receipt-link">
                                <i class="fas fa-paperclip"></i> {{ __('index_receipt_view') }}
                            </a>
                        @else <span class="text-secondary opacity-50">-</span> @endif
                    </td>
                    <td class="text-end {{ $transaction->type == 'income' ? 'text-success' : 'text-danger' }} fw-bold">
                        @if($currentCurrency == 'IDR')
                            Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                        @else
                            $ {{ number_format($transaction->amount / $exchangeRate['rate'], 2, '.', ',') }}
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="action-buttons">
                            <a href="{{ route('transactions.edit', $transaction->id) }}" class="btn-edit"><i class="fas fa-pencil-alt"></i></a>
                            <form action="{{ route('transactions.destroy', $transaction->id) }}" method="POST" style="display: inline;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-delete" onclick="return confirm('{{ __('index_delete_confirm') }}')"><i class="fas fa-trash-alt"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    @if($transactions->hasPages())
    <div class="pagination-container">
        <span class="page-info">
            {{ __('index_pagination_showing') }} {{ $transactions->firstItem() }} - {{ $transactions->lastItem() }} of {{ $transactions->total() }}
        </span>
        <div class="pagination-btns">
            @if ($transactions->onFirstPage()) <button class="page-btn disabled"><i class="fas fa-chevron-left"></i></button>
            @else <a href="{{ $transactions->previousPageUrl() }}" class="page-btn"><i class="fas fa-chevron-left"></i></a> @endif
            
            @foreach ($transactions->getUrlRange(1, $transactions->lastPage()) as $page => $url)
                <a href="{{ $url }}" class="page-btn {{ $page == $transactions->currentPage() ? 'active' : '' }}">{{ $page }}</a>
            @endforeach
            
            @if ($transactions->hasMorePages()) <a href="{{ $transactions->nextPageUrl() }}" class="page-btn"><i class="fas fa-chevron-right"></i></a>
            @else <button class="page-btn disabled"><i class="fas fa-chevron-right"></i></button> @endif
        </div>
    </div>
    @endif
    @else
    <div class="no-data"><i class="fas fa-inbox"></i><p>{{ __('index_no_data') }}</p></div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('toggleFiltersBtn');
        const advancedFilters = document.getElementById('advancedFilters');

        toggleBtn.addEventListener('click', function() {
            if (advancedFilters.style.display === 'none') {
                advancedFilters.style.display = 'grid';
                toggleBtn.innerHTML = '<i class="fas fa-chevron-up"></i> {{ __("filter_toggle_less") }}';
            } else {
                advancedFilters.style.display = 'none';
                toggleBtn.innerHTML = '<i class="fas fa-sliders-h"></i> {{ __("filter_toggle_more") }}';
            }
        });
    });
</script>
@endsection