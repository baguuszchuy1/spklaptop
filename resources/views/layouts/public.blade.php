<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('brand.name'))</title>
    <meta name="description" content="{{ config('brand.tagline') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col" x-data="{ menuOpen: false }">

    @php
        $links = [
            ['route' => 'public.home', 'label' => 'Beranda'],
            ['route' => 'public.recommendation', 'label' => 'Rekomendasi'],
            ['route' => 'public.catalog', 'label' => 'Katalog Laptop'],
        ];
    @endphp

    <header class="sticky top-0 z-40 bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 md:px-6 flex items-center justify-between h-16">
            <a href="{{ route('public.home') }}" class="flex items-center gap-2.5">
                @if (config('brand.logo'))
                    <img src="{{ asset(config('brand.logo')) }}" alt="{{ config('brand.name') }}" class="w-9 h-9 rounded-xl object-cover">
                @else
                    <span class="w-9 h-9 rounded-xl bg-[#7a1220] flex items-center justify-center text-xs font-bold text-white">{{ config('brand.badge') }}</span>
                @endif
                <span class="font-bold text-slate-800 text-sm leading-tight">{{ config('brand.name') }}</span>
            </a>

            <!-- Menu desktop -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                @foreach ($links as $link)
                    <a href="{{ route($link['route']) }}"
                       class="{{ request()->routeIs($link['route']) ? 'text-[#7a1220]' : 'text-slate-600 hover:text-[#7a1220]' }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach
                <a href="{{ route('login') }}"
                   class="border border-[#7a1220] text-[#7a1220] px-4 py-1.5 rounded-lg hover:bg-[#7a1220] hover:text-white transition">
                    Login Admin
                </a>
            </nav>

            <button @click="menuOpen = !menuOpen" class="md:hidden p-2 text-slate-600" aria-label="Menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <!-- Menu mobile -->
        <div x-show="menuOpen" x-cloak class="md:hidden border-t border-slate-100 px-4 py-3 space-y-1 bg-white">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}" class="block text-sm font-medium text-slate-600 py-2">{{ $link['label'] }}</a>
            @endforeach
            <a href="{{ route('login') }}" class="block text-sm font-semibold text-[#7a1220] py-2">Login Admin</a>
        </div>
    </header>

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white mt-16">
        <div class="max-w-6xl mx-auto px-4 md:px-6 py-8 text-sm text-slate-500 flex flex-col md:flex-row items-center justify-between gap-3">
            <p>&copy; {{ date('Y') }} {{ config('brand.name') }}. Dihitung dengan metode ROC dan SAW.</p>
            <a href="{{ route('login') }}" class="text-[#7a1220] font-medium hover:underline">Masuk sebagai Admin</a>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
