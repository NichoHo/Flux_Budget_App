@extends('app')

@section('title', 'Add Recurring Bill - Flux')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/recurring.css') }}">
@endsection

@section('content')

<div class="form-header">
    <div>
        <h1>Add Recurring Bill</h1>
        <p>Set up automatic recurring income or expense</p>
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
                <p><strong>Please fix the following errors:</strong></p>
                <ul style="margin-top: 0.5rem; padding-left: 1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <input type="text" 
                   id="description" 
                   name="description" 
                   class="form-control @error('description') error @enderror" 
                   value="{{ old('description') }}" 
                   required 
                   placeholder="e.g., Netflix Subscription, Monthly Salary">
            @error('description')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="type">Type</label>
                <select id="type" name="type" class="form-select @error('type') error @enderror" required onchange="updateCategoryOptions()">
                    <option value="" disabled selected>Select type</option>
                    <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>Expense</option>
                    <option value="income" {{ old('type') == 'income' ? 'selected' : '' }}>Income</option>
                </select>
                @error('type')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="frequency">Frequency</label>
                <select id="frequency" name="frequency" class="form-select @error('frequency') error @enderror" required>
                    <option value="" disabled selected>Select frequency</option>
                    <option value="weekly" {{ old('frequency') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="monthly" {{ old('frequency') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="yearly" {{ old('frequency') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                </select>
                @error('frequency')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="amount">
                Amount ({{ $currentCurrency }})
                @if($currentCurrency == 'USD')
                    <small class="text-muted" style="margin-left: 0.5rem;">Will be converted to IDR for storage</small>
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
                    placeholder="{{ $currentCurrency == 'IDR' ? '50000' : '10.00' }}">
            </div>
            @error('amount')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="category">Category (Optional)</label>
            <select id="category" name="category" class="form-select @error('category') error @enderror" disabled>
                <option value="" selected>Select category</option>
            </select>
            @error('category')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="start_date">Start Date</label>
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
                This will be the date of the first automatic transaction
            </p>
        </div>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>How it works:</strong>
                <p>Recurring bills automatically create transactions on the scheduled date. You can edit or stop them anytime.</p>
            </div>
        </div>

        <div class="button-group">
            <button type="submit" class="btn-save">
                <i class="fas fa-save"></i> Create Recurring Bill
            </button>
            <a href="{{ route('recurring.index') }}" class="btn-cancel">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<script>
    const categoryOptions = {
        income: [
            { value: '', text: 'Select category' },
            { value: 'Salary', text: 'Salary' },
            { value: 'Freelance', text: 'Freelance' },
            { value: 'Investment', text: 'Investment' },
            { value: 'Business', text: 'Business' },
            { value: 'Other Income', text: 'Other Income' }
        ],
        expense: [
            { value: '', text: 'Select category' },
            { value: 'Food', text: 'Food' },
            { value: 'Shopping', text: 'Shopping' },
            { value: 'Transportation', text: 'Transportation' },
            { value: 'Entertainment', text: 'Entertainment' },
            { value: 'Bills & Utilities', text: 'Bills & Utilities' },
            { value: 'Healthcare', text: 'Healthcare' },
            { value: 'Education', text: 'Education' },
            { value: 'Travel', text: 'Travel' },
            { value: 'Other', text: 'Other' }
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
            defaultOption.textContent = 'Select category';
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
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
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