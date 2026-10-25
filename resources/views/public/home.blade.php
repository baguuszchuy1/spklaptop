@extends('layouts.public')

@section('title', config('brand.name') . ' - ' . config('brand.tagline'))

@section('content')
    @php
        $fmt = fn ($number, $decimals = 4) => number_format($number, $decimals, ',', '.');
        $badge = [1 => 'bg-amber-400 text-amber-950', 2 => 'bg-slate-300 text-slate-800', 3 => 'bg-orange-300 text-orange-950'];
        $maxPercent = $weightBars === [] ? 1 : max(array_column($weightBars, 'percent'));
    @endphp

    <!-- Hero -->
    <section class="bg-gradient-to-b from-[#fdf1f2] to-slate-50 border-b border-slate-100">
        <div class="max-w-6xl mx-auto px-4 md:px-6 py-14 md:py-20 text-center">
            <h1 class="text-3xl md:text-4xl font-bold text-slate-800 mb-3">
                Laptop Apa yang Cocok untuk Mahasiswa Pendidikan Komputer?
            </h1>
            <p class="text-slate-500 max-w-2xl mx-auto mb-8">
                Peringkat disusun dari <strong>prioritas mahasiswa Pendidikan Komputer</strong> dan
                <strong>spesifikasi riil</strong> tiap laptop, lalu dihitung dengan metode ROC dan SAW.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('public.recommendation') }}"
                   class="bg-[#7a1220] text-white font-semibold px-6 py-3 rounded-lg hover:bg-[#5c0d18] transition shadow-sm w-full sm:w-auto">
                    Lihat Peringkat Lengkap
                </a>
                <a href="{{ route('public.catalog') }}"
                   class="border border-[#7a1220] text-[#7a1220] font-semibold px-6 py-3 rounded-lg hover:bg-[#fdf1f2] transition w-full sm:w-auto">
                    Katalog Laptop
                </a>
            </div>
        </div>
    </section>

    <div class="max-w-6xl mx-auto px-4 md:px-6 py-12 space-y-14">
        <!-- Ringkasan -->
        <section class="grid grid-cols-3 gap-3 md:gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5 text-center">
                <p class="text-xs text-slate-500 mb-1">Responden survei</p>
                <p class="text-2xl md:text-3xl font-bold text-slate-800">{{ $survey['respondents_used'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5 text-center">
                <p class="text-xs text-slate-500 mb-1">Laptop dibandingkan</p>
                <p class="text-2xl md:text-3xl font-bold text-slate-800">{{ $laptopCount }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5 text-center">
                <p class="text-xs text-slate-500 mb-1">Kriteria penilaian</p>
                <p class="text-2xl md:text-3xl font-bold text-slate-800">{{ $criteriaCount }}</p>
            </div>
        </section>

        @if (! $ready)
            <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-10 text-center text-slate-500">
                <p class="font-medium text-slate-700 mb-1">Hasil rekomendasi belum tersedia</p>
                <p class="text-sm">Data survei dan spesifikasi laptop masih disiapkan. Silakan kembali beberapa saat lagi.</p>
            </section>
        @else
            <!-- Rekomendasi teratas -->
            <section>
                <div class="flex items-end justify-between mb-4">
                    <h2 class="text-xl font-bold text-slate-800">Rekomendasi Teratas</h2>
                    <a href="{{ route('public.recommendation') }}" class="text-sm text-[#7a1220] font-medium hover:underline">Lihat semua &rarr;</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach ($top as $row)
                        <div class="bg-white rounded-xl shadow-sm border {{ $row['rank'] === 1 ? 'border-amber-300' : 'border-slate-200' }} p-5">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold {{ $badge[$row['rank']] ?? 'bg-slate-100 text-slate-500' }}">{{ $row['rank'] }}</span>
                                <span class="text-xs text-slate-400">Skor {{ $fmt($row['score']) }}</span>
                            </div>
                            <h3 class="font-semibold text-slate-800 leading-snug">{{ $row['laptop']->name }}</h3>
                            <p class="text-[#7a1220] font-bold mt-1 mb-3">{{ $row['laptop']->price_label }}</p>
                            <ul class="text-xs text-slate-500 space-y-1">
                                <li>{{ $row['laptop']->processor_name }}</li>
                                <li>RAM {{ $row['laptop']->ram_label }}</li>
                                <li>{{ $row['laptop']->storage_label }}</li>
                            </ul>
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- Prioritas mahasiswa -->
            <section>
                <h2 class="text-xl font-bold text-slate-800 mb-1">Apa yang Paling Penting bagi Mahasiswa?</h2>
                <p class="text-sm text-slate-500 mb-5">
                    Bobot tiap kriteria berasal dari urutan prioritas {{ $survey['respondents_used'] }} mahasiswa Pendidikan Komputer.
                </p>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 md:p-6 space-y-4">
                    @foreach ($weightBars as $bar)
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-slate-700 font-medium">{{ $bar['name'] }}</span>
                                <span class="font-semibold text-[#7a1220]">{{ $fmt($bar['percent'], 1) }}%</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2.5">
                                <div class="bg-[#7a1220] h-2.5 rounded-full" style="width: {{ round(($bar['percent'] / $maxPercent) * 100, 1) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Cara kerja -->
        <section>
            <h2 class="text-xl font-bold text-slate-800 mb-5">Bagaimana Peringkat Ini Dihitung?</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ([
                    ['1', 'Prioritas mahasiswa', 'Mahasiswa mengurutkan kriteria dari yang paling penting. Urutan itu diubah menjadi bobot dengan metode ROC (Rank Order Centroid).'],
                    ['2', 'Spesifikasi riil', 'Spesifikasi tiap tipe laptop (harga, processor, RAM, penyimpanan) dikonversi menjadi skor lewat rubrik yang bersumber.'],
                    ['3', 'Peringkat dengan SAW', 'Skor dinormalisasi lalu dikalikan bobot dan dijumlahkan (Simple Additive Weighting). Skor tertinggi menjadi peringkat 1.'],
                ] as [$number, $title, $text])
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-[#fdf1f2] text-[#7a1220] text-sm font-bold mb-3">{{ $number }}</span>
                        <h3 class="font-semibold text-slate-800 mb-1">{{ $title }}</h3>
                        <p class="text-sm text-slate-500">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
