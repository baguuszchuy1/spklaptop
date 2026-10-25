@extends('layouts.app')

@section('title', 'Perhitungan SAW')

@section('content')
    @php
        $criteria = $result['criteria'];
        $laptops = $result['laptops'];
        $matrix = $result['matrix'];
        $normalized = $result['normalized'];
        $weighted = $result['weighted'];
        $totals = $result['totals'];
        $weights = $result['weights'];
        $names = $laptops->pluck('name', 'id');

        $fmt = fn ($number, $decimals = 4) => number_format($number, $decimals, ',', '.');
        $formatSpec = function ($laptop, $criterion) {
            $value = $laptop->getAttribute($criterion->spec_field);

            return match ($criterion->spec_field) {
                'price' => 'Rp ' . number_format((float) $value, 0, ',', '.'),
                'ram_gb', 'storage_gb' => number_format((float) $value, 0, ',', '.') . ' GB',
                'processor_score' => number_format((float) $value, 0, ',', '.'),
                default => (string) $value,
            };
        };
        $th = 'px-4 py-3 text-right whitespace-nowrap';
    @endphp

    <h1 class="text-2xl font-bold mb-1 text-slate-800">Perhitungan SAW</h1>
    <p class="text-sm text-slate-500 mb-4">
        Seluruh tahap perhitungan ditampilkan berurutan, dari nilai awal sampai ranking, supaya bisa dicocokkan dengan hitungan manual.
    </p>

    <!-- Pilih metode bobot -->
    <div class="flex flex-wrap items-center gap-2 mb-6">
        <span class="text-sm text-slate-500 mr-1">Bobot yang dipakai:</span>
        @foreach ($methodLabels as $key => $label)
            <a href="{{ route('saw.index', ['method' => $key]) }}"
               class="px-3.5 py-1.5 rounded-lg text-sm font-medium border transition
                      {{ $method === $key ? 'bg-[#7a1220] text-white border-[#7a1220]' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if ($result['weight_fallback'])
        <div class="mb-6 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">
            Belum ada responden survei yang valid, sehingga bobot sementara <strong>sama rata</strong>.
            Impor data lewat menu <a href="{{ route('survey.index') }}" class="underline font-medium">Data Survei</a>.
        </div>
    @endif

    @if ($result['excluded'] !== [])
        <div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            <p class="font-medium mb-1">Laptop berikut tidak ikut dihitung karena datanya belum lengkap:</p>
            <ul class="list-disc list-inside text-xs space-y-0.5">
                @foreach ($result['excluded'] as $row)
                    <li>
                        <a href="{{ route('laptops.edit', $row['laptop']) }}" class="underline font-medium">{{ $row['laptop']->name }}</a>:
                        {{ implode('; ', $row['problems']) }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($laptops->isEmpty() || $criteria->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-10 text-center text-slate-400">
            Belum ada laptop yang bisa dihitung. Tambahkan laptop di menu
            <a href="{{ route('laptops.index') }}" class="underline font-medium text-[#7a1220]">Data Laptop</a>.
        </div>
    @else
        <!-- Rekomendasi teratas -->
        @php $top = $result['ranking'][0]; @endphp
        <div class="mb-6 rounded-xl bg-[#fdf1f2] border border-[#f3d6d9] px-5 py-4">
            <p class="text-xs text-[#7a1220] font-medium mb-0.5">Peringkat 1 (bobot {{ $methodLabels[$method] }})</p>
            <p class="text-lg font-bold text-slate-800">{{ $top['laptop']->name }}</p>
            <p class="text-sm text-slate-500">Skor akhir {{ $fmt($top['score']) }}</p>
        </div>

        <div class="space-y-8">
            <!-- 1. Bobot -->
            <section>
                <h2 class="text-lg font-semibold text-slate-800 mb-1">1. Bobot kriteria</h2>
                <p class="text-xs text-slate-400 mb-3">Dari data survei menurut metode yang dipilih. Atribut menentukan rumus normalisasi.</p>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto">
                    <table class="w-full text-sm min-w-[560px]">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Kriteria</th>
                                <th class="px-4 py-3 text-left">Atribut</th>
                                <th class="{{ $th }}">Bobot</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($criteria as $criterion)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2.5"><span class="text-slate-400">{{ $criterion->code }}</span> {{ $criterion->name }}</td>
                                    <td class="px-4 py-2.5">{{ $criterion->isCost() ? 'Cost' : 'Benefit' }}</td>
                                    <td class="px-4 py-2.5 text-right font-semibold text-[#7a1220]">{{ $fmt($weights[$criterion->id] * 100, 2) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- 2. Matriks keputusan -->
            <section>
                <h2 class="text-lg font-semibold text-slate-800 mb-1">2. Matriks keputusan (x<sub>ij</sub>)</h2>
                <p class="text-xs text-slate-400 mb-3">
                    Kriteria bermode rubrik menampilkan skor rubrik (besar) beserta spesifikasi aslinya (kecil).
                    Kriteria bermode nilai asli memakai spesifikasi langsung.
                </p>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto">
                    <table class="w-full text-sm min-w-[720px]">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Laptop</th>
                                @foreach ($criteria as $criterion)
                                    <th class="{{ $th }}">{{ $criterion->code }}<br><span class="font-normal text-xs">{{ $criterion->name }}</span></th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($laptops as $laptop)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2.5 font-medium whitespace-nowrap">{{ $laptop->name }}</td>
                                    @foreach ($criteria as $criterion)
                                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                            @if ($criterion->usesRubric())
                                                <span class="font-semibold">{{ $fmt($matrix[$laptop->id][$criterion->id], 0) }}</span>
                                                <span class="block text-[11px] text-slate-400">{{ $formatSpec($laptop, $criterion) }}</span>
                                            @else
                                                {{ $formatSpec($laptop, $criterion) }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- 3. Normalisasi -->
            <section>
                <h2 class="text-lg font-semibold text-slate-800 mb-1">3. Normalisasi (r<sub>ij</sub>)</h2>
                <p class="text-xs text-slate-400 mb-3">
                    Benefit: r = x / nilai terbesar kolom. Cost: r = nilai terkecil kolom / x. Baris "Nilai acuan" menunjukkan pembagi/pembilangnya.
                </p>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto">
                    <table class="w-full text-sm min-w-[720px]">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Laptop</th>
                                @foreach ($criteria as $criterion)
                                    <th class="{{ $th }}">{{ $criterion->code }}<br><span class="font-normal text-xs">{{ $criterion->isCost() ? 'cost' : 'benefit' }}</span></th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($laptops as $laptop)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2.5 font-medium whitespace-nowrap">{{ $laptop->name }}</td>
                                    @foreach ($criteria as $criterion)
                                        <td class="px-4 py-2.5 text-right">{{ $fmt($normalized[$laptop->id][$criterion->id]) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t border-slate-200 text-xs text-slate-500">
                            <tr>
                                <td class="px-4 py-2">Nilai acuan</td>
                                @foreach ($criteria as $criterion)
                                    @php $column = array_column($matrix, $criterion->id); @endphp
                                    <td class="px-4 py-2 text-right whitespace-nowrap">
                                        {{ $criterion->isCost() ? 'min' : 'maks' }}
                                        {{ number_format($criterion->isCost() ? min($column) : max($column), 0, ',', '.') }}
                                    </td>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            <!-- 4. Terbobot -->
            <section>
                <h2 class="text-lg font-semibold text-slate-800 mb-1">4. Nilai terbobot (v<sub>ij</sub> = w<sub>j</sub> &times; r<sub>ij</sub>) dan skor akhir</h2>
                <p class="text-xs text-slate-400 mb-3">Skor akhir V<sub>i</sub> adalah jumlah nilai terbobot pada satu baris.</p>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto">
                    <table class="w-full text-sm min-w-[760px]">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Laptop</th>
                                @foreach ($criteria as $criterion)
                                    <th class="{{ $th }}">{{ $criterion->code }}<br><span class="font-normal text-xs">w = {{ $fmt($weights[$criterion->id], 4) }}</span></th>
                                @endforeach
                                <th class="{{ $th }}">Skor akhir (V)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($laptops as $laptop)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2.5 font-medium whitespace-nowrap">{{ $laptop->name }}</td>
                                    @foreach ($criteria as $criterion)
                                        <td class="px-4 py-2.5 text-right">{{ $fmt($weighted[$laptop->id][$criterion->id]) }}</td>
                                    @endforeach
                                    <td class="px-4 py-2.5 text-right font-bold text-[#7a1220]">{{ $fmt($totals[$laptop->id]) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- 5. Ranking -->
            <section>
                <h2 class="text-lg font-semibold text-slate-800 mb-3">5. Peringkat akhir</h2>
                @php $maxScore = max(array_column($result['ranking'], 'score')) ?: 1; @endphp
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto">
                    <table class="w-full text-sm min-w-[520px]">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Peringkat</th>
                                <th class="px-4 py-3 text-left">Laptop</th>
                                <th class="px-4 py-3 text-left w-1/3">Skor akhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($result['ranking'] as $row)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold
                                                     {{ $row['rank'] === 1 ? 'bg-amber-400 text-amber-950' : 'bg-slate-100 text-slate-500' }}">{{ $row['rank'] }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 font-medium">{{ $row['laptop']->name }}</td>
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-slate-100 rounded-full h-2.5 min-w-[60px]">
                                                <div class="{{ $row['rank'] === 1 ? 'bg-amber-400' : 'bg-[#b13a4b]' }} h-2.5 rounded-full"
                                                     style="width: {{ round(($row['score'] / $maxScore) * 100, 1) }}%"></div>
                                            </div>
                                            <span class="text-xs font-semibold text-slate-600 w-16 text-right">{{ $fmt($row['score']) }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- 6. Perbandingan metode bobot -->
            <section>
                <h2 class="text-lg font-semibold text-slate-800 mb-1">6. Analisis sensitivitas: cara pembobotan</h2>
                <p class="text-xs text-slate-400 mb-3">Apakah ranking berubah kalau bobotnya dihitung dengan cara lain?</p>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto mb-3">
                    <table class="w-full text-sm min-w-[900px]">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left" rowspan="2">Laptop</th>
                                @foreach ($methodLabels as $label)
                                    <th class="px-4 py-2 text-center" colspan="2">{{ $label }}</th>
                                @endforeach
                            </tr>
                            <tr class="text-xs">
                                @foreach ($methodLabels as $label)
                                    <th class="px-4 pb-2 text-right">Skor</th>
                                    <th class="px-4 pb-2 text-right">Peringkat</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($laptops as $laptop)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2.5 font-medium whitespace-nowrap">{{ $laptop->name }}</td>
                                    @foreach ($methodLabels as $key => $label)
                                        <td class="px-4 py-2.5 text-right">{{ $fmt($comparison['methods'][$key]['totals'][$laptop->id]) }}</td>
                                        <td class="px-4 py-2.5 text-right font-semibold">{{ $comparison['methods'][$key]['ranks'][$laptop->id] }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- <p class="text-sm text-slate-600">
                    Laptop peringkat 1
                    <strong>{{ $comparison['top_same'] ? 'sama' : 'berbeda' }}</strong>
                    di ketiga cara pembobotan.
                    Korelasi Spearman urutan ROC vs Likert:
                    <strong>{{ $comparison['correlations']['likert'] !== null ? $fmt($comparison['correlations']['likert'], 3) : '-' }}</strong>;
                    ROC vs sama rata:
                    <strong>{{ $comparison['correlations']['equal'] !== null ? $fmt($comparison['correlations']['equal'], 3) : '-' }}</strong>.
                </p> --}}
                <p class="text-sm text-slate-600">
                    Laptop peringkat 1
                    <strong>{{ $comparison['top_same'] ? 'sama' : 'berbeda' }}</strong>
                    di keempat cara pembobotan.
                    Korelasi Spearman terhadap ROC Kolektif:
                    @foreach ($comparison['correlations'] as $key => $value)
                        {{ $methodLabels[$key] }} <strong>{{ $value !== null ? $fmt($value, 3) : '-' }}</strong>@if (! $loop->last); @else. @endif
                    @endforeach
                </p>
            </section>

            <!-- 7. Perubahan bobot -->
            <section>
                <h2 class="text-lg font-semibold text-slate-800 mb-1">7. Analisis sensitivitas: perubahan bobot &plusmn;{{ $fmt($perturbation['delta'] * 100, 0) }}%</h2>
                <p class="text-xs text-slate-400 mb-3">
                    Bobot satu kriteria dikali {{ $fmt(1 - $perturbation['delta'], 1) }} atau {{ $fmt(1 + $perturbation['delta'], 1) }},
                    lalu seluruh bobot dinormalisasi ulang agar total tetap 100%, kemudian ranking
                    (bobot {{ $methodLabels[$method] }}) dihitung ulang.
                </p>
                {{-- <p class="text-xs text-slate-400 mb-3">
                    Bobot satu kriteria dinaikkan atau diturunkan, bobot lainnya disesuaikan agar total tetap 100%, lalu ranking
                    (bobot {{ $methodLabels[$method] }}) dihitung ulang.
                </p> --}}
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto mb-3">
                    <table class="w-full text-sm min-w-[680px]">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Skenario</th>
                                <th class="px-4 py-3 text-left">Peringkat 1</th>
                                <th class="px-4 py-3 text-center">Peringkat 1 sama?</th>
                                <th class="px-4 py-3 text-center">Urutan sama?</th>
                                <th class="{{ $th }}">Spearman</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($perturbation['scenarios'] as $scenario)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        {{ $scenario['criterion']->code }} {{ $scenario['criterion']->name }}
                                        <span class="text-slate-400">{{ $scenario['change'] > 0 ? '+' : '-' }}{{ $fmt(abs($scenario['change']) * 100, 0) }}%</span>
                                    </td>
                                    <td class="px-4 py-2.5">{{ collect($scenario['top_ids'])->map(fn ($id) => $names[$id] ?? '?')->implode(', ') }}</td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $scenario['top_same'] ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' }}">{{ $scenario['top_same'] ? 'Ya' : 'Berubah' }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $scenario['same_order'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $scenario['same_order'] ? 'Ya' : 'Berubah' }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-right">{{ $scenario['correlation'] !== null ? $fmt($scenario['correlation'], 3) : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($perturbation['scenarios'] !== [])
                    <p class="text-sm text-slate-600">
                        Pada {{ count($perturbation['scenarios']) }} skenario,
                        laptop peringkat 1 <strong>{{ $perturbation['stable_top'] ? 'tidak pernah berubah' : 'berubah pada sebagian skenario' }}</strong>
                        dan urutan keseluruhan <strong>{{ $perturbation['stable_order'] ? 'selalu sama' : 'berubah pada sebagian skenario' }}</strong>.
                        @if ($perturbation['min_correlation'] !== null)
                            Korelasi terendah dengan urutan dasar: {{ $fmt($perturbation['min_correlation'], 3) }}.
                        @endif
                    </p>
                @endif
            </section>
        </div>
    @endif
@endsection
