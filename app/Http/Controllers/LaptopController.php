<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Models\Laptop;
use App\Services\SawService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LaptopController extends Controller
{
    /** Dipakai kalau rubrik tipe penyimpanan belum punya level. */
    public const DEFAULT_STORAGE_TYPES = ['HDD', 'SSD SATA', 'SSD + HDD', 'SSD NVMe'];

    public function __construct(protected SawService $saw)
    {
    }

    public function index(): View
    {
        // Jalankan SAW hanya untuk mengetahui laptop mana yang datanya belum bisa dihitung.
        $result = $this->saw->calculate('equal');

        $problems = collect($result['excluded'])
            ->mapWithKeys(fn ($row) => [$row['laptop']->id => $row['problems']]);

        return view('laptops.index', [
            'laptops' => Laptop::orderBy('name')->get(),
            'problems' => $problems,
        ]);
    }

    public function create(): View
    {
        $laptop = new Laptop(['spec_accessed_on' => now()->toDateString()]);

        return view('laptops.create', $this->formData($laptop));
    }

    public function store(Request $request): RedirectResponse
    {
        Laptop::create($request->validate($this->rules()));

        return redirect()->route('laptops.index')->with('success', 'Laptop ditambahkan.');
    }

    public function edit(Laptop $laptop): View
    {
        return view('laptops.edit', $this->formData($laptop));
    }

    public function update(Request $request, Laptop $laptop): RedirectResponse
    {
        $laptop->update($request->validate($this->rules($laptop)));

        return redirect()->route('laptops.index')->with('success', 'Data laptop diperbarui.');
    }

    public function destroy(Laptop $laptop): RedirectResponse
    {
        $laptop->delete();

        return redirect()->route('laptops.index')->with('success', 'Laptop dihapus.');
    }

    protected function rules(?Laptop $laptop = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('laptops', 'name')->ignore($laptop?->id)],
            'brand' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'processor_name' => ['required', 'string', 'max:255'],
            'processor_score' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'ram_gb' => ['required', 'integer', 'min:1', 'max:1024'],
            'storage_type' => ['required', Rule::in($this->storageTypes($laptop?->storage_type))],
            'storage_gb' => ['required', 'integer', 'min:1', 'max:100000'],
            'spec_source' => ['nullable', 'string', 'max:2000'],
            'spec_accessed_on' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function formData(Laptop $laptop): array
    {
        return [
            'laptop' => $laptop,
            'storageTypes' => $this->storageTypes($laptop->storage_type),
        ];
    }

    /**
     * Pilihan tipe penyimpanan mengikuti label level rubrik kriteria tipe penyimpanan,
     * supaya isian laptop selalu bisa dicocokkan dengan rubrik. Nilai lama yang sudah tersimpan
     * tetap dimasukkan agar tidak berubah diam-diam saat form dibuka.
     *
     * @return array<int,string>
     */
    protected function storageTypes(?string $current = null): array
    {
        $labels = Criterion::where('spec_field', 'storage_type')
            ->with('levels')
            ->get()
            ->flatMap(fn ($criterion) => $criterion->levels->pluck('label'))
            ->unique()
            ->values()
            ->all();

        $types = $labels !== [] ? $labels : self::DEFAULT_STORAGE_TYPES;

        if ($current !== null && $current !== '' && ! in_array($current, $types, true)) {
            $types[] = $current;
        }

        return $types;
    }
}
