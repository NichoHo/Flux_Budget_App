@extends('app')

@section('title', 'Dashboard - Flux')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endsection

@section('content')

@php
    $nextCurrency = $currentCurrency == 'USD' ? 'IDR' : 'USD';
@endphp

<div class="dashboard-header">
    <div class="header-content">
        <h1>{{ __('welcome_user', ['name' => auth()->user()->name]) }}</h1>
        <p>{{ __('dashboard_subtitle') }}</p>
    </div>
    <div class="header-actions">
        <button type="button" id="privacyToggle" class="btn-secondary-custom" onclick="togglePrivacyMode()" title="Toggle Privacy Mode">
            <i class="fas fa-eye"></i>
        </button>

        <a href="{{ route('currency.switch', $nextCurrency) }}" class="btn-secondary-custom">
            <i class="fas fa-coins"></i> 
            <span>{{ $currentCurrency == 'USD' ? 'USD ($)' : 'IDR (Rp)' }}</span>
        </a>
        
        <a href="{{ route('transactions.create') }}" class="btn-primary-custom">
            <i class="fas fa-plus"></i>
            <span>{{ __('index_add_transaction') }}</span>
        </a>
    </div>
</div>

<!-- Balance Cards -->
<div class="balance-cards">
    <div class="balance-card card-total">
        <div class="card-icon"><i class="fas fa-wallet"></i></div>
        <div class="card-content">
            <p class="card-label">{{ __('card_balance') }}</p>
            <h2 class="card-value">
                @if($currentCurrency == 'IDR')
                    Rp {{ number_format($balance, 0, ',', '.') }}
                @else
                    $ {{ number_format($balance, 2, '.', ',') }}
                @endif
            </h2>
        </div>
    </div>

    <div class="balance-card card-income">
        <div class="card-icon"><i class="fas fa-arrow-up"></i></div>
        <div class="card-content">
            <p class="card-label">{{ __('card_income') }}</p>
            <h2 class="card-value">
                @if($currentCurrency == 'IDR')
                    Rp {{ number_format($income, 0, ',', '.') }}
                @else
                    $ {{ number_format($income, 2, '.', ',') }}
                @endif
            </h2>
        </div>
    </div>

    <div class="balance-card card-expense">
        <div class="card-icon"><i class="fas fa-arrow-down"></i></div>
        <div class="card-content">
            <p class="card-label">{{ __('card_expense') }}</p>
            <h2 class="card-value">
                @if($currentCurrency == 'IDR')
                    Rp {{ number_format($expense, 0, ',', '.') }}
                @else
                    $ {{ number_format($expense, 2, '.', ',') }}
                @endif
            </h2>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="transactions-section">
    <div class="section-header mb-3">
        <h2>{{ __('section_activity') }}</h2>
    </div>

    <div class="table-responsive">
        <table class="transactions-table">
            <thead>
                <tr>
                    <th>{{ __('table_date') }}</th>
                    <th>{{ __('table_description') }}</th>
                    <th>{{ __('table_category') }}</th>
                    <th>{{ __('table_amount') }}</th>
                    <th>{{ __('table_receipt') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentTransactions as $transaction)
                    <tr>
                        <td class="td-date">{{ $transaction->created_at->format('Y-m-d') }}</td>
                        <td class="td-desc">{{ $transaction->description }}</td>
                        
                        <td class="td-cat">
                            @if($transaction->category && trim($transaction->category) !== '')
                                <span class="badge {{ $transaction->type == 'income' ? 'badge-income-category' : 'badge-expense-category' }}">
                                    {{ $transaction->category }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        <td class="td-amount {{ $transaction->type == 'income' ? 'text-success' : 'text-danger' }}">
                            @if($currentCurrency == 'IDR')
                                Rp {{ number_format($transaction->display_amount, 0, ',', '.') }}
                            @else
                                $ {{ number_format($transaction->display_amount, 2, '.', ',') }}
                            @endif
                        </td>

                        <td class="td-receipt">
                            @if($transaction->receipt_image_url)
                                @php
                                    $receiptPath = $transaction->receipt_image_url;
                                    if (!\Illuminate\Support\Str::startsWith($receiptPath, 'http')) {
                                        $receiptPath = Storage::url($receiptPath);
                                    }
                                @endphp
                                <a href="{{ $receiptPath }}" target="_blank" class="btn-link">
                                    <i class="fas fa-paperclip"></i>
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr class="no-data">
                        <td colspan="5" class="text-center py-4">
                            <i class="fas fa-inbox fa-2x mb-3 text-secondary"></i>
                            <p>{{ __('no_transactions') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="text-center mt-4">
        <a href="{{ route('transactions.index') }}" class="btn-secondary-custom" style="width: 100%; justify-content: center;">
            <span>{{ __('btn_view_history') }}</span> <i class="fas fa-arrow-right ms-2"></i>
        </a>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Check local storage for privacy preference on load
        const isPrivacyActive = localStorage.getItem('privacyMode') === 'true';
        if (isPrivacyActive) {
            enablePrivacyMode();
        }
    });

    function togglePrivacyMode() {
        const body = document.body;
        // Check if currently active to determine action
        if (body.classList.contains('privacy-active')) {
            disablePrivacyMode();
        } else {
            enablePrivacyMode();
        }
    }

    function enablePrivacyMode() {
        document.body.classList.add('privacy-active');
        const icon = document.querySelector('#privacyToggle i');
        if(icon) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
        localStorage.setItem('privacyMode', 'true');
    }

    function disablePrivacyMode() {
        document.body.classList.remove('privacy-active');
        const icon = document.querySelector('#privacyToggle i');
        if(icon) {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
        localStorage.setItem('privacyMode', 'false');
    }
</script>
@endsection