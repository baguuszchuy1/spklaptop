@extends('layouts.app')

@section('title', 'Data Survei')

@section('content')
    <div x-data="{ selected: [], allIds: {{ $respondents->pluck('id')->toJson() }} }">
        <h1 class="text-2xl font-bold mb-1 text-slate-800">Data Survei</h1>
        <p class="text-sm text-slate-500 mb-6">
            Jawaban kuesioner prioritas kriteria (Likert dan urutan prioritas) yang menjadi dasar bobot.
        </p>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('import_report'))
            @php $report = session('import_report'); @endphp
            <div class="mb-4 rounded-lg border px-4 py-3 text-sm
                        {{ $report['failed'] === [] ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-amber-50 border-amber-200 text-amber-800' }}">
                <p class="font-medium">
                    Impor selesai: {{ $report['imported'] }} responden baru,
                    {{ $report['skipped'] }} dilewati (sudah pernah diimpor),
                    {{ count($report['failed']) }} gagal.
                </p>
                @if ($report['failed'] !== [])
                    <ul class="mt-2 list-disc list-inside text-xs">
                        @foreach ($report['failed'] as $failure)
                            <li>{{ $failure }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-2 text-xs">Baris yang gagal tidak disimpan. Perbaiki di Google Sheets lalu impor ulang, baris yang sudah masuk tidak akan dobel.</p>
                @endif
            </div>
        @endif

        <!-- Unggah CSV -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-6 mb-6">
            <h2 class="text-lg font-semibold mb-1 text-slate-800">Impor dari Google Form</h2>
            <p class="text-sm text-slate-500 mb-4">
                Buka Google Sheets hasil respons, lalu <strong>File &rarr; Download &rarr; Comma-separated values (.csv)</strong>
                dan unggah di sini. Kolom dikenali dari judulnya, jadi urutan kolom tidak masalah.
            </p>
            <form method="POST" action="{{ route('survey.import') }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row sm:items-center gap-3">
                @csrf
                <input type="file" name="file" accept=".csv,.txt" required
                       class="text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-[#fdf1f2] file:px-4 file:py-2 file:text-sm file:font-medium file:text-[#7a1220] hover:file:bg-[#f3d6d9]">
                <button type="submit"
                        class="bg-[#7a1220] text-white text-sm font-medium rounded-lg px-5 py-2.5 hover:bg-[#5c0d18] transition">
                    Impor CSV
                </button>
            </form>
        </div>

        <!-- Ringkasan -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5">
                <p class="text-xs text-slate-500 mb-1">Total responden</p>
                <p class="text-2xl font-bold text-slate-800">{{ $total }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5">
                <p class="text-xs text-slate-500 mb-1">Valid (dipakai menghitung bobot)</p>
                <p class="text-2xl font-bold {{ $summary['respondents_used'] > 0 ? 'text-emerald-600' : 'text-amber-500' }}">{{ $summary['respondents_used'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5">
                <p class="text-xs text-slate-500 mb-1">Tidak valid (dilewati)</p>
                <p class="text-2xl font-bold {{ $summary['respondents_skipped'] > 0 ? 'text-red-500' : 'text-slate-800' }}">{{ $summary['respondents_skipped'] }}</p>
            </div>
        </div>

        <!-- Aksi massal -->
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div x-show="selected.length > 0" x-cloak
                 class="flex items-center gap-3 bg-[#fdf1f2] border border-[#f3d6d9] rounded-lg px-4 py-2">
                <p class="text-sm text-[#5c0d18]"><span x-text="selected.length"></span> dipilih</p>
                <form action="{{ route('survey.bulkDestroy') }}" method="POST"
                      onsubmit="return confirm('Hapus responden yang dipilih? Tindakan ini tidak bisa dibatalkan.');">
                    @csrf
                    @method('DELETE')
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>
                    <button type="submit" class="bg-red-600 text-white text-sm font-medium rounded-lg px-4 py-1.5 hover:bg-red-700 transition">Hapus Terpilih</button>
                </form>
            </div>

            @if ($total > 0)
                <form action="{{ route('survey.destroyAll') }}" method="POST" class="ml-auto"
                      onsubmit="return confirm('Hapus SELURUH data survei ({{ $total }} responden)? Pakai ini untuk membersihkan data uji coba sebelum pengumpulan data asli.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-500 hover:underline font-medium">Hapus semua data survei</button>
                </form>
            @endif
        </div>

        <!-- Tabel -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[820px]">
                    <thead class="bg-slate-50 text-slate-500 text-left">
                        <tr>
                            <th class="px-4 py-3 w-10">
                                <input type="checkbox" class="accent-[#7a1220]"
                                       @change="selected = $event.target.checked ? [...allIds] : []"
                                       :checked="selected.length === allIds.length && allIds.length > 0">
                            </th>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Angkatan</th>
                            <th class="px-4 py-3">Jenis kelamin</th>
                            <th class="px-4 py-3">Laptop dipakai</th>
                            <th class="px-4 py-3">Waktu isi</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($respondents as $respondent)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3">
                                    <input type="checkbox" class="accent-[#7a1220]" value="{{ $respondent->id }}" x-model="selected">
                                </td>
                                <td class="px-4 py-3 font-medium">{{ $respondent->name }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $respondent->cohort_year ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $respondent->gender ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $respondent->laptop_used ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-slate-500">{{ $respondent->submitted_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($validity[$respondent->id] ?? false)
                                        <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">Valid</span>
                                    @else
                                        <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full bg-red-50 text-red-600">Tidak valid</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <form action="{{ route('survey.destroy', $respondent) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Hapus responden ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline font-medium">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-slate-400">
                                    Belum ada data survei. Unggah file CSV dari Google Sheets di atas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="text-[11px] text-slate-400 px-4 py-2 sm:hidden">← Geser tabel ke kiri/kanan untuk melihat semua kolom</p>
        </div>

        <div class="mt-4">
            {{ $respondents->links() }}
        </div>
    </div>
@endsection
