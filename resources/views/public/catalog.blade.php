@extends('layouts.public')

@section('title', 'Katalog Laptop - ' . config('brand.name'))

@section('content')
    <div class="max-w-6xl mx-auto px-4 md:px-6 py-10" x-data="{ q: '' }">
        <header class="mb-6">
            <h1 class="text-2xl md:text-3xl font-bold text-slate-800 mb-2">Katalog Laptop</h1>
            <p class="text-sm text-slate-500">
                Semua tipe laptop yang dibandingkan, lengkap dengan spesifikasi yang dipakai dalam perhitungan.
            </p>
        </header>

        <div class="mb-4">
            <input type="search" x-model="q" placeholder="Cari tipe atau merek laptop..."
                   class="w-full sm:w-80 rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b] focus:border-[#8a1c2b]">
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[760px]">
                    <thead class="bg-slate-50 text-slate-500 text-left">
                        <tr>
                            <th class="px-4 py-3">Laptop</th>
                            <th class="px-4 py-3">Harga</th>
                            <th class="px-4 py-3">Processor</th>
                            <th class="px-4 py-3">RAM</th>
                            <th class="px-4 py-3">Penyimpanan</th>
                            <th class="px-4 py-3">Sumber data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($laptops as $laptop)
                            <tr class="border-t border-slate-100 align-top"
                                data-name="{{ mb_strtolower($laptop->name . ' ' . $laptop->brand) }}"
                                x-show="!q || $el.dataset.name.includes(q.toLowerCase())">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-800">{{ $laptop->name }}</p>
                                    @if ($laptop->brand)
                                        <p class="text-xs text-slate-400">{{ $laptop->brand }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-semibold text-[#7a1220]">{{ $laptop->price_label }}</td>
                                <td class="px-4 py-3">{{ $laptop->processor_name }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $laptop->ram_label }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $laptop->storage_label }}</td>
                                <td class="px-4 py-3 text-xs text-slate-400">
                                    @if ($laptop->spec_source)
                                        <p class="break-words max-w-[14rem]">{{ $laptop->spec_source }}</p>
                                    @endif
                                    @if ($laptop->spec_accessed_on)
                                        <p>Diakses {{ $laptop->spec_accessed_on->format('d/m/Y') }}</p>
                                    @endif
                                    @if (! $laptop->spec_source && ! $laptop->spec_accessed_on)
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada laptop di katalog.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="text-[11px] text-slate-400 px-4 py-2 sm:hidden">← Geser tabel ke kiri/kanan untuk melihat semua kolom</p>
        </div>

        @if ($sourceDates)
            <p class="text-xs text-slate-400 mt-4">
                Harga dan spesifikasi diakses pada {{ $sourceDates['first']->format('d/m/Y') }}
                @if (! $sourceDates['first']->isSameDay($sourceDates['last']))
                    sampai {{ $sourceDates['last']->format('d/m/Y') }}
                @endif
                dan dapat berubah sewaktu-waktu.
            </p>
        @endif
    </div>
@endsection
