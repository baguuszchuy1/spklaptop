@php
    // Tautan hanya tampil kalau rutenya sudah ada, jadi menu bertambah sendiri tiap langkah selesai.
    $items = [
        ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Dashboard',
            'icon' => 'M3 3v18h18M8 17V9m4 8V5m4 12v-6'],
        ['route' => 'criteria.index', 'match' => 'criteria.*', 'label' => 'Kriteria & Rubrik',
            'icon' => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'],
        ['route' => 'laptops.index', 'match' => 'laptops.*', 'label' => 'Data Laptop',
            'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
        ['route' => 'survey.index', 'match' => 'survey.*', 'label' => 'Data Survei',
            'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
        ['route' => 'weights.index', 'match' => 'weights.*', 'label' => 'Bobot Kriteria',
            'icon' => 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3'],
        ['route' => 'saw.index', 'match' => 'saw.*', 'label' => 'Perhitungan SAW',
            'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h-4.5M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
    ];
@endphp

<nav class="flex-1 p-4 space-y-1 overflow-y-auto">
    @foreach ($items as $item)
        @if (\Illuminate\Support\Facades\Route::has($item['route']))
            <a href="{{ route($item['route']) }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border-l-2
                        {{ request()->routeIs($item['match']) ? 'bg-white/10 text-white border-[#b13a4b]' : 'text-slate-300 border-transparent hover:bg-white/5 hover:text-white' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                </svg>
                {{ $item['label'] }}
            </a>
        @endif
    @endforeach
</nav>
