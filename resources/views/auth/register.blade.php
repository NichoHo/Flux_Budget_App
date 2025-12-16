<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('signup_title') }} - Flux</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                <img src="{{ asset('images/logo.png') }}" 
                    alt="Flux Logo" 
                    style="height: 32px; width: auto; margin-right: 10px;">
                <span style="font-family: 'Outfit', sans-serif; font-weight: 700; letter-spacing: -0.02em; font-size: 1.5rem;">flux</span>
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

    <section class="signup-section">
        <div class="container">
            <div class="signup-container">
                <div class="signup-card">
                    <div class="signup-header">
                        <h1>{{ __('signup_title') }}</h1>
                        <p>{{ __('signup_subtitle') }}</p>
                    </div>

                    <form action="{{ route('register') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="fullName" class="form-label">{{ __('label_name') }}</label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="fullName" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required 
                                   autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('label_email') }}</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   required>
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

                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">{{ __('label_confirm') }}</label>
                            <div class="password-input-wrapper">
                                <input type="password" 
                                       class="form-control" 
                                       id="confirmPassword" 
                                       name="password_confirmation" 
                                       required>
                                <button type="button" class="password-toggle" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="language" class="form-label">{{ __('label_language') }}</label>
                            <select class="form-select @error('preferred_language') is-invalid @enderror" id="language" name="preferred_language">
                                <option value="en" {{ old('preferred_language') == 'en' ? 'selected' : '' }}>English</option>
                                <option value="id" {{ old('preferred_language') == 'id' ? 'selected' : '' }}>Bahasa Indonesia</option>
                            </select>
                            @error('preferred_language')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn-primary-custom">{{ __('btn_signup') }}</button>
                    </form>
                    <div class="divider">
                        <span>{{ __('divider_or') }}</span>
                    </div>

                    <a href="#" class="btn-social">
                        <i class="fab fa-google"></i>
                        <span>{{ __('btn_google') }}</span>
                    </a>

                    <a href="#" class="btn-social">
                        <i class="fab fa-github"></i>
                        <span>{{ __('btn_github') }}</span>
                    </a>

                    <div class="signup-footer">
                        <span>{{ __('signup_footer_text') }}</span>
                        <a href="{{ route('login') }}">{{ __('signup_footer_link') }}</a>
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
        function setupPasswordToggle(toggleId, inputId) {
            const toggle = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            
            if (toggle && input) {
                toggle.addEventListener('click', () => {
                    const type = input.type === 'password' ? 'text' : 'password';
                    input.type = type;
                    toggle.querySelector('i').classList.toggle('fa-eye');
                    toggle.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }
        }

        setupPasswordToggle('togglePassword', 'password');
        setupPasswordToggle('toggleConfirmPassword', 'confirmPassword');
    </script>
</body>
</html>