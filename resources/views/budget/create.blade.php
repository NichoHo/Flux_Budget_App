@extends('app')

@section('title', __('budget_meta_title_add'))

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/budget.css') }}">
@endsection

@section('content')

<div class="form-header">
    <div>
        <h1>{{ __('budget_create_title') }}</h1>
        <p>{{ __('budget_create_subtitle') }}</p>
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
    <form action="{{ route('budget.store') }}" method="POST" id="budgetForm">
        @csrf

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
                <span class="required-asterisk">*</span>
            </label>
            <select id="category" name="category" class="form-select @error('category') error @enderror" required>
                <option value="" disabled selected>{{ __('budget_form_category_placeholder') }}</option>
                @if(count($availableCategories) > 0)
                    @foreach($availableCategories as $category)
                        <option value="{{ $category }}" {{ old('category') == $category ? 'selected' : '' }}>
                            {{ __($category) }}
                        </option>
                    @endforeach
                @else
                    <option value="" disabled>{{ __('budget_form_category_all_taken') }}</option>
                @endif
            </select>
            @error('category')
                <div class="error-message">{{ $message }}</div>
            @enderror
            <p class="form-hint">
                <i class="fas fa-info-circle"></i> 
                {{ __('budget_form_category_hint') }}
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
                    value="{{ old('amount') }}" 
                    required 
                    min="0.01"
                    placeholder="{{ $currentCurrency == 'IDR' ? '1000000' : '100.00' }}">
            </div>
            @error('amount')
                <div class="error-message">{{ $message }}</div>
            @enderror
            <p class="form-hint">
                @if($currentCurrency == 'IDR')
                    <i class="fas fa-info-circle"></i> 
                    {{ __('budget_form_amount_idr_hint') }}
                @else
                    <i class="fas fa-exchange-alt"></i> 
                    {{ __('budget_form_exchange_rate', ['rate' => number_format($exchangeRate['rate'], 0, ',', '.')]) }}
                @endif
            </p>
        </div>

        <div class="info-box">
            <div class="info-icon">
                <i class="fas fa-lightbulb"></i>
            </div>
            <div class="info-content">
                <h4>{{ __('budget_tips_title') }}</h4>
                <ul>
                    <li>{{ __('budget_tip_1') }}</li>
                    <li>{{ __('budget_tip_2') }}</li>
                    <li>{{ __('budget_tip_3') }}</li>
                    <li>{{ __('budget_tip_4') }}</li>
                </ul>
            </div>
        </div>

        <div class="button-group">
            <button type="submit" class="btn-save" {{ count($availableCategories) == 0 ? 'disabled' : '' }}>
                <i class="fas fa-save"></i> {{ __('budget_btn_save') }}
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
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("budget_btn_creating") }}';
                    submitBtn.disabled = true;
                }
            });
        }

        // Category icon preview
        const categorySelect = document.getElementById('category');
        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                // Optional: Add visual feedback when category is selected
                this.classList.add('selected');
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