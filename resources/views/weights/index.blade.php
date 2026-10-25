@extends('layouts.app')

@section('title', 'Bobot Kriteria')

@section('content')
    <h1 class="text-2xl font-bold mb-1 text-slate-800">Bobot Kriteria</h1>
    <p class="text-sm text-slate-500 mb-6">
        Bobot dihitung dari data survei. Bobot utama memakai ROC (dari urutan prioritas), dan dibandingkan dengan
        bobot dari rata-rata skor Likert sebagai pembanding.
    </p>

    @if ($survey['fallback'])
        <div class="mb-6 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">
            Belum ada responden yang valid, sehingga semua metode sementara memakai <strong>bobot sama rata</strong>.
            Impor data lewat menu <a href="{{ route('survey.index') }}" class="underline font-medium">Data Survei</a>.
        </div>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5">
            <p class="text-xs text-slate-500 mb-1">Responden dipakai</p>
            <p class="text-2xl font-bold text-slate-800">{{ $survey['respondents_used'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5">
            <p class="text-xs text-slate-500 mb-1">Responden dilewati</p>
            <p class="text-2xl font-bold {{ $survey['respondents_skipped'] > 0 ? 'text-red-500' : 'text-slate-800' }}">{{ $survey['respondents_skipped'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5 col-span-2 lg:col-span-1">
            <p class="text-xs text-slate-500 mb-1">Korelasi urutan ROC vs Likert (Spearman)</p>
            <p class="text-2xl font-bold text-slate-800">{{ $correlation !== null ? number_format($correlation, 3, ',', '.') : '-' }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[760px]">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-3">Kriteria</th>
                        <th class="px-4 py-3 text-right">Rata-rata peringkat</th>
                        <th class="px-4 py-3 text-right">Rata-rata Likert</th>
                        <th class="px-4 py-3 text-right">Bobot ROC</th>
                        <th class="px-4 py-3 text-right">Bobot Likert</th>
                        <th class="px-4 py-3 text-right">Sama rata</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($criteria as $criterion)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3 font-medium whitespace-nowrap">
                                <span class="text-slate-400">{{ $criterion->code }}</span> {{ $criterion->name }}
                            </td>
                            <td class="px-4 py-3 text-right">{{ isset($survey['mean_rank'][$criterion->id]) ? number_format($survey['mean_rank'][$criterion->id], 2, ',', '.') : '-' }}</td>
                            <td class="px-4 py-3 text-right">{{ isset($survey['mean_likert'][$criterion->id]) ? number_format($survey['mean_likert'][$criterion->id], 2, ',', '.') : '-' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-[#7a1220]">{{ number_format($survey['roc'][$criterion->id] * 100, 2, ',', '.') }}%</td>
                            <td class="px-4 py-3 text-right">{{ number_format($survey['likert'][$criterion->id] * 100, 2, ',', '.') }}%</td>
                            <td class="px-4 py-3 text-right text-slate-500">{{ number_format($survey['equal'][$criterion->id] * 100, 2, ',', '.') }}%</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-slate-200 text-slate-500 text-xs">
                    <tr>
                        <td class="px-4 py-2" colspan="3">Total</td>
                        <td class="px-4 py-2 text-right">{{ number_format(array_sum($survey['roc']) * 100, 2, ',', '.') }}%</td>
                        <td class="px-4 py-2 text-right">{{ number_format(array_sum($survey['likert']) * 100, 2, ',', '.') }}%</td>
                        <td class="px-4 py-2 text-right">{{ number_format(array_sum($survey['equal']) * 100, 2, ',', '.') }}%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-6 mb-6">
        <h2 class="text-base md:text-lg font-semibold mb-4 text-slate-800">Perbandingan Bobot Tiap Metode (%)</h2>
        <canvas id="weightChart" height="110"></canvas>
    </div>

    <div class="text-xs text-slate-400 space-y-1">
        <p>ROC: tiap responden mengubah urutan prioritasnya jadi bobot (prioritas 1 = bobot terbesar), lalu dirata-rata antarresponden.</p>
        <p>Likert: rata-rata skor kepentingan tiap kriteria dibagi jumlah seluruh rata-rata. Responden yang jawabannya tidak lengkap atau urutannya tidak valid tidak dihitung.</p>
        <p>Korelasi Spearman mendekati 1 berarti kedua metode mengurutkan kepentingan kriteria dengan pola yang sama. Dengan hanya 5 kriteria, tafsirkan sebagai gambaran, bukan uji signifikansi.</p>
    </div>
@endsection

@push('scripts')
    <script>
        new Chart(document.getElementById('weightChart'), {
            type: 'bar',
            data: {
                labels: @json($chart['labels']),
                datasets: [
                    { label: 'ROC (utama)', data: @json($chart['roc']), backgroundColor: '#7a1220', borderRadius: 4 },
                    { label: 'Likert', data: @json($chart['likert']), backgroundColor: '#c96575', borderRadius: 4 },
                    { label: 'Sama rata', data: @json($chart['equal']), backgroundColor: '#cbd5e1', borderRadius: 4 },
                ]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, ticks: { callback: (v) => v + '%' } } },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ': ' + ctx.parsed.y + '%' } }
                }
            }
        });
    </script>
@endpush
