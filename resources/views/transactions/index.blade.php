@extends('app')

@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;
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
        <!-- Currency Switcher -->
        <a href="{{ route('currency.switch', $currentCurrency == 'USD' ? 'IDR' : 'USD') }}" class="btn-secondary-custom">
            <i class="fas fa-coins"></i> 
            <span>{{ $currentCurrency == 'USD' ? 'USD ($)' : 'IDR (Rp)' }}</span>
        </a>
        
        <!-- Add Transaction Button -->
        <a href="{{ route('transactions.create') }}" class="btn-primary-custom">
            <i class="fas fa-plus"></i>
            <span>{{ __('index_add_transaction') }}</span>
        </a>
    </div>
</div>

<!-- Filter Container -->
<form action="{{ route('transactions.index') }}" method="GET" class="filter-container">
    <div class="form-group">
        <label>{{ __('table_description') }}</label>
        <input type="text" 
               name="search" 
               class="form-control" 
               placeholder="{{ __('index_search_placeholder') }}" 
               value="{{ request('search') }}">
    </div>
    <div class="form-group">
        <label>{{ __('index_filter_type') }}</label>
        <select class="form-select" name="type">
            <option value="all" {{ request('type') == 'all' || !request('type') ? 'selected' : '' }}>{{ __('index_filter_all') }}</option>
            <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>{{ __('index_filter_income') }}</option>
            <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>{{ __('index_filter_expense') }}</option>
        </select>
    </div>
    <div class="form-group">
        <label>{{ __('index_filter_date') }}</label>
        <input type="date" 
               name="date" 
               class="form-control" 
               value="{{ request('date') }}">
    </div>
    <div class="form-group">
        <button type="submit" class="btn-secondary-custom w-100 justify-content-center">
            <i class="fas fa-filter"></i> {{ __('index_filter_apply') }}
        </button>
    </div>
    @if(request()->has('search') || request()->has('type') && request('type') != 'all' || request()->has('date'))
    <div class="form-group" style="grid-column: 1 / -1;">
        <a href="{{ route('transactions.index') }}" class="btn-secondary-custom w-100 justify-content-center">
            <i class="fas fa-times"></i> {{ __('index_filter_clear') }}
        </a>
    </div>
    @endif
</form>

<!-- Transactions Table -->
<div class="transactions-section">
    @if($transactions->count() > 0)
    <div class="table-responsive">
        <table class="transactions-table">
            <thead>
                <tr>
                    <th width="12%">{{ __('table_date') }}</th>
                    <th width="25%">{{ __('table_description') }}</th>
                    <th width="12%">{{ __('table_type') }}</th>
                    <th width="15%">{{ __('table_category') }}</th>
                    <th width="12%">{{ __('table_receipt') }}</th>
                    <th width="12%">{{ __('table_amount') }}</th>
                    <th width="12%"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->created_at->format('Y-m-d') }}</td>
                    <td>
                        <div class="fw-bold">{{ $transaction->description }}</div>
                    </td>
                    <td>
                        <span class="badge {{ $transaction->type == 'income' ? 'badge-income' : 'badge-expense' }}">
                            {{ ucfirst($transaction->type) }}
                        </span>
                    </td>
                    <td>
                        @if($transaction->category)
                            <span class="badge {{ $transaction->type == 'income' ? 'badge-income-category' : 'badge-expense-category' }}">
                                {{ $transaction->category }}
                            </span>
                        @else
                            <span class="text-secondary opacity-50">-</span>
                        @endif
                    </td>
                    <td>
                        @if($transaction->receipt_image_url)
                            @php
                                // Check if the path is already a full URL or a storage path
                                $receiptPath = $transaction->receipt_image_url;
                                if (!Str::startsWith($receiptPath, 'http')) {
                                    // If it's a storage path, use the storage URL helper
                                    $receiptPath = Storage::url($receiptPath);
                                }
                            @endphp
                            <a href="{{ $receiptPath }}" target="_blank" class="btn btn-sm btn-light border">
                                <i class="fas fa-file-image text-secondary"></i>
                            </a>
                        @else
                            <span class="text-secondary opacity-50">-</span>
                        @endif
                    </td>
                    <td class="{{ $transaction->type == 'income' ? 'text-success' : 'text-danger' }} fw-bold">
                        @if($currentCurrency == 'IDR')
                            Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                        @else
                            $ {{ number_format($transaction->amount / $exchangeRate['rate'], 2, '.', ',') }}
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="action-buttons">
                            <a href="{{ route('transactions.edit', $transaction->id) }}" class="btn-edit">
                                <i class="fas fa-pencil-alt"></i>
                            </a>
                            <form action="{{ route('transactions.destroy', $transaction->id) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-delete" onclick="return confirm('{{ __('index_delete_confirm') }}')">
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
    
    <!-- Replace the entire pagination section with: -->
    @if($transactions->hasPages())
    <div class="pagination-container">
        <span class="page-info">
            Showing {{ $transactions->firstItem() }} - {{ $transactions->lastItem() }} of {{ $transactions->total() }} results
        </span>
        <div class="pagination-btns">
            @if ($transactions->onFirstPage())
                <button class="page-btn disabled"><i class="fas fa-chevron-left"></i></button>
            @else
                <a href="{{ $transactions->previousPageUrl() }}" class="page-btn"><i class="fas fa-chevron-left"></i></a>
            @endif
            
            @foreach ($transactions->getUrlRange(1, $transactions->lastPage()) as $page => $url)
                @if ($page == $transactions->currentPage())
                    <button class="page-btn active">{{ $page }}</button>
                @else
                    <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
                @endif
            @endforeach
            
            @if ($transactions->hasMorePages())
                <a href="{{ $transactions->nextPageUrl() }}" class="page-btn"><i class="fas fa-chevron-right"></i></a>
            @else
                <button class="page-btn disabled"><i class="fas fa-chevron-right"></i></button>
            @endif
        </div>
    </div>
    @endif
    @else
    <div class="no-data">
        <i class="fas fa-inbox"></i>
        <p>{{ __('index_no_data') }}</p>
    </div>
    @endif
</div>

@if($transactions->count() > 0)
<div class="text-center mt-4">
    <a href="{{ route('dashboard') }}" class="btn-secondary-custom" style="width: 100%; justify-content: center;">
        <i class="fas fa-arrow-left"></i>
        <span>{{ __('index_btn_back') }}</span>
    </a>
</div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // File input display (if any)
        // Simple pagination button logic (you might want to replace this with Laravel's built-in pagination)
        const pageButtons = document.querySelectorAll('.page-btn');
        pageButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                pageButtons.forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button (if it's a number button)
                if (!this.querySelector('i')) {
                    this.classList.add('active');
                }
            });
        });
    });
</script>
@endsection