@extends('app')

@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;
@endphp

@section('title', 'Edit Transaction - Flux')

@php
    $currentCurrency = session('currency', app()->getLocale() == 'id' ? 'IDR' : 'USD');
@endphp

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/transactions.css') }}">
@endsection

@section('content')

<div class="form-header">
    <div>
        <h1>{{ __('edit_title') }}</h1>
        <p>{{ __('edit_subtitle') }}</p>
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
    <form action="{{ route('transactions.update', $transaction->id) }}" method="POST" enctype="multipart/form-data" id="transactionForm">
        @csrf
        @method('PUT')

        <input type="hidden" name="input_currency" value="{{ $currentCurrency }}">

        @if ($errors->any())
            <div class="error-message" style="margin-bottom: 1.5rem;">
                <p><strong>{{ __('edit_error_fix') }}</strong></p>
                <ul style="margin-top: 0.5rem; padding-left: 1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="form-group">
            <label class="form-label" for="description">{{ __('edit_description_label') }}</label>
            <input type="text" 
                   id="description" 
                   name="description" 
                   class="form-control @error('description') error @enderror" 
                   value="{{ old('description', $transaction->description) }}" 
                   required 
                   placeholder="{{ __('edit_description_placeholder') }}">
            @error('description')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="amount">
                {{ str_replace('{currency}', $currentCurrency, __('edit_amount_label')) }}
                @if($currentCurrency == 'USD')
                    <small class="text-muted">{{ __('edit_amount_usd_note') }}</small>
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
                    value="{{ old('amount', $currentCurrency == 'IDR' ? $transaction->amount : $transaction->amount / $exchangeRate['rate']) }}" 
                    required 
                    placeholder="{{ $currentCurrency == 'IDR' ? __('edit_amount_placeholder_idr') : __('edit_amount_placeholder_usd') }}">
            </div>
            @error('amount')
                <div class="error-message">{{ $message }}</div>
            @enderror
            <p style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-secondary-light);">
                @if($currentCurrency == 'IDR')
                    {{ str_replace('{amount}', number_format($transaction->amount, 0, ',', '.'), __('edit_amount_stored_idr')) }}
                @else
                    {{ str_replace(['{rate}', '{stored_amount}'], [number_format($exchangeRate['rate'], 0, ',', '.'), number_format($transaction->amount, 0, ',', '.')], __('edit_amount_stored_usd')) }}
                @endif
            </p>
        </div>

        <div class="form-group">
            <label class="form-label" for="type">{{ __('edit_type_label') }}</label>
            <select id="type" name="type" class="form-select @error('type') error @enderror" required onchange="updateCategoryOptions()">
                <option value="" disabled>{{ __('edit_type_placeholder') }}</option>
                <option value="expense" {{ (old('type', $transaction->type) == 'expense') ? 'selected' : '' }}>{{ __('edit_type_expense') }}</option>
                <option value="income" {{ (old('type', $transaction->type) == 'income') ? 'selected' : '' }}>{{ __('edit_type_income') }}</option>
            </select>
            @error('type')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="category">{{ __('edit_category_label') }}</label>
            <select id="category" name="category" class="form-select @error('category') error @enderror" disabled>
                <option value="" selected>{{ __('edit_category_placeholder') }}</option>
            </select>
            @error('category')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label">{{ __('edit_current_receipt') }}</label>
            @if($transaction->receipt_image_url)
                @php
                    $receiptPath = $transaction->receipt_image_url;
                    if (!Str::startsWith($receiptPath, 'http')) {
                        $receiptPath = Storage::url($receiptPath);
                    }
                @endphp
                <div class="current-receipt">
                    <p style="font-size: 0.875rem; margin-bottom: 0.5rem;">{{ __('edit_current_receipt') }}:</p>
                    <img src="{{ $receiptPath }}" alt="{{ __('index_receipt_view') }}" width="150">
                    <p style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-secondary-light);">
                        {{ __('edit_receipt_keep') }}
                    </p>
                </div>
            @else
                <p style="font-size: 0.875rem; color: var(--text-secondary-light);">{{ __('edit_no_receipt') }}</p>
            @endif
        </div>

        <div class="form-group">
            <label class="form-label">{{ __('edit_receipt_change_label') }}</label>
            <div class="file-input-container">
                <input type="file" 
                       id="receipt_image" 
                       name="receipt_image" 
                       class="file-input" 
                       accept="image/*">
                <label for="receipt_image" class="file-input-label">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>{{ __('edit_receipt_change_button') }}</span>
                </label>
            </div>
            <div id="fileName" class="file-name"></div>
            <p style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-secondary-light);">
                {{ __('edit_receipt_formats') }}
            </p>
            @error('receipt_image')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="button-group">
            <button type="submit" class="btn-save">
                <i class="fas fa-save"></i> {{ __('edit_button_update') }}
            </button>
            <a href="{{ route('transactions.index') }}" class="btn-cancel">
                <i class="fas fa-times"></i> {{ __('edit_button_cancel') }}
            </a>
        </div>
    </form>
</div>

<script>
    const categoryOptions = {
        income: [
            { value: '', text: '{{ __("edit_category_placeholder") }}' },
            { value: 'Salary', text: '{{ __("Salary") }}' },
            { value: 'Freelance', text: '{{ __("Freelance") }}' },
            { value: 'Investment', text: '{{ __("Investment") }}' },
            { value: 'Business', text: '{{ __("Business") }}' },
            { value: 'Other Income', text: '{{ __("Other Income") }}' }
        ],
        expense: [
            { value: '', text: '{{ __("edit_category_placeholder") }}' },
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
            const currentCategory = '{{ old("category", $transaction->category) }}';
            
            options.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option.value;
                optionElement.textContent = option.text;
                
                if (option.value === currentCategory) {
                    optionElement.selected = true;
                }
                
                categorySelect.appendChild(optionElement);
            });
            
            const currentType = '{{ $transaction->type }}';
            const currentCat = '{{ $transaction->category }}';
            if (selectedType !== currentType && currentCat) {
                showCategoryWarning();
            }
        } else {
            categorySelect.disabled = true;
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = '{{ __("edit_category_placeholder") }}';
            defaultOption.selected = true;
            categorySelect.appendChild(defaultOption);
        }
    }

    function showCategoryWarning() {
        if (!document.getElementById('category-warning')) {
            const warningDiv = document.createElement('div');
            warningDiv.id = 'category-warning';
            warningDiv.className = 'alert alert-warning mt-2';
            warningDiv.style.fontSize = '0.875rem';
            warningDiv.style.padding = '0.5rem';
            warningDiv.innerHTML = '⚠️ {{ __("category_type_mismatch") }}';
            
            const categorySelect = document.getElementById('category');
            categorySelect.parentNode.insertBefore(warningDiv, categorySelect.nextSibling);
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
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("edit_updating") }}';
                    submitBtn.disabled = true;
                }
            });
        }

        updateCategoryOptions();
    
        const typeSelect = document.getElementById('type');
        typeSelect.addEventListener('change', updateCategoryOptions);
    });
</script>
@endsection