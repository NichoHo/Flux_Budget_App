<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="{{ asset('images/logo.png') }}" 
            alt="Flux Logo" 
            style="height: 32px; width: auto; vertical-align: middle;">
        <span style="font-family: 'Outfit', sans-serif; font-weight: 700; letter-spacing: -0.02em; font-size: 1.5rem;">flux</span>
    </div>

    <ul class="sidebar-menu">
        <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a href="{{ route('dashboard') }}">
                <i class="fas fa-home"></i>
                <span>{{ __('menu_dashboard') }}</span>
            </a>
        </li>
        
        <li class="menu-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
            <a href="{{ route('transactions.index') }}">
                <i class="fas fa-exchange-alt"></i>
                <span>{{ __('menu_transactions') }}</span>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('recurring.*') ? 'active' : '' }}">
            <a href="{{ route('recurring.index') }}">
                <i class="fas fa-sync-alt"></i>
                <span>Recurring Bills</span>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('budget.*') ? 'active' : '' }}">
            <a href="{{ route('budget.index') }}">
                <i class="fas fa-wallet"></i>
                <span>{{ __('menu_budget') }}</span>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('analytics.*') ? 'active' : '' }}">
            <a href="{{ route('analytics') }}">
                <i class="fas fa-chart-pie"></i>
                <span>{{ __('menu_analytics') }}</span>
            </a>
        </li>
        
        <li class="menu-item {{ request()->routeIs('settings') ? 'active' : '' }}">
            <a href="{{ route('settings') }}">
                <i class="fas fa-cog"></i>
                <span>{{ __('menu_settings') }}</span>
            </a>
        </li>
        @if(auth()->user()->isAdmin())
        <li class="menu-item {{ request()->routeIs('admin.*') ? 'active' : '' }}">
            <a href="{{ route('admin.dashboard') }}">
                <i class="fas fa-user-shield"></i>
                <span>Admin Panel</span>
            </a>
        </li>
        @endif
    </ul>

    <div class="sidebar-footer">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn-logout-full">
                <i class="fas fa-sign-out-alt"></i> <span>{{ __('btn_logout') }}</span>
            </button>
        </form>
    </div>
</aside>