@extends('app')

@section('title', __('recurring_create_title') . ' - Flux')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/recurring.css') }}">
@endsection

@section('content')

<div class="form-header">
    <div>
        <h1>{{ __('recurring_create_title') }}</h1>
        <p>{{ __('recurring_create_subtitle') }}</p>
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
    <form action="{{ route('recurring.store') }}" method="POST" id="recurringForm">
        @csrf

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
            <label class="form-label" for="description">{{ __('recurring_form_description') }}</label>
            <input type="text" 
                   id="description" 
                   name="description" 
                   class="form-control @error('description') error @enderror" 
                   value="{{ old('description') }}" 
                   required 
                   placeholder="{{ __('recurring_form_description_placeholder') }}">
            @error('description')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="type">{{ __('recurring_form_type') }}</label>
                <select id="type" name="type" class="form-select @error('type') error @enderror" required onchange="updateCategoryOptions()">
                    <option value="" disabled selected>{{ __('recurring_form_type_placeholder') }}</option>
                    <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>{{ __('index_type_expense') }}</option>
                    <option value="income" {{ old('type') == 'income' ? 'selected' : '' }}>{{ __('index_type_income') }}</option>
                </select>
                @error('type')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="frequency">{{ __('recurring_form_frequency') }}</label>
                <select id="frequency" name="frequency" class="form-select @error('frequency') error @enderror" required>
                    <option value="" disabled selected>{{ __('recurring_form_frequency_placeholder') }}</option>
                    <option value="weekly" {{ old('frequency') == 'weekly' ? 'selected' : '' }}>{{ __('recurring_frequency_weekly') }}</option>
                    <option value="monthly" {{ old('frequency') == 'monthly' ? 'selected' : '' }}>{{ __('recurring_frequency_monthly') }}</option>
                    <option value="yearly" {{ old('frequency') == 'yearly' ? 'selected' : '' }}>{{ __('recurring_frequency_yearly') }}</option>
                </select>
                @error('frequency')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="amount">
                {{ __('recurring_form_amount') }} ({{ $currentCurrency }})
                @if($currentCurrency == 'USD')
                    <small class="text-muted" style="margin-left: 0.5rem;">{{ __('recurring_form_amount_usd_help') }}</small>
                @endif
            </label>
            <div class="amount-input-container">
                <div class="currency-symbol">
                    @if($currentCurrency == 'IDR') Rp @else $ @endif
                </div>
                <input type="number" 
                    step="{{ $currentCurrency == 'IDR' ? '1' : '0.01' }}" 
                    id="amount" 
                    name="amount" 
                    class="form-control @error('amount') error @enderror" 
                    value="{{ old('amount') }}" 
                    required 
                    placeholder="{{ $currentCurrency == 'IDR' ? '50000' : '10.00' }}">
            </div>
            @error('amount')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="category">{{ __('recurring_form_category') }}</label>
            <select id="category" name="category" class="form-select @error('category') error @enderror" disabled>
                <option value="" selected>{{ __('recurring_form_category_placeholder') }}</option>
            </select>
            @error('category')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="start_date">{{ __('recurring_form_start_date') }}</label>
            <input type="date" 
                   id="start_date" 
                   name="start_date" 
                   class="form-control @error('start_date') error @enderror" 
                   value="{{ old('start_date', date('Y-m-d')) }}" 
                   required>
            @error('start_date')
                <div class="error-message">{{ $message }}</div>
            @enderror
            <p style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-secondary-light);">
                {{ __('recurring_form_start_date_help') }}
            </p>
        </div>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>{{ __('recurring_info_how_it_works') }}</strong>
                <p>{{ __('recurring_info_how_it_works_desc') }}</p>
            </div>
        </div>

        <div class="button-group">
            <button type="submit" class="btn-save">
                <i class="fas fa-save"></i> {{ __('recurring_btn_create') }}
            </button>
            <a href="{{ route('recurring.index') }}" class="btn-cancel">
                <i class="fas fa-times"></i> {{ __('recurring_btn_cancel') }}
            </a>
        </div>
    </form>
</div>

<script>
    const categoryOptions = {
        income: [
            { value: '', text: '{{ __("recurring_form_category_placeholder") }}' },
            { value: 'Salary', text: '{{ __("Salary") }}' },
            { value: 'Freelance', text: '{{ __("Freelance") }}' },
            { value: 'Investment', text: '{{ __("Investment") }}' },
            { value: 'Business', text: '{{ __("Business") }}' },
            { value: 'Other Income', text: '{{ __("Other Income") }}' }
        ],
        expense: [
            { value: '', text: '{{ __("recurring_form_category_placeholder") }}' },
            { value: 'Food', text: '{{ __("Food") }}' },
            { value: 'Shopping', text: '{{ __("Shopping") }}' },
            { value: 'Transportation', text: '{{ __("Transportation") }}' },
            { value: 'Entertainment', text: '{{ __("Entertainment") }}' },
            { value: 'Bills & Utilities', text: '{{ __("Bills & Utilities") }}' },
            { value: 'Healthcare', text: '{{ __("Healthcare") }}' },
            { value: 'Education', text: '{{ __("Education") }}' },
            { value: 'Travel', text: '{{ __("Travel") }}' },
            { value: 'Other', text: '{{ __("Other") }}' }
        ]
    };

    function updateCategoryOptions() {
        const typeSelect = document.getElementById('type');
        const categorySelect = document.getElementById('category');
        const selectedType = typeSelect.value;
        
        categorySelect.innerHTML = '';
        
        if (selectedType === 'income' || selectedType === 'expense') {
            categorySelect.disabled = false;
            
            const options = categoryOptions[selectedType];
            options.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option.value;
                optionElement.textContent = option.text;
                
                if (option.value === '{{ old("category") }}') {
                    optionElement.selected = true;
                }
                
                categorySelect.appendChild(optionElement);
            });
        } else {
            categorySelect.disabled = true;
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = '{{ __("recurring_form_category_placeholder") }}';
            defaultOption.selected = true;
            categorySelect.appendChild(defaultOption);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('recurringForm');
        if (form) {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("recurring_btn_creating") }}';
                    submitBtn.disabled = true;
                }
            });
        }

        const typeSelect = document.getElementById('type');
        if (typeSelect.value) {
            updateCategoryOptions();
        }
        
        typeSelect.addEventListener('change', updateCategoryOptions);
    });
</script>
@endsection