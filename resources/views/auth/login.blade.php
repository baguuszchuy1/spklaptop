<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - SPK Pemilihan Merek Laptop</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-b from-[#3b0a10] via-slate-950 to-slate-950 px-4">

    <div class="w-full max-w-sm">
        <a href="{{ route('public.home') }}"
           class="inline-flex items-center gap-2 text-slate-300 hover:text-white text-sm mb-6 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Beranda
        </a>

        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-6">
                <span class="inline-flex w-14 h-14 rounded-2xl bg-[#7a1220] items-center justify-center text-white text-lg font-bold shadow-lg shadow-[#7a1220]/30 mb-3">
                    SL
                </span>
                <h1 class="text-lg font-bold text-slate-800">Login Admin</h1>
                <p class="text-xs text-slate-400 mt-1">SPK Pemilihan Merek Laptop</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b] focus:border-[#8a1c2b]">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b] focus:border-[#8a1c2b]">
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded accent-[#7a1220]">
                    Ingat saya
                </label>

                <button type="submit"
                        class="w-full bg-[#7a1220] text-white rounded-lg py-2.5 text-sm font-semibold hover:bg-[#5c0d18] transition shadow-sm">
                    Masuk
                </button>
            </form>
        </div>

        {{-- <p class="text-xs text-slate-500 text-center mt-6">
            Akun default: admin@spk-laptop.test / password123<br>
            Buat login akunnya, ntar bisa di ganti
        </p> --}}
    </div>
</body>
</html>
