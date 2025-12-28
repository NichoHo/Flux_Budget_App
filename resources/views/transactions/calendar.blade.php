@extends('app')

@php
    use Carbon\Carbon;
@endphp

@section('title', __('menu_transactions') . ' - Calendar - Flux')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/transactions.css') }}">
    <link rel="stylesheet" href="{{ asset('css/calendar.css') }}">
@endsection

@section('content')

<div class="transactions-header">
    <div class="header-content">
        <h1>{{ __('menu_transactions') }} Calendar</h1>
        <p>View your transactions by date</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('currency.switch', $currentCurrency == 'USD' ? 'IDR' : 'USD') }}" class="btn-secondary-custom">
            <i class="fas fa-coins"></i> 
            <span>{{ $currentCurrency == 'USD' ? 'USD ($)' : 'IDR (Rp)' }}</span>
        </a>
        
        <a href="{{ route('transactions.index') }}" class="btn-secondary-custom">
            <i class="fas fa-list"></i>
            <span>List View</span>
        </a>
        
        <a href="{{ route('transactions.create') }}" class="btn-primary-custom">
            <i class="fas fa-plus"></i>
            <span>{{ __('index_add_transaction') }}</span>
        </a>
    </div>
</div>

<div class="calendar-layout">
    <div class="calendar-section">
        <div class="calendar-navigation">
            <a href="{{ route('transactions.calendar', ['month' => $prevMonth->format('Y-m')]) }}" class="nav-btn">
                <i class="fas fa-chevron-left"></i>
            </a>
            
            <div class="current-month">
                <h2>{{ $currentMonth->format('F Y') }}</h2>
                <a href="{{ route('transactions.calendar') }}" class="today-btn">
                    <i class="fas fa-calendar-day"></i> Today
                </a>
            </div>
            
            <a href="{{ route('transactions.calendar', ['month' => $nextMonth->format('Y-m')]) }}" class="nav-btn">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>

        <div class="calendar-container">
            <div class="calendar-grid">
                <div class="calendar-header">Sun</div>
                <div class="calendar-header">Mon</div>
                <div class="calendar-header">Tue</div>
                <div class="calendar-header">Wed</div>
                <div class="calendar-header">Thu</div>
                <div class="calendar-header">Fri</div>
                <div class="calendar-header">Sat</div>
                
                @for($i = 0; $i < $startDayOfWeek; $i++)
                    <div class="calendar-day empty"></div>
                @endfor
                
                @for($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $date = $currentMonth->copy()->day($day);
                        $dateKey = $date->format('Y-m-d');
                        $dayTransactions = $transactionsByDate[$dateKey] ?? [];
                        $isToday = $date->isToday();
                    @endphp
                    
                    <div class="calendar-day {{ $isToday ? 'today' : '' }} {{ count($dayTransactions) > 0 ? 'has-transactions' : '' }}"
                         data-date="{{ $dateKey }}"
                         onclick="showTransactions('{{ $dateKey }}')">
                        <div class="day-number">{{ $day }}</div>
                        
                        @if(count($dayTransactions) > 0)
                            <div class="transaction-indicator"></div>
                        @endif
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <div class="details-section">
        <div class="details-header">
            <h3 id="detailsTitle">{{ $currentMonth->format('F Y') }} Overview</h3>
            <button id="clearSelection" class="btn-clear" style="display: none;">
                <i class="fas fa-times"></i> Clear Selection
            </button>
        </div>
        
        <div class="details-content" id="detailsContent">
            <div class="month-stats">
                @php
                    $monthIncome = collect($transactionsByDate)->flatten(1)->where('type', 'income')->sum('amount');
                    $monthExpense = collect($transactionsByDate)->flatten(1)->where('type', 'expense')->sum('amount');
                    $monthTotal = count(collect($transactionsByDate)->flatten(1));
                @endphp
                
                <div class="stat-card income">
                    <div class="stat-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total Income</div>
                        <div class="stat-value">
                            @if($currentCurrency == 'IDR')
                                Rp {{ number_format($monthIncome, 0, ',', '.') }}
                            @else
                                $ {{ number_format($monthIncome / $exchangeRate['rate'], 2, '.', ',') }}
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="stat-card expense">
                    <div class="stat-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total Expense</div>
                        <div class="stat-value">
                            @if($currentCurrency == 'IDR')
                                Rp {{ number_format($monthExpense, 0, ',', '.') }}
                            @else
                                $ {{ number_format($monthExpense / $exchangeRate['rate'], 2, '.', ',') }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="transactions-list">
                <h4>Recent Transactions</h4>
                @php
                    $recentTransactions = collect($transactionsByDate)->flatten(1)->sortByDesc('created_at')->take(5);
                @endphp
                
                @if($recentTransactions->count() > 0)
                    <div id="transactionsList">
                        @foreach($recentTransactions as $transaction)
                            <div class="detail-transaction {{ $transaction->type }}">
                                <div class="dt-icon-wrapper">
                                    <div class="dt-icon">
                                        <i class="fas fa-{{ $transaction->type == 'income' ? 'arrow-down' : 'arrow-up' }}"></i>
                                    </div>
                                </div>
                                <div class="dt-content">
                                    <div class="dt-header">
                                        <span class="dt-desc">{{ $transaction->description }}</span>
                                        <div class="dt-amount {{ $transaction->type === 'income' ? 'text-success' : 'text-danger' }}">
                                            @if($currentCurrency == 'IDR')
                                                Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                            @else
                                                $ {{ number_format($transaction->amount / $exchangeRate['rate'], 2, '.', ',') }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="dt-footer">
                                        <span class="dt-date">{{ $transaction->created_at->format('M d, Y') }}</span>
                                        <div class="dt-meta">
                                            @if($transaction->category)
                                                <span class="dt-category">{{ $transaction->category }}</span>
                                            @endif
                                            <div class="dt-actions">
                                                @if($transaction->receipt_image_url)
                                                    <a href="{{ Storage::url($transaction->receipt_image_url) }}" target="_blank" class="dt-action-btn" title="View Receipt">
                                                        <i class="fas fa-paperclip"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ route('transactions.edit', $transaction->id) }}" class="dt-action-btn" title="Edit">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    @if($monthTotal > 5)
                        <div class="load-more-container">
                            <button id="loadMoreBtn" class="btn-secondary-custom w-100 justify-content-center">
                                <i class="fas fa-chevron-down"></i> Load More
                            </button>
                        </div>
                    @endif
                @else
                    <p class="no-transactions">No transactions this month</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="text-center mt-4">
    <a href="{{ route('dashboard') }}" class="btn-secondary-custom" style="width: 100%; justify-content: center;">
        <i class="fas fa-arrow-left"></i>
        <span>{{ __('index_btn_back') }}</span>
    </a>
</div>

<script>
    const transactionsByDate = @json($transactionsByDate);
    const currentCurrency = '{{ $currentCurrency }}';
    const exchangeRate = {{ $exchangeRate['rate'] }};
    let currentDisplayCount = 5;
    let selectedDate = null;
    
    function showTransactions(date) {
        selectedDate = date;
        const transactions = transactionsByDate[date] || [];
        const detailsTitle = document.getElementById('detailsTitle');
        const detailsContent = document.getElementById('detailsContent');
        const clearButton = document.getElementById('clearSelection');
        
        document.querySelectorAll('.calendar-day').forEach(day => {
            day.classList.remove('selected');
        });
        document.querySelector(`[data-date="${date}"]`).classList.add('selected');
        
        clearButton.style.display = 'flex';
        
        const dateObj = new Date(date);
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        detailsTitle.textContent = dateObj.toLocaleDateString('en-US', options);
        
        let html = '<div class="transactions-list"><h4>Transactions on this day</h4>';
        
        if (transactions.length === 0) {
            html += '<p class="no-transactions">No transactions on this day</p>';
        } else {
            html += '<div id="transactionsList">';
            const displayTransactions = transactions.slice(0, currentDisplayCount);
            displayTransactions.forEach(transaction => {
                html += buildTransactionHTML(transaction);
            });
            html += '</div>';
            
            if (transactions.length > currentDisplayCount) {
                html += `
                    <div class="load-more-container">
                        <button id="loadMoreBtn" class="btn-secondary-custom w-100 justify-content-center" onclick="loadMoreTransactions()">
                            <i class="fas fa-chevron-down"></i> Load More
                        </button>
                    </div>
                `;
            }
        }
        
        html += '</div>';
        detailsContent.innerHTML = html;
        currentDisplayCount = 5;
    }
    
    function buildTransactionHTML(transaction) {
        const amount = currentCurrency === 'IDR' 
            ? 'Rp ' + new Intl.NumberFormat('id-ID').format(transaction.amount)
            : '$ ' + new Intl.NumberFormat('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(transaction.amount / exchangeRate);
        
        const iconClass = transaction.type === 'income' ? 'arrow-down' : 'arrow-up';
        const dateStr = new Date(transaction.created_at).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'});
        
        // Build receipt button HTML if URL exists
        const receiptHtml = transaction.receipt_image_url 
            ? `<a href="/storage/${transaction.receipt_image_url}" target="_blank" class="dt-action-btn" title="View Receipt"><i class="fas fa-paperclip"></i></a>` 
            : '';
            
        return `
            <div class="detail-transaction ${transaction.type}">
                <div class="dt-icon-wrapper">
                    <div class="dt-icon">
                        <i class="fas fa-${iconClass}"></i>
                    </div>
                </div>
                <div class="dt-content">
                    <div class="dt-header">
                        <span class="dt-desc">${transaction.description}</span>
                        <div class="dt-amount ${transaction.type === 'income' ? 'text-success' : 'text-danger'}">
                            ${amount}
                        </div>
                    </div>
                    <div class="dt-footer">
                        <span class="dt-date">${dateStr}</span>
                        <div class="dt-meta">
                            ${transaction.category ? `<span class="dt-category">${transaction.category}</span>` : ''}
                            <div class="dt-actions">
                                ${receiptHtml}
                                <a href="/transactions/${transaction.id}/edit" class="dt-action-btn" title="Edit">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    function loadMoreTransactions() {
        currentDisplayCount += 5;
        
        if (selectedDate) {
            const transactions = transactionsByDate[selectedDate] || [];
            const transactionsList = document.getElementById('transactionsList');
            const displayTransactions = transactions.slice(0, currentDisplayCount);
            let html = '';
            displayTransactions.forEach(transaction => {
                html += buildTransactionHTML(transaction);
            });
            transactionsList.innerHTML = html;
            
            if (currentDisplayCount >= transactions.length) {
                const loadMoreBtn = document.getElementById('loadMoreBtn');
                if (loadMoreBtn) loadMoreBtn.parentElement.remove();
            }
        } else {
            const allTransactions = Object.values(transactionsByDate).flat().sort((a, b) => 
                new Date(b.created_at) - new Date(a.created_at)
            );
            const transactionsList = document.getElementById('transactionsList');
            const displayTransactions = allTransactions.slice(0, currentDisplayCount);
            let html = '';
            displayTransactions.forEach(transaction => {
                html += buildTransactionHTML(transaction);
            });
            transactionsList.innerHTML = html;
            
            if (currentDisplayCount >= allTransactions.length) {
                const loadMoreBtn = document.getElementById('loadMoreBtn');
                if (loadMoreBtn) loadMoreBtn.parentElement.remove();
            }
        }
    }
    
    function clearSelection() {
        selectedDate = null;
        currentDisplayCount = 5;
        document.querySelectorAll('.calendar-day').forEach(day => {
            day.classList.remove('selected');
        });
        document.getElementById('clearSelection').style.display = 'none';
        location.reload();
    }
    
    document.getElementById('clearSelection').addEventListener('click', clearSelection);
    
    const initialLoadMoreBtn = document.getElementById('loadMoreBtn');
    if (initialLoadMoreBtn) {
        initialLoadMoreBtn.addEventListener('click', loadMoreTransactions);
    }
</script>
@endsection