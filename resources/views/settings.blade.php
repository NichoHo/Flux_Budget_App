@extends('app')

@section('title', __('menu_settings'))

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/settings.css') }}">
@endsection

@section('content')

<div class="page-header">
    <h1>{{ __('menu_settings') }}</h1>
    <p>{{ __('settings_subtitle') ?? 'Manage your account preferences' }}</p>
</div>

<div class="settings-card">
    <h3>{{ __('settings_appearance') ?? 'Appearance & Language' }}</h3>
    
    <!-- Language Selection -->
    <div class="settings-row">
        <div class="settings-label">
            <h4>{{ __('label_language') ?? 'Language' }}</h4>
            <p>{{ __('desc_language') ?? 'Select your preferred language' }}</p>
        </div>
        <div style="width: 200px;">
            <select class="form-select" id="languageSelect">
                <option value="en" {{ app()->getLocale() == 'en' ? 'selected' : '' }}>English (US)</option>
                <option value="id" {{ app()->getLocale() == 'id' ? 'selected' : '' }}>Bahasa Indonesia</option>
            </select>
        </div>
    </div>

    <!-- Dark Mode Toggle -->
    <div class="settings-row">
        <div class="settings-label">
            <h4>{{ __('label_theme') ?? 'Dark Mode' }}</h4>
            <p>{{ __('desc_theme') ?? 'Switch between light and dark themes' }}</p>
        </div>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="darkModeSwitch" style="width: 3em; height: 1.5em; cursor: pointer;">
        </div>
    </div>
</div>

<div class="settings-card">
    <h3 class="text-danger">{{ __('settings_danger') ?? 'Account Actions' }}</h3>
    <div class="settings-row">
        <div class="settings-label">
            <h4>{{ __('btn_logout') }}</h4>
            <p>{{ __('desc_logout') ?? 'Sign out of your account' }}</p>
        </div>
        
        <!-- Replaced JS button with actual Laravel Form -->
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn-danger-custom" id="logoutBtnMain">
                <i class="fas fa-sign-out-alt"></i> <span>{{ __('btn_logout') }}</span>
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Dark Mode Handling (Syncs with app.blade.php)
        const body = document.body;
        const themeSwitch = document.getElementById('darkModeSwitch');
        let isDark = localStorage.getItem('darkMode') === 'true';

        // Set initial state of the switch based on what app.blade.php loaded
        if(themeSwitch) themeSwitch.checked = isDark;

        themeSwitch.addEventListener('change', (e) => {
            const isDarkMode = e.target.checked;
            
            if (isDarkMode) {
                body.classList.add('dark-mode');
            } else {
                body.classList.remove('dark-mode');
            }
            
            localStorage.setItem('darkMode', isDarkMode);
        });

        // Language Switch Handling
        const languageSelect = document.getElementById('languageSelect');
        if(languageSelect) {
            languageSelect.addEventListener('change', function() {
                const selectedLang = this.value;
                // Build the URL correctly with the locale parameter
                window.location.href = '{{ route("lang.switch", ["locale" => "__LOCALE__"]) }}'.replace('__LOCALE__', selectedLang);
            });
        }
    });
</script>
@endsection