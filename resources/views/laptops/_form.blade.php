{{-- Variabel: $laptop, $storageTypes, $action, $method (POST|PUT), $submitLabel --}}
<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <h2 class="text-base font-semibold text-slate-800 mb-3">Identitas</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tipe laptop</label>
                <input type="text" name="name" value="{{ old('name', $laptop->name) }}" required placeholder="mis. ASUS VivoBook 14 A1404"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b] focus:border-[#8a1c2b]">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Merek (opsional)</label>
                <input type="text" name="brand" value="{{ old('brand', $laptop->brand) }}" placeholder="mis. ASUS"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b] focus:border-[#8a1c2b]">
            </div>
        </div>
    </div>

    <div>
        <h2 class="text-base font-semibold text-slate-800 mb-3">Spesifikasi</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Harga (Rp)</label>
                <input type="number" name="price" min="1" step="1" value="{{ old('price', $laptop->price) }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b] focus:border-[#8a1c2b]">
                <p class="text-xs text-slate-400 mt-1">Angka saja tanpa titik, mis. 6500000.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Processor</label>
                <input type="text" name="processor_name" value="{{ old('processor_name', $laptop->processor_name) }}" required placeholder="mis. Intel Core i5-1235U"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b] focus:border-[#8a1c2b]">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Skor benchmark processor</label>
                <input type="number" name="processor_score" min="0" step="1" value="{{ old('processor_score', $laptop->processor_score) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b] focus:border-[#8a1c2b]">
                <p class="text-xs text-slate-400 mt-1">Skor dari benchmark publik. Tulis nama benchmark dan tanggalnya di Catatan.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">RAM (GB)</label>
                <input type="number" name="ram_gb" min="1" step="1" value="{{ old('ram_gb', $laptop->ram_gb) }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b] focus:border-[#8a1c2b]">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tipe penyimpanan</label>
                <select name="storage_type" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b]">
                    <option value="" disabled @selected(old('storage_type', $laptop->storage_type) === null)>Pilih tipe</option>
                    @foreach ($storageTypes as $type)
                        <option value="{{ $type }}" @selected(old('storage_type', $laptop->storage_type) === $type)>{{ $type }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-400 mt-1">Pilihannya mengikuti label rubrik Tipe Penyimpanan.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Kapasitas penyimpanan (GB)</label>
                <input type="number" name="storage_gb" min="1" step="1" value="{{ old('storage_gb', $laptop->storage_gb) }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b] focus:border-[#8a1c2b]">
                <p class="text-xs text-slate-400 mt-1">1 TB = 1000 (atau 1024) GB.</p>
            </div>
        </div>
    </div>

    <div>
        <h2 class="text-base font-semibold text-slate-800 mb-3">Sumber data</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Sumber spesifikasi dan harga</label>
                <textarea name="spec_source" rows="2" placeholder="URL atau nama toko/situs"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b] focus:border-[#8a1c2b]">{{ old('spec_source', $laptop->spec_source) }}</textarea>
                {{-- <input type="text" name="spec_source" value="{{ old('spec_source', $laptop->spec_source) }}" placeholder="URL atau nama toko/situs"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b] focus:border-[#8a1c2b]"> --}}
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal diakses</label>
                <input type="date" name="spec_accessed_on" max="{{ now()->toDateString() }}"
                       value="{{ old('spec_accessed_on', $laptop->spec_accessed_on?->format('Y-m-d')) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b] focus:border-[#8a1c2b]">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Catatan (opsional)</label>
                <textarea name="notes" rows="3"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#8a1c2b] focus:border-[#8a1c2b]">{{ old('notes', $laptop->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-3 pt-2">
        <button type="submit"
                class="bg-[#7a1220] text-white rounded-lg px-6 py-2.5 text-sm font-semibold hover:bg-[#5c0d18] transition shadow-sm">
            {{ $submitLabel }}
        </button>
        <a href="{{ route('laptops.index') }}"
           class="border border-slate-300 rounded-lg px-6 py-2.5 text-sm font-medium hover:bg-slate-50 text-center">
            Batal
        </a>
    </div>
</form>
