@extends('app')

@section('title', __('budget_meta_title_edit'))

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/budget.css') }}">
@endsection

@section('content')

<div class="form-header">
    <div>
        <h1>{{ __('budget_edit_title') }}</h1>
        <p>{{ __('budget_edit_subtitle', ['category' => __($budget->category)]) }}</p>
    </div>
    <div>
        <a href="{{ route('currency.switch', $currentCurrency == 'USD' ? 'IDR' : 'USD') }}" class="btn-secondary-custom">
            <i class="fas fa-coins"></i> 
            <span>{{ $currentCurrency == 'USD' ? 'USD ($)' : 'IDR (Rp)' }}</span>
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert-success">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

<div class="form-card">
    <form action="{{ route('budget.update', $budget->id) }}" method="POST" id="budgetForm">
        @csrf
        @method('PUT')

        <input type="hidden" name="input_currency" value="{{ $currentCurrency }}">

        @if ($errors->any())
            <div class="error-message" style="margin-bottom: 1.5rem;">
                <p><strong>{{ __('create_error_fix') }}</strong></p>
                <ul style="margin-top: 0.5rem; padding-left: 1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="form-group">
            <label class="form-label" for="category">
                {{ __('budget_form_category_label') }}
            </label>
            <input type="text" 
                   id="category" 
                   class="form-control" 
                   value="{{ __($budget->category) }}" 
                   disabled 
                   style="background-color: var(--bg-light); opacity: 0.6; cursor: not-allowed;">
            <p class="form-hint">
                <i class="fas fa-info-circle"></i> 
                {{ __('budget_form_category_locked_hint') }}
            </p>
        </div>

        <div class="form-group">
            <label class="form-label" for="amount">
                {{ __('budget_form_amount_label', ['currency' => $currentCurrency]) }}
                <span class="required-asterisk">*</span>
                @if($currentCurrency == 'USD')
                    <small class="text-muted">{{ __('budget_form_amount_usd_note') }}</small>
                @endif
            </label>
            <div class="amount-input-container">
                <div class="currency-symbol">
                    @if($currentCurrency == 'IDR')
                        Rp
                    @else
                        $
                    @endif
                </div>
                <input type="number" 
                    step="{{ $currentCurrency == 'IDR' ? '1' : '0.01' }}" 
                    id="amount" 
                    name="amount" 
                    class="form-control @error('amount') error @enderror" 
                    value="{{ old('amount', $currentCurrency == 'IDR' ? $budget->amount : $budget->amount / $exchangeRate['rate']) }}" 
                    required 
                    min="0.01"
                    placeholder="{{ $currentCurrency == 'IDR' ? '1000000' : '100.00' }}">
            </div>
            @error('amount')
                <div class="error-message">{{ $message }}</div>
            @enderror
            <p class="form-hint">
                @if($currentCurrency == 'IDR')
                    <i class="fas fa-database"></i> 
                    {{ __('budget_form_stored_idr', ['amount' => number_format($budget->amount, 0, ',', '.')]) }}
                @else
                    <i class="fas fa-exchange-alt"></i> 
                    {{ __('budget_form_stored_usd', [
                        'rate' => number_format($exchangeRate['rate'], 0, ',', '.'),
                        'stored_amount' => number_format($budget->amount, 0, ',', '.')
                    ]) }}
                @endif
            </p>
        </div>

        <div class="status-card">
            <div class="status-header">
                <h4>{{ __('budget_status_section_title') }}</h4>
            </div>
            <div class="status-body">
                <div class="status-row">
                    <span class="status-label">{{ __('budget_status_limit') }}</span>
                    <span class="status-value">
                        @if($currentCurrency == 'IDR')
                            Rp {{ number_format($budget->amount, 0, ',', '.') }}
                        @else
                            $ {{ number_format($budget->amount / $exchangeRate['rate'], 2, '.', ',') }}
                        @endif
                    </span>
                </div>
                <div class="status-row">
                    <span class="status-label">{{ __('budget_status_spent_month') }}</span>
                    <span class="status-value text-danger">
                        @if($currentCurrency == 'IDR')
                            Rp {{ number_format($spent, 0, ',', '.') }}
                        @else
                            $ {{ number_format($spent / $exchangeRate['rate'], 2, '.', ',') }}
                        @endif
                    </span>
                </div>
                <div class="status-row">
                    <span class="status-label">{{ __('budget_overview_remaining') }}</span>
                    <span class="status-value {{ ($budget->amount - $spent) >= 0 ? 'text-success' : 'text-danger' }}">
                        @if($currentCurrency == 'IDR')
                            Rp {{ number_format($budget->amount - $spent, 0, ',', '.') }}
                        @else
                            $ {{ number_format(($budget->amount - $spent) / $exchangeRate['rate'], 2, '.', ',') }}
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <div class="info-box">
            <div class="info-icon">
                <i class="fas fa-lightbulb"></i>
            </div>
            <div class="info-content">
                <h4>{{ __('budget_update_tips_title') }}</h4>
                <ul>
                    <li>{{ __('budget_update_tip_1') }}</li>
                    <li>{{ __('budget_update_tip_2') }}</li>
                    <li>{{ __('budget_update_tip_3') }}</li>
                    <li>{{ __('budget_update_tip_4') }}</li>
                </ul>
            </div>
        </div>

        <div class="button-group">
            <button type="submit" class="btn-save">
                <i class="fas fa-save"></i> {{ __('budget_btn_update') }}
            </button>
            <a href="{{ route('budget.index') }}" class="btn-cancel">
                <i class="fas fa-times"></i> {{ __('budget_btn_cancel') }}
            </a>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('budgetForm');
        if (form) {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("budget_btn_updating") }}';
                    submitBtn.disabled = true;
                }
            });
        }

        // Amount validation
        const amountInput = document.getElementById('amount');
        if (amountInput) {
            amountInput.addEventListener('input', function() {
                if (this.value < 0) {
                    this.value = 0;
                }
            });
        }
    });
</script>
@endsection