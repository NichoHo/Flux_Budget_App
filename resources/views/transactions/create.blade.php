@extends('app')

@section('title', 'Add Transaction - Flux')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/transactions.css') }}">
@endsection

@section('content')

<div class="form-header">
    <div>
        <h1>{{ __('create_title') }}</h1>
        <p>{{ __('create_subtitle') }}</p>
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
    <form action="{{ route('transactions.store') }}" method="POST" enctype="multipart/form-data" id="transactionForm">
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
            <label class="form-label" for="description">{{ __('create_description_label') }}</label>
            <input type="text" 
                   id="description" 
                   name="description" 
                   class="form-control @error('description') error @enderror" 
                   value="{{ old('description') }}" 
                   required 
                   placeholder="{{ __('create_description_placeholder') }}">
            @error('description')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="amount">
                {{ str_replace('{currency}', $currentCurrency, __('create_amount_label')) }}
                @if($currentCurrency == 'USD')
                    <small class="text-muted" style="margin-left: 0.5rem;">{{ __('create_amount_usd_note') }}</small>
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
                    placeholder="{{ $currentCurrency == 'IDR' ? __('create_amount_placeholder_idr') : __('create_amount_placeholder_usd') }}">
            </div>
            @error('amount')
                <div class="error-message">{{ $message }}</div>
            @enderror
            <p style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-secondary-light);">
                @if($currentCurrency == 'IDR')
                    {{ __('create_amount_stored_idr') }}
                @else
                    {{ str_replace('{rate}', number_format($exchangeRate['rate'], 0, ',', '.'), __('create_amount_exchange_rate')) }}
                @endif
            </p>
        </div>

        <div class="form-group">
            <label class="form-label" for="type">{{ __('create_type_label') }}</label>
            <select id="type" name="type" class="form-select @error('type') error @enderror" required onchange="updateCategoryOptions()">
                <option value="" disabled selected>{{ __('create_type_placeholder') }}</option>
                <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>{{ __('create_type_expense') }}</option>
                <option value="income" {{ old('type') == 'income' ? 'selected' : '' }}>{{ __('create_type_income') }}</option>
            </select>
            @error('type')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="category">{{ __('create_category_label') }}</label>
            <select id="category" name="category" class="form-select @error('category') error @enderror" disabled>
                <option value="" selected>{{ __('create_category_placeholder') }}</option>
            </select>
            @error('category')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label">{{ __('create_receipt_label') }}</label>
            <div class="file-input-container">
                <input type="file" 
                       id="receipt_image" 
                       name="receipt_image" 
                       class="file-input" 
                       accept="image/*">
                <label for="receipt_image" class="file-input-label">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>{{ __('create_receipt_button') }}</span>
                </label>
            </div>
            <div id="fileName" class="file-name"></div>
            <p style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-secondary-light);">
                {{ __('create_receipt_formats') }}
            </p>
            @error('receipt_image')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="button-group">
            <button type="submit" class="btn-save">
                <i class="fas fa-save"></i> {{ __('create_button_save') }}
            </button>
            <a href="{{ route('transactions.index') }}" class="btn-cancel">
                <i class="fas fa-times"></i> {{ __('create_button_cancel') }}
            </a>
        </div>
    </form>
</div>

<script>
    const categoryOptions = {
        income: [
            { value: '', text: '{{ __("create_category_placeholder") }}' },
            { value: 'Salary', text: '{{ __("Salary") }}' },
            { value: 'Freelance', text: '{{ __("Freelance") }}' },
            { value: 'Investment', text: '{{ __("Investment") }}' },
            { value: 'Business', text: '{{ __("Business") }}' },
            { value: 'Other Income', text: '{{ __("Other Income") }}' }
        ],
        expense: [
            { value: '', text: '{{ __("create_category_placeholder") }}' },
            { value: 'Food', text: '{{ __("Food") }}' },
            { value: 'Shopping', text: '{{ __("Shopping") }}' },
            { value: 'Transportation', text: '{{ __("Transportation") }}' },
            { value: 'Entertainment', text: '{{ __("Entertainment") }}' },
            { value: 'Bills and Utilities', text: '{{ __("Bills and Utilities") }}' },
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
            defaultOption.textContent = '{{ __("create_category_placeholder") }}';
            defaultOption.selected = true;
            categorySelect.appendChild(defaultOption);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('receipt_image');
        const fileName = document.getElementById('fileName');
        
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    fileName.textContent = '{{ __("index_receipt_view") }}: ' + this.files[0].name;
                } else {
                    fileName.textContent = '';
                }
            });
        }
        
        const form = document.getElementById('transactionForm');
        if (form) {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("create_saving") }}';
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