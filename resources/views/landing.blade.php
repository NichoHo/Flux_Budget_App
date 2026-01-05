<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flux - {{ __('hero_title') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
       .hero {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(13, 148, 136, 0.85) 100%), 
                        url("{{ asset('images/finance.jpg') }}");
            background-size: cover;
            background-position: center;
       }
       .dark-mode .hero {
            background: linear-gradient(135deg, rgba(2, 6, 23, 0.95) 0%, rgba(17, 94, 89, 0.9) 100%),
                        url("{{ asset('images/finance.jpg') }}");
            background-size: cover;
            background-position: center;
       }
       .cta {
            background: linear-gradient(135deg, rgba(13, 148, 136, 0.9) 0%, rgba(6, 182, 212, 0.85) 100%), 
                        url("{{ asset('images/graph.webp') }}");
            background-size: cover;
            background-position: center;
       }
       .dark-mode .cta {
            background: linear-gradient(135deg, rgba(15, 118, 110, 0.95) 0%, rgba(8, 145, 178, 0.9) 100%),
                        url("{{ asset('images/graph.webp') }}");
            background-size: cover;
            background-position: center;
       }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="{{ asset('images/logo.png') }}" 
                    alt="Flux Logo" 
                >
                <span style="font-family: 'Outfit', sans-serif; font-weight: 700; letter-spacing: -0.02em; font-size: 1.5rem;">flux</span>
            </a>

            <div class="d-none d-lg-block">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">{{ __('nav_home') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">{{ __('nav_features') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">{{ __('nav_how') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#faq">FAQ</a>
                    </li>
                </ul>
            </div>

            <div class="d-flex align-items-center">
                <a href="{{ route('lang.switch', app()->getLocale() == 'en' ? 'id' : 'en') }}" class="lang-toggle" title="Switch Language">
                    <i class="fas fa-globe"></i>
                </a>
                
                <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">
                    <i class="fas fa-cloud-moon"></i>
                </button>

                <div class="divider d-md-block"></div>

                @auth
                    <a class="btn-primary-custom btn-nav ms-0 ms-md-2" href="{{ route('transactions.index') }}">{{ __('nav_dashboard') }}</a>
                @else
                    <a class="btn-primary-custom btn-nav ms-0 ms-md-2" href="{{ route('register') }}">{{ __('nav_signup') }}</a>
                    <a class="btn-login-custom btn-nav ms-2 d-md-inline-block" href="{{ route('login') }}">{{ __('nav_login') }}</a>
                @endauth
            </div>
        </div>
    </nav>

    <section class="hero" id="home">
        <div class="container hero-container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1>{{ __('hero_title') }}</h1>
                    <p>{{ __('hero_subtitle') }}</p>
                    
                    <div class="hero-buttons">
                        <a href="{{ route('register') }}" class="btn-primary-custom">{{ __('hero_cta') }}</a>
                        <a href="#how-it-works" class="btn-secondary-custom">{{ __('hero_secondary') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="features section-padding" id="features">
        <div class="container">
            <h2 class="section-title">{{ __('features_title') }}</h2>
            <div class="section-divider"></div>

            <ul class="nav nav-pills feature-nav justify-content-center mb-5" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="pills-f1-tab" data-bs-toggle="pill" data-bs-target="#pills-f1" type="button" role="tab" aria-controls="pills-f1" aria-selected="true">
                        <i class="fas fa-chart-pie me-2"></i> {{ __('feature1_title') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pills-f2-tab" data-bs-toggle="pill" data-bs-target="#pills-f2" type="button" role="tab" aria-controls="pills-f2" aria-selected="false">
                        <i class="fas fa-wallet me-2"></i> {{ __('feature2_title') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pills-f3-tab" data-bs-toggle="pill" data-bs-target="#pills-f3" type="button" role="tab" aria-controls="pills-f3" aria-selected="false">
                        <i class="fas fa-calendar-check me-2"></i> {{ __('feature3_title') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pills-f4-tab" data-bs-toggle="pill" data-bs-target="#pills-f4" type="button" role="tab" aria-controls="pills-f4" aria-selected="false">
                        <i class="fas fa-money-bill-wave me-2"></i> {{ __('feature4_title') }}
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-f1" role="tabpanel" aria-labelledby="pills-f1-tab" tabindex="0">
                    <div class="tab-container row align-items-center gy-5">
                        <div class="col-lg-6 order-lg-1 position-relative">
                            <div class="feature-image-blob"></div>
                            <img src="{{ asset('images/analytics.jpg') }}" alt="Analytics Feature" class="img-fluid feature-detailed-img rounded-4 shadow-lg position-relative z-1">
                        </div>
                        <div class="col-lg-6 order-lg-2">
                            <h3 class="feature-heading display-6 fw-bold mb-4">{{ __('feature1_title') }}</h3>
                            <p class="feature-text lead mb-4">{{ __('feature1_desc') }}</p>
                            <a href="{{ route('register') }}" class="btn btn-feature">{{ __('hero_cta') }}</a>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="pills-f2" role="tabpanel" aria-labelledby="pills-f2-tab" tabindex="0">
                    <div class="row align-items-center gy-5">
                        <div class="col-lg-6 order-lg-1 position-relative">
                            <div class="feature-image-blob"></div>
                            <img src="{{ asset('images/phone2.jpg') }}" alt="Budget Feature" class="img-fluid feature-detailed-img rounded-4 shadow-lg position-relative z-1">
                        </div>
                        <div class="col-lg-6 order-lg-2">
                            <h3 class="feature-heading display-6 fw-bold mb-4">{{ __('feature2_title') }}</h3>
                            <p class="feature-text lead mb-4">{{ __('feature2_desc') }}</p>
                            <a href="{{ route('register') }}" class="btn btn-feature">{{ __('hero_cta') }}</a>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="pills-f3" role="tabpanel" aria-labelledby="pills-f3-tab" tabindex="0">
                    <div class="row align-items-center gy-5">
                        <div class="col-lg-6 order-lg-1 position-relative">
                            <div class="feature-image-blob"></div>
                            <img src="{{ asset('images/phone3.jpg') }}" alt="Recurring Feature" class="img-fluid feature-detailed-img rounded-4 shadow-lg position-relative z-1">
                        </div>
                        <div class="col-lg-6 order-lg-2">
                            <h3 class="feature-heading display-6 fw-bold mb-4">{{ __('feature3_title') }}</h3>
                            <p class="feature-text lead mb-4">{{ __('feature3_desc') }}</p>
                            <a href="{{ route('register') }}" class="btn btn-feature">{{ __('hero_cta') }}</a>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="pills-f4" role="tabpanel" aria-labelledby="pills-f4-tab" tabindex="0">
                    <div class="row align-items-center gy-5">
                        <div class="col-lg-6 order-lg-1 position-relative">
                            <div class="feature-image-blob"></div>
                            <img src="{{ asset('images/conversation.jpg') }}" alt="Currency Feature" class="img-fluid feature-detailed-img rounded-4 shadow-lg position-relative z-1">
                        </div>
                        <div class="col-lg-6 order-lg-2">
                            <h3 class="feature-heading display-6 fw-bold mb-4">{{ __('feature4_title') }}</h3>
                            <p class="feature-text lead mb-4">{{ __('feature4_desc') }}</p>
                            <a href="{{ route('register') }}" class="btn btn-feature">{{ __('hero_cta') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <h2 class="section-title">{{ __('how_title') }}</h2>
            <div class="section-divider"></div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h3>{{ __('step1_title') }}</h3>
                        <p>{{ __('step1_desc') }}</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h3>{{ __('step2_title') }}</h3>
                        <p>{{ __('step2_desc') }}</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h3>{{ __('step3_title') }}</h3>
                        <p>{{ __('step3_desc') }}</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <h3>{{ __('step4_title') }}</h3>
                        <p>{{ __('step4_desc') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="features" id="faq">
        <div class="container">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <div class="section-divider"></div>
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="faq-image">
                        <img src="{{ asset('images/phone.jpg') }}" alt="Finance FAQ Illustration" class="img-fluid rounded">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Is Flux free to use?
                                </button>
                            </h3>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, the core features of Flux including transaction tracking, receipt uploads, and basic analytics are completely free. We aim to provide accessible financial management tools for everyone.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    What currencies does Flux support?
                                </button>
                            </h3>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Flux is designed for global use with full localization support. We currently support major currencies including <strong>USD ($)</strong> and <strong>IDR (Rp)</strong>. The system automatically handles conversions.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Can I access Flux on my mobile device?
                                </button>
                            </h3>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Absolutely! Flux is fully responsive and works perfectly on mobile devices, tablets, and desktops. You can access your financial data anytime, anywhere.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    What analytics features do you offer?
                                </button>
                            </h3>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Flux provides spending categorization, monthly trend analysis, expense vs income comparisons, and visual charts to help you understand your financial habits. Premium analytics with advanced insights are coming soon.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <h2>{{ __('cta_title') }}</h2>
            <p>{{ __('cta_subtitle') }}</p>
            <a href="{{ route('register') }}" class="btn-cta">{{ __('cta_button') }}</a>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p class="mb-0">{{ __('footer_text') }}</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark mode
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

        // --- UPDATED: Navbar Smart Scroll (Hide down, Show up) ---
        const navbar = document.querySelector('.navbar');
        let lastScrollTop = 0;
        
        window.addEventListener('scroll', () => {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Prevent negative scrolling values (e.g. mobile rubber-banding)
            if (scrollTop < 0) scrollTop = 0;

            // 1. Handle Background styling (Existing logic)
            if (scrollTop > 20) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }

            // 2. Handle Hide/Show logic
            // If we are scrolling DOWN and we are past the initial top area (e.g. 60px)
            if (scrollTop > lastScrollTop && scrollTop > 60) {
                navbar.classList.add('navbar-hidden');
            } else {
                // We are scrolling UP
                navbar.classList.remove('navbar-hidden');
            }
            
            lastScrollTop = scrollTop;
        });

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offset = 80;
                    const targetPosition = target.offsetTop - offset;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>