@extends('layouts.app')

@section('title', 'Kriteria & Rubrik')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Kriteria & Rubrik</h1>
            <p class="text-sm text-slate-500">Kriteria penilaian laptop, arah penilaiannya (benefit/cost), dan rubrik konversi spesifikasi ke skor.</p>
        </div>

        @if ($hasSurveyData)
            <span class="text-xs text-slate-400 sm:text-right">Jumlah kriteria terkunci<br>(data survei sudah ada)</span>
        @else
            <a href="{{ route('criteria.create') }}"
               class="bg-[#7a1220] text-white text-sm font-medium rounded-lg px-4 py-2.5 hover:bg-[#5c0d18] transition text-center">
                + Tambah Kriteria
            </a>
        @endif
    </div>

    <div class="mb-6 rounded-xl bg-[#fdf1f2] border border-[#f3d6d9] text-[#5c0d18] px-4 py-3 text-sm">
        Kriteria di sini sama dengan lima pertanyaan di Google Form. Setelah data survei masuk, jumlah kriteria
        dikunci supaya urutan prioritas responden tetap valid. Nama, arah penilaian, dan rubrik tetap bisa diubah.
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[780px]">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Arah</th>
                        <th class="px-4 py-3">Sumber nilai</th>
                        <th class="px-4 py-3">Mode skor</th>
                        <th class="px-4 py-3">Level rubrik</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($criteria as $criterion)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3 font-semibold whitespace-nowrap">{{ $criterion->code }}</td>
                            <td class="px-4 py-3 font-medium whitespace-nowrap">{{ $criterion->name }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full
                                             {{ $criterion->isCost() ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                                    {{ $criterion->isCost() ? 'Cost' : 'Benefit' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $specFields[$criterion->spec_field] ?? $criterion->spec_field }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $criterion->usesRubric() ? 'Rubrik' : 'Nilai asli' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($criterion->usesRubric())
                                    @if ($criterion->levels_count === 0)
                                        <span class="text-xs font-medium text-red-500">Belum ada level</span>
                                    @else
                                        {{ $criterion->levels_count }} level
                                    @endif
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                                <a href="{{ route('criteria.edit', $criterion) }}" class="text-[#7a1220] hover:underline font-medium">Edit</a>
                                @unless ($hasSurveyData)
                                    <form action="{{ route('criteria.destroy', $criterion) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Hapus kriteria ini beserta rubriknya?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline font-medium">Hapus</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada kriteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
