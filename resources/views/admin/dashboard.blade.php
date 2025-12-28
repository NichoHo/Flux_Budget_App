@extends('app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="container py-4">
    <h2 class="fw-bold mb-4">Admin Overview</h2>
    
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-3">
                <h6 class="text-secondary">Total Registered Users</h6>
                <h3 class="fw-bold mb-0">{{ $totalUsers }}</h3>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-3">
                <h6 class="text-secondary">Total Transactions Logged</h6>
                <h3 class="fw-bold mb-0">{{ $totalTransactions }}</h3>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">Recent Users</h5>
        </div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentUsers as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->created_at->format('d M Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection