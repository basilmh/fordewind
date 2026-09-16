<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title') | Fordewind</title>
        @vite(['resources/css/app.css'])
        @stack('scripts')
    </head>
    <body>
        <div class="site-shell">
            <header class="site-header">
                <a class="brand" href="{{ route('vote') }}" aria-label="Fordewind, голосование">
                    <span class="brand-mark">F</span>
                    <span>Fordewind</span>
                </a>
                <nav class="site-nav" aria-label="Основная навигация">
                    <a class="{{ request()->routeIs('vote') ? 'is-active' : '' }}" href="{{ route('vote') }}">Голосование</a>
                    <a class="{{ request()->routeIs('statistics') ? 'is-active' : '' }}" href="{{ route('statistics') }}">Статистика</a>
                </nav>
            </header>

            <main>
                @yield('content')
            </main>
        </div>
    </body>
</html>
