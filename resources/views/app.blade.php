<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Flux')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    @yield('styles')

    <!-- Initialize currency session -->
    @php
        // Initialize currency session if not set
        if (!session()->has('currency')) {
            $defaultCurrency = app()->getLocale() == 'id' ? 'IDR' : 'USD';
            session(['currency' => $defaultCurrency]);
        }
        
        // Share with all views
        $currentCurrency = session('currency', 'IDR');
    @endphp

    <!-- Apply Dark Mode Immediately to prevent flash -->
    <script>
        // Check for dark mode preference on page load
        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('darkMode') === 'true') {
                document.body.classList.add('dark-mode');
            }
        });
    </script>
</head>
<body>
    <!-- Removed the inline class condition since it was causing the error -->
    <!-- Dark mode class will be added via JavaScript above -->

    <div class="dashboard-container">
        <!-- Include Sidebar Partial -->
        @include('partials.sidebar')

        <!-- Main Content Area -->
        <main class="main-content">
            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>