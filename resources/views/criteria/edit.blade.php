@extends('layouts.app')

@section('title', 'Edit Kriteria')

@section('content')
    <a href="{{ route('criteria.index') }}" class="text-sm text-slate-500 hover:text-[#7a1220]">&larr; Kembali ke daftar kriteria</a>
    <h1 class="text-2xl font-bold mt-2 mb-6 text-slate-800">{{ $criterion->code }} &middot; {{ $criterion->name }}</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (! empty($warnings))
        <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">
            <p class="font-medium mb-1">Perlu diperiksa pada rubrik:</p>
            <ul class="list-disc list-inside">
                @foreach ($warnings as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="space-y-6 max-w-4xl">
        <!-- Data kriteria -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-6">
            <h2 class="text-lg font-semibold mb-4 text-slate-800">Data Kriteria</h2>
            @include('criteria._form', [
                'action' => route('criteria.update', $criterion),
                'method' => 'PUT',
                'submitLabel' => 'Simpan Perubahan',
            ])
        </div>

        <!-- Rubrik -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-6">
            <h2 class="text-lg font-semibold mb-1 text-slate-800">Rubrik Skor</h2>

            @if (! $criterion->usesRubric())
                <p class="text-sm text-slate-500">
                    Kriteria ini memakai nilai asli (tanpa rubrik), jadi bagian ini tidak dipakai dalam perhitungan.
                    Ubah "Cara memberi nilai" menjadi Rubrik kalau ingin mengonversi spesifikasi ke skor.
                </p>
            @else
                <div class="text-sm text-slate-500 mb-4 space-y-1">
                    @if ($isCategorical)
                        <p>Kriteria ini <strong>kategorikal</strong>: kosongkan Min dan Max, dan isi Label persis sama dengan nilai tipe penyimpanan laptop (mis. HDD, SSD SATA, SSD NVMe, SSD + HDD).</p>
                    @else
                        <p>Kriteria ini <strong>numerik</strong>: tiap level punya rentang Min sampai Max (inklusif). Kosongkan Min atau Max untuk "tanpa batas". Pastikan rentang antarlevel saling menyambung tanpa celah dan tanpa tumpang tindih.</p>
                    @endif
                    <p>Skor rubrik berkisar 1 sampai 5.
                        @if ($criterion->isCost())
                            <strong>Karena kriteria ini bertipe Cost</strong>, beri skor <strong>kecil</strong> untuk nilai yang diinginkan (mis. harga murah) dan skor besar untuk nilai yang merugikan, sebab normalisasi cost akan membaliknya. Kalau skor dibuat besar untuk yang diinginkan, hasilnya terbalik dua kali.
                        @else
                            Beri skor <strong>besar</strong> untuk nilai yang lebih baik.
                        @endif
                    </p>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($criterion->levels as $level)
                        <div class="py-3 flex flex-col lg:flex-row lg:items-end gap-2">
                            <form method="POST" action="{{ route('levels.update', $level) }}" class="flex flex-wrap items-end gap-2 flex-1">
                                @csrf
                                @method('PUT')
                                <div class="w-full sm:w-48">
                                    <label class="block text-[11px] text-slate-400 mb-0.5">Label</label>
                                    <input type="text" name="label" value="{{ $level->label }}" required
                                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b]">
                                </div>
                                @unless ($isCategorical)
                                    <div class="w-28">
                                        <label class="block text-[11px] text-slate-400 mb-0.5">Min</label>
                                        <input type="number" step="any" name="min_value" value="{{ $level->min_value }}"
                                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b]">
                                    </div>
                                    <div class="w-28">
                                        <label class="block text-[11px] text-slate-400 mb-0.5">Max</label>
                                        <input type="number" step="any" name="max_value" value="{{ $level->max_value }}"
                                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b]">
                                    </div>
                                @endunless
                                <div class="w-20">
                                    <label class="block text-[11px] text-slate-400 mb-0.5">Skor</label>
                                    <input type="number" name="score" min="1" max="5" value="{{ $level->score }}" required
                                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b]">
                                </div>
                                <button type="submit"
                                        class="border border-[#7a1220] text-[#7a1220] text-sm font-medium rounded-lg px-4 py-2 hover:bg-[#fdf1f2] transition">
                                    Simpan
                                </button>
                            </form>

                            <form method="POST" action="{{ route('levels.destroy', $level) }}"
                                  onsubmit="return confirm('Hapus level rubrik ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:underline text-sm font-medium px-2 py-2">Hapus</button>
                            </form>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-slate-400">Belum ada level. Tambahkan di bawah.</p>
                    @endforelse
                </div>

                <!-- Tambah level -->
                <div class="mt-4 pt-4 border-t border-slate-200">
                    <h3 class="text-sm font-semibold text-slate-700 mb-2">Tambah level baru</h3>
                    @php $isNew = old('_form') === 'new'; @endphp
                    <form method="POST" action="{{ route('criteria.levels.store', $criterion) }}" class="flex flex-wrap items-end gap-2">
                        @csrf
                        <input type="hidden" name="_form" value="new">
                        <div class="w-full sm:w-48">
                            <label class="block text-[11px] text-slate-400 mb-0.5">Label</label>
                            <input type="text" name="label" value="{{ $isNew ? old('label') : '' }}" required
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b]">
                        </div>
                        @unless ($isCategorical)
                            <div class="w-28">
                                <label class="block text-[11px] text-slate-400 mb-0.5">Min</label>
                                <input type="number" step="any" name="min_value" value="{{ $isNew ? old('min_value') : '' }}"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b]">
                            </div>
                            <div class="w-28">
                                <label class="block text-[11px] text-slate-400 mb-0.5">Max</label>
                                <input type="number" step="any" name="max_value" value="{{ $isNew ? old('max_value') : '' }}"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b]">
                            </div>
                        @endunless
                        <div class="w-20">
                            <label class="block text-[11px] text-slate-400 mb-0.5">Skor</label>
                            <input type="number" name="score" min="1" max="5" value="{{ $isNew ? old('score') : '' }}" required
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b]">
                        </div>
                        <button type="submit"
                                class="bg-[#7a1220] text-white text-sm font-medium rounded-lg px-4 py-2 hover:bg-[#5c0d18] transition">
                            + Tambah
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
