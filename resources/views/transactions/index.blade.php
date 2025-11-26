<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <title>Flux Dashboard</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .btn { padding: 5px 10px; text-decoration: none; background: #eee; border: 1px solid #ccc; }
        .danger { color: red; }
    </style>
</head>
<body>
    <h1>Flux - {{ __('Dashboard') }}</h1>

    <p>
        Language: 
        <a href="{{ route('lang.switch', 'en') }}">English</a> | 
        <a href="{{ route('lang.switch', 'id') }}">Bahasa Indonesia</a>
    </p>
    
        
        <div style="margin-bottom: 20px;">
            <p>{{ __('Welcome') }}, {{ Auth::user()->name }}</p>
            
            <a href="{{ route('transactions.create') }}" class="btn">+ {{ __('Add Transaction') }}</a>
            
            <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit">{{ __('Logout') }}</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Amount') }}</th> <th>{{ __('Receipt') }}</th>
                    <th>{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $t)
                <tr>
                    <td>{{ $t->created_at->format('Y-m-d') }}</td>
                    <td>{{ $t->description }}</td>
                    <td>{{ ucfirst($t->type) }}</td>
                    
                    <td>
                        @if(app()->getLocale() == 'id')
                            Rp {{ number_format($t->amount, 0, ',', '.') }}
                        @else
                            $ {{ number_format($t->amount, 2, '.', ',') }}
                        @endif
                    </td>

                    <td>
                        @if($t->receipt_image_url)
                            <a href="{{ asset('storage/' . $t->receipt_image_url) }}" target="_blank">
                                {{ __('View Receipt') }}
                            </a>
                        @else
                            {{ __('No Receipt') }}
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('transactions.destroy', $t->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="danger">{{ __('Delete') }}</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
</body>
</html>