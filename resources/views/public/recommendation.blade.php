@extends('layouts.public')

@section('title', 'Rekomendasi Laptop - ' . config('brand.name'))

@section('content')
    @php
        $fmt = fn ($number, $decimals = 4) => number_format($number, $decimals, ',', '.');
        $badge = [1 => 'bg-amber-400 text-amber-950', 2 => 'bg-slate-300 text-slate-800', 3 => 'bg-orange-300 text-orange-950'];
        $maxPercent = $weightBars === [] ? 1 : max(array_column($weightBars, 'percent'));
        $maxScore = $ranking === [] ? 1 : (max(array_column($ranking, 'score')) ?: 1);
    @endphp

    <div class="max-w-4xl mx-auto px-4 md:px-6 py-10 space-y-10">
        <header>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-800 mb-2">Peringkat Laptop untuk Mahasiswa Pendidikan Komputer</h1>
            <p class="text-sm text-slate-500">
                Urutan dari yang paling sesuai dengan prioritas mahasiswa berdasarkan spesifikasi riil tiap laptop.
            </p>
        </header>

        @if (! $ready)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-10 text-center text-slate-500">
                <p class="font-medium text-slate-700 mb-1">Hasil belum tersedia</p>
                <p class="text-sm">Data survei dan spesifikasi laptop masih disiapkan. Silakan kembali beberapa saat lagi.</p>
            </div>
        @else
            <p class="text-sm text-slate-500">
                Berdasarkan prioritas <strong>{{ $survey['respondents_used'] }}</strong> mahasiswa dan
                <strong>{{ count($ranking) }}</strong> tipe laptop.
            </p>

            <!-- Peringkat -->
            <section class="space-y-3">
                @foreach ($ranking as $row)
                    @php $laptop = $row['laptop']; @endphp
                    <article class="bg-white rounded-xl shadow-sm border {{ $row['rank'] === 1 ? 'border-amber-300' : 'border-slate-200' }} p-4 md:p-5">
                        <div class="flex items-start gap-4">
                            <span class="inline-flex shrink-0 items-center justify-center w-9 h-9 rounded-full text-sm font-bold {{ $badge[$row['rank']] ?? 'bg-slate-100 text-slate-500' }}">{{ $row['rank'] }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-1">
                                    <h2 class="font-semibold text-slate-800">{{ $laptop->name }}</h2>
                                    <p class="font-bold text-[#7a1220]">{{ $laptop->price_label }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <span class="text-xs bg-slate-100 text-slate-600 px-2.5 py-1 rounded-full">{{ $laptop->processor_name }}</span>
                                    <span class="text-xs bg-slate-100 text-slate-600 px-2.5 py-1 rounded-full">RAM {{ $laptop->ram_label }}</span>
                                    <span class="text-xs bg-slate-100 text-slate-600 px-2.5 py-1 rounded-full">{{ $laptop->storage_label }}</span>
                                </div>
                                <div class="flex items-center gap-2 mt-3">
                                    <div class="flex-1 bg-slate-100 rounded-full h-2">
                                        <div class="{{ $row['rank'] === 1 ? 'bg-amber-400' : 'bg-[#b13a4b]' }} h-2 rounded-full"
                                             style="width: {{ round(($row['score'] / $maxScore) * 100, 1) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-600 w-16 text-right">{{ $fmt($row['score']) }}</span>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>

            <!-- Bobot -->
            <section>
                <h2 class="text-lg font-bold text-slate-800 mb-1">Prioritas Mahasiswa (Bobot Kriteria)</h2>
                <p class="text-sm text-slate-500 mb-4">Makin besar bobotnya, makin besar pengaruh kriteria itu terhadap peringkat.</p>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 md:p-6 space-y-4">
                    @foreach ($weightBars as $bar)
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-slate-700 font-medium">
                                    {{ $bar['name'] }}
                                    <span class="text-xs font-normal text-slate-400">{{ $bar['type'] === 'cost' ? '(makin murah makin baik)' : '' }}</span>
                                </span>
                                <span class="font-semibold text-[#7a1220]">{{ $fmt($bar['percent'], 1) }}%</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2.5">
                                <div class="bg-[#7a1220] h-2.5 rounded-full" style="width: {{ round(($bar['percent'] / $maxPercent) * 100, 1) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- Kekokohan -->
            @if ($perturbation && $perturbation['scenarios'] !== [])
                <section>
                    <h2 class="text-lg font-bold text-slate-800 mb-2">Seberapa Kokoh Hasil Ini?</h2>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 text-sm text-slate-600">
                        Bobot tiap kriteria diuji dinaikkan dan diturunkan {{ number_format($perturbation['delta'] * 100, 0) }}%
                        ({{ count($perturbation['scenarios']) }} skenario). Laptop peringkat 1
                        <strong>{{ $perturbation['stable_top'] ? 'tidak berubah pada seluruh skenario' : 'berubah pada sebagian skenario' }}</strong>,
                        dan urutan keseluruhan
                        <strong>{{ $perturbation['stable_order'] ? 'selalu sama' : 'berubah pada sebagian skenario' }}</strong>.
                    </div>
                </section>
            @endif
        @endif

        <!-- Catatan -->
        <section class="text-xs text-slate-400 space-y-1 border-t border-slate-200 pt-6">
            <p>Peringkat bersifat rekomendasi berdasarkan kriteria yang dinilai (harga, processor, RAM, kapasitas dan tipe penyimpanan), bukan penilaian mutlak.</p>
            @if ($sourceDates)
                <p>
                    Data spesifikasi dan harga diakses pada {{ $sourceDates['first']->format('d/m/Y') }}
                    @if (! $sourceDates['first']->isSameDay($sourceDates['last']))
                        sampai {{ $sourceDates['last']->format('d/m/Y') }}
                    @endif
                    dan dapat berubah sewaktu-waktu.
                </p>
            @endif
        </section>
    </div>
@endsection
