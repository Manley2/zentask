<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Zentask') }} - Dashboard</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-cover bg-center bg-no-repeat min-h-screen"
      style="background-image: url('{{ asset('images/dashboard-bg.png') }}');">

    <!-- Overlay gelap untuk readability yang lebih baik -->
    <div class="fixed inset-0 bg-gradient-to-br from-slate-900/40 via-blue-900/30 to-slate-900/40 backdrop-blur-[2px] -z-10"></div>


    <!-- Navigation Bar -->
    <nav class="dashboard-nav">
        <div class="nav-container">
            <!-- Logo -->
            <div class="nav-logo">
                <h1 class="text-2xl font-bold text-white tracking-tight">Zentask<span class="text-blue-400">.</span></h1>
            </div>

            <!-- Navigation Links -->
            <div class="nav-links">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="#" class="nav-link">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span>Tasks</span>
                </a>

                <a href="#" class="nav-link">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Calendar</span>
                </a>
            </div>

            <!-- User Menu -->
            <div class="nav-user">
                <div class="user-info">
                    <span class="user-name">{{ Auth::user()->name }}</span>
                    <span class="user-email">{{ Auth::user()->email }}</span>
                </div>
                <div class="user-avatar">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>

                <!-- Logout -->
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="logout-btn" title="Logout">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="content-container">
            @yield('content')
        </div>
    </main>

</body>
</html>

<style>
    /* Navigation Bar - Style Premium */
    .dashboard-nav {
        background: rgba(15, 23, 42, 0.8);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding: 1rem 0;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    }

    .nav-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 2rem;
    }

    .nav-logo {
        flex-shrink: 0;
    }

    .nav-links {
        display: flex;
        gap: 0.5rem;
        flex: 1;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        border-radius: 12px;
        font-weight: 500;
        transition: all 0.3s ease;
        font-size: 0.938rem;
        position: relative;
    }

    .nav-link::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(96, 165, 250, 0.1), rgba(59, 130, 246, 0.1));
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .nav-link:hover {
        color: white;
        transform: translateY(-2px);
    }

    .nav-link:hover::before {
        opacity: 1;
    }

    .nav-link.active {
        background: linear-gradient(135deg, rgba(96, 165, 250, 0.25), rgba(59, 130, 246, 0.25));
        color: #60a5fa;
        border: 1px solid rgba(96, 165, 250, 0.4);
        box-shadow: 0 4px 12px rgba(96, 165, 250, 0.3);
    }

    /* User Menu */
    .nav-user {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .user-info {
        display: none;
        flex-direction: column;
        align-items: flex-end;
    }

    @media (min-width: 768px) {
        .user-info {
            display: flex;
        }
    }

    .user-name {
        color: white;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .user-email {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.75rem;
    }

    .user-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, #60a5fa, #3b82f6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 1.125rem;
        box-shadow: 0 4px 16px rgba(96, 165, 250, 0.5);
        border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .logout-btn {
        padding: 0.625rem;
        color: rgba(255, 255, 255, 0.7);
        background: transparent;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .logout-btn:hover {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
        transform: scale(1.05);
    }

    /* Main Content */
    .dashboard-content {
        padding: 2.5rem 0;
        min-height: calc(100vh - 80px);
    }

    .content-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 2rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .nav-links {
            display: none;
        }

        .content-container {
            padding: 0 1rem;
        }

        .nav-container {
            padding: 0 1rem;
        }
    }
</style>
