<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('login_title') }} - Flux</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar fixed-top">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">
                <img src="{{ asset('images/logo.png') }}" 
                    alt="Flux Logo" 
                    style="height: 30px; width: auto; margin-right: 8px; vertical-align: middle; padding-bottom: 3px;">
                Flux
            </a>
            <div class="d-flex align-items-center">
                <a href="{{ route('lang.switch', app()->getLocale() == 'en' ? 'id' : 'en') }}" class="lang-toggle" title="Switch Language">
                    <i class="fas fa-globe"></i>
                </a>
                <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
    </nav>

    <section class="login-section">
        <div class="container">
            <div class="login-container">
                <div class="login-card">
                    <div class="login-header">
                        <h1>{{ __('login_title') }}</h1>
                        <p>{{ __('login_subtitle') }}</p>
                    </div>

                    @if(session('success'))
                        <div class="alert-custom alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('login') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('label_email') }}</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email"
                                   value="{{ old('email') }}" 
                                   required 
                                   autofocus>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('label_password') }}</label>
                            <div class="password-input-wrapper">
                                <input type="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       id="password" 
                                       name="password"
                                       required>
                                <button type="button" class="password-toggle" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-options">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                                <label class="form-check-label" for="rememberMe">
                                    {{ __('remember_me') }}
                                </label>
                            </div>
                            <a href="#" class="forgot-password">{{ __('forgot_password') }}</a>
                        </div>

                        <button type="submit" class="btn-primary-custom">{{ __('btn_login') }}</button>
                    </form>

                    <div class="divider">
                        <span>{{ __('divider_or') }}</span>
                    </div>

                    <button class="btn-social">
                        <i class="fab fa-google"></i>
                        <span>{{ __('btn_google') }}</span>
                    </button>

                    <button class="btn-social">
                        <i class="fab fa-github"></i>
                        <span>{{ __('btn_github') }}</span>
                    </button>

                    <div class="login-footer">
                        <span>{{ __('login_footer_text') }}</span>
                        <a href="{{ route('register') }}">{{ __('login_footer_link') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark mode logic
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;
        const isDark = localStorage.getItem('darkMode') === 'true';

        if (isDark) {
            body.classList.add('dark-mode');
            themeToggle.innerHTML = '<i class="fas fa-cloud-sun"></i>';
        }

        themeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            const isDarkMode = body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDarkMode);
            themeToggle.innerHTML = isDarkMode ? '<i class="fas fa-cloud-sun"></i>' : '<i class="fas fa-cloud-moon"></i>';
        });

        // Password toggle logic
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', () => {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                togglePassword.querySelector('i').classList.toggle('fa-eye');
                togglePassword.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }
    </script>
</body>
</html>