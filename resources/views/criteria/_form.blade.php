{{-- Variabel: $criterion, $types, $scoringModes, $specFields, $action, $method (POST|PUT), $submitLabel --}}
<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Kode</label>
            <input type="text" name="code" value="{{ old('code', $criterion->code) }}" required maxlength="10"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b] focus:border-[#8a1c2b]">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nama kriteria</label>
            <input type="text" name="name" value="{{ old('name', $criterion->name) }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b] focus:border-[#8a1c2b]">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Arah penilaian</label>
            <select name="type" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b]">
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $criterion->type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Urutan tampil</label>
            <input type="number" name="sort_order" min="1" max="99" value="{{ old('sort_order', $criterion->sort_order) }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b] focus:border-[#8a1c2b]">
            <p class="text-xs text-slate-400 mt-1">Samakan dengan urutan soal di Google Form.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Sumber nilai (kolom spesifikasi laptop)</label>
            <select name="spec_field" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b]">
                @foreach ($specFields as $value => $label)
                    <option value="{{ $value }}" @selected(old('spec_field', $criterion->spec_field) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Cara memberi nilai</label>
            <select name="scoring_mode" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b]">
                @foreach ($scoringModes as $value => $label)
                    <option value="{{ $value }}" @selected(old('scoring_mode', $criterion->scoring_mode) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-3 pt-2">
        <button type="submit"
                class="bg-[#7a1220] text-white rounded-lg px-6 py-2.5 text-sm font-semibold hover:bg-[#5c0d18] transition shadow-sm">
            {{ $submitLabel }}
        </button>
        <a href="{{ route('criteria.index') }}"
           class="border border-slate-300 rounded-lg px-6 py-2.5 text-sm font-medium hover:bg-slate-50 text-center">
            Kembali
        </a>
    </div>
</form>
