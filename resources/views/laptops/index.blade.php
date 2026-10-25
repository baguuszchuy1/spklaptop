@extends('layouts.app')

@section('title', 'Data Laptop')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Data Laptop</h1>
            <p class="text-sm text-slate-500">Total: {{ $laptops->count() }} laptop (alternatif yang dibandingkan)</p>
        </div>
        <a href="{{ route('laptops.create') }}"
           class="bg-[#7a1220] text-white text-sm font-medium rounded-lg px-4 py-2.5 hover:bg-[#5c0d18] transition text-center">
            + Tambah Laptop
        </a>
    </div>

    <div class="mb-6 rounded-xl bg-[#fdf1f2] border border-[#f3d6d9] text-[#5c0d18] px-4 py-3 text-sm">
        Catat sumber spesifikasi dan tanggal akses tiap laptop, karena harga berubah-ubah dan reviewer biasanya
        menanyakan asal datanya. Laptop dengan status merah tidak ikut dihitung sampai datanya dilengkapi.
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[900px]">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-3">Laptop</th>
                        <th class="px-4 py-3">Harga</th>
                        <th class="px-4 py-3">Processor</th>
                        <th class="px-4 py-3">RAM</th>
                        <th class="px-4 py-3">Penyimpanan</th>
                        <th class="px-4 py-3">Sumber data</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($laptops as $laptop)
                        @php $laptopProblems = $problems->get($laptop->id, []); @endphp
                        <tr class="border-t border-slate-100 align-top">
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ $laptop->name }}</p>
                                @if ($laptop->brand)
                                    <p class="text-xs text-slate-400">{{ $laptop->brand }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">Rp {{ number_format($laptop->price, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <p>{{ $laptop->processor_name }}</p>
                                <p class="text-xs text-slate-400">
                                    Skor: {{ $laptop->processor_score !== null ? number_format($laptop->processor_score, 0, ',', '.') : '-' }}
                                </p>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $laptop->ram_gb }} GB</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $laptop->storage_type }} &middot; {{ number_format($laptop->storage_gb, 0, ',', '.') }} GB</td>
                            <td class="px-4 py-3">
                                @if ($laptop->spec_source && $laptop->spec_accessed_on)
                                    <p class="text-xs text-slate-600 break-words max-w-[14rem]">{{ $laptop->spec_source }}</p>
                                    <p class="text-xs text-slate-400">Diakses {{ $laptop->spec_accessed_on->format('d/m/Y') }}</p>
                                @else
                                    <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">Belum dicatat</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($laptopProblems === [])
                                    <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">Siap dihitung</span>
                                @else
                                    <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full bg-red-50 text-red-600">Belum bisa dihitung</span>
                                    <ul class="mt-1 text-xs text-red-500 list-disc list-inside max-w-[16rem]">
                                        @foreach ($laptopProblems as $problem)
                                            <li>{{ $problem }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                                <a href="{{ route('laptops.edit', $laptop) }}" class="text-[#7a1220] hover:underline font-medium">Edit</a>
                                <form action="{{ route('laptops.destroy', $laptop) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Hapus laptop ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline font-medium">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-400">
                                Belum ada laptop. Klik "+ Tambah Laptop" untuk mulai memasukkan alternatif.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="text-[11px] text-slate-400 px-4 py-2 sm:hidden">← Geser tabel ke kiri/kanan untuk melihat semua kolom</p>
    </div>
@endsection
