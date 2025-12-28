<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Flux')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    @yield('styles')

    @php
        if (!session()->has('currency')) {
            $defaultCurrency = app()->getLocale() == 'id' ? 'IDR' : 'USD';
            session(['currency' => $defaultCurrency]);
        }
        $currentCurrency = session('currency', 'IDR');
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('darkMode') === 'true') {
                document.body.classList.add('dark-mode');
            }
        });
    </script>
</head>
<body>
    <div class="dashboard-container">
        @include('partials.sidebar')

        <main class="main-content">
            @yield('content')
        </main>
    </div>

    <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm"> 
            <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem; overflow: hidden;">
                <div class="modal-body p-4 text-center">
                    
                    @if(session('success'))
                        <div class="mb-3">
                            <div class="mb-3 text-success">
                                <i class="fas fa-check-circle" style="font-size: 3.5rem;"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Success!</h5>
                            <p class="text-secondary mb-0" style="font-size: 0.95rem;">
                                {{ session('success') }}
                            </p>
                        </div>
                    @endif

                    @if(session('budget_alert'))
                        <div class="alert alert-warning d-flex align-items-start text-start mt-4 mb-0" role="alert" style="border-radius: 0.75rem; border: 1px solid #fde047; background-color: #fefce8; color: #854d0e;">
                            <i class="fas fa-exclamation-triangle mt-1 me-2 flex-shrink-0"></i>
                            <div style="font-size: 0.9rem; line-height: 1.4;">
                                {{ session('budget_alert') }}
                            </div>
                        </div>
                    @endif
                    
                    @if($errors->any())
                        <div class="alert alert-danger text-start mt-3 mb-0">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mt-4">
                        <button type="button" class="btn btn-primary-custom w-100 justify-content-center" data-bs-dismiss="modal">
                            Okay, Got it
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success') || session('budget_alert') || $errors->any())
                var myModal = new bootstrap.Modal(document.getElementById('statusModal'));
                myModal.show();
            @endif
        });
    </script>

    @yield('scripts')
</body>
</html>