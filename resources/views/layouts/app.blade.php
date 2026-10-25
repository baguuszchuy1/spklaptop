<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SPK Pemilihan Merek Laptop')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800" x-data="{ sidebarOpen: false }">

    <!-- Top bar (mobile only) -->
    <header class="md:hidden sticky top-0 z-30 bg-[#3b0a10] text-white flex items-center justify-between px-4 py-3 shadow-md">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-[#8a1c2b] flex items-center justify-center text-xs font-bold">SL</span>
            <span class="font-bold text-sm">SPK Laptop</span>
        </a>
        <button @click="sidebarOpen = true" class="p-2 rounded-md hover:bg-white/10">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </header>

    <!-- Overlay (mobile) -->
    <div x-show="sidebarOpen" x-cloak
         class="fixed inset-0 bg-black/50 z-40 md:hidden"
         @click="sidebarOpen = false"></div>

    <div class="flex min-h-screen">
        <!-- Sidebar: sticky di desktop supaya tidak ikut ter-scroll hilang -->
        <aside
            x-cloak
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
            class="fixed md:sticky md:top-0 z-50 top-0 left-0 h-full md:h-screen w-72 md:w-64 flex-shrink-0 md:self-start
                   bg-gradient-to-b from-[#3b0a10] via-slate-950 to-slate-950 text-slate-100
                   transform transition-transform duration-200 ease-in-out flex flex-col">

            <div class="p-6 border-b border-white/10 flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 hover:opacity-80 transition">
                    <span class="w-9 h-9 rounded-xl bg-[#8a1c2b] flex items-center justify-center text-sm font-bold shadow-lg shadow-[#8a1c2b]/30">SL</span>
                    <div>
                        <h1 class="text-base font-bold leading-tight">SPK Laptop</h1>
                        <p class="text-[11px] text-slate-400">Metode ROC + SAW</p>
                    </div>
                </a>
                <button @click="sidebarOpen = false" class="md:hidden p-1 text-slate-400 hover:text-white">✕</button>
            </div>

            @include('partials.admin-nav')

            <div class="p-4 border-t border-white/10">
                <div class="flex items-center gap-3 px-2 py-2 mb-2">
                    <div class="w-8 h-8 rounded-full bg-[#8a1c2b] flex items-center justify-center text-xs font-bold">
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    </div>
                    <div class="text-xs">
                        <p class="font-medium text-white">{{ auth()->user()->name ?? 'Admin' }}</p>
                        <p class="text-slate-400">{{ auth()->user()->email ?? '' }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium hover:bg-red-500/10 text-red-300 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main content -->
        <main class="flex-1 p-4 md:p-8 w-full min-w-0">
            @if (session('success'))
                <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
