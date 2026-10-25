<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Models\SurveyRespondent;
use App\Services\SawService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CriterionController extends Controller
{
    public const TYPES = [
        'benefit' => 'Benefit (makin besar makin baik)',
        'cost' => 'Cost (makin kecil makin baik)',
    ];

    public const SCORING_MODES = [
        'raw' => 'Nilai asli (langsung dinormalisasi)',
        'rubric' => 'Rubrik (spesifikasi dikonversi ke skor 1-5)',
    ];

    public const SPEC_FIELD_LABELS = [
        'price' => 'Harga (Rp)',
        'processor_score' => 'Skor processor (benchmark)',
        'ram_gb' => 'RAM (GB)',
        'storage_gb' => 'Kapasitas penyimpanan (GB)',
        'storage_type' => 'Tipe penyimpanan (teks: HDD, SSD, ...)',
    ];

    public function index(): View
    {
        return view('criteria.index', [
            'criteria' => Criterion::withCount('levels')->orderBy('sort_order')->get(),
            'hasSurveyData' => SurveyRespondent::exists(),
            'specFields' => self::SPEC_FIELD_LABELS,
        ]);
    }

    public function create(): View|RedirectResponse
    {
        if (SurveyRespondent::exists()) {
            return $this->locked();
        }

        $criterion = new Criterion([
            'type' => 'benefit',
            'scoring_mode' => 'rubric',
            'sort_order' => (int) Criterion::max('sort_order') + 1,
        ]);

        return view('criteria.create', $this->formData($criterion));
    }

    public function store(Request $request): RedirectResponse
    {
        if (SurveyRespondent::exists()) {
            return $this->locked();
        }

        $data = $request->validate($this->rules());

        if ($error = $this->crossFieldError($data)) {
            return back()->withInput()->withErrors($error);
        }

        $criterion = Criterion::create($data);

        return redirect()->route('criteria.edit', $criterion)
            ->with('success', 'Kriteria ditambahkan. Lengkapi rubriknya di bagian bawah halaman ini.');
    }

    public function edit(Criterion $criterion): View
    {
        $criterion->load('levels');

        return view('criteria.edit', $this->formData($criterion) + [
            'warnings' => $this->rubricWarnings($criterion),
            'isCategorical' => $criterion->spec_field === 'storage_type',
            'hasSurveyData' => SurveyRespondent::exists(),
        ]);
    }

    public function update(Request $request, Criterion $criterion): RedirectResponse
    {
        $data = $request->validate($this->rules($criterion));

        if ($error = $this->crossFieldError($data)) {
            return back()->withInput()->withErrors($error);
        }

        $criterion->update($data);

        return redirect()->route('criteria.edit', $criterion)->with('success', 'Kriteria diperbarui.');
    }

    public function destroy(Criterion $criterion): RedirectResponse
    {
        if (SurveyRespondent::exists()) {
            return $this->locked();
        }

        $criterion->delete();

        return redirect()->route('criteria.index')->with('success', 'Kriteria dihapus.');
    }

    protected function rules(?Criterion $criterion = null): array
    {
        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('criteria', 'code')->ignore($criterion?->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'spec_field' => ['required', Rule::in(SawService::SPEC_FIELDS)],
            'scoring_mode' => ['required', Rule::in(array_keys(self::SCORING_MODES))],
            'sort_order' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    /** Aturan lintas-kolom yang tidak bisa dicek oleh validasi biasa. */
    protected function crossFieldError(array $data): ?array
    {
        if ($data['scoring_mode'] === 'raw' && $data['spec_field'] === 'storage_type') {
            return ['scoring_mode' => 'Tipe penyimpanan berupa teks, jadi harus memakai rubrik (bukan nilai asli).'];
        }

        return null;
    }

    protected function formData(Criterion $criterion): array
    {
        return [
            'criterion' => $criterion,
            'types' => self::TYPES,
            'scoringModes' => self::SCORING_MODES,
            'specFields' => self::SPEC_FIELD_LABELS,
        ];
    }

    protected function locked(): RedirectResponse
    {
        return redirect()->route('criteria.index')->with(
            'error',
            'Jumlah kriteria dikunci karena data survei sudah ada. Menambah atau menghapus kriteria akan membuat urutan prioritas responden tidak valid.'
        );
    }

    /**
     * Cek rubrik: level kosong, jenis level tidak cocok dengan jenis kriteria, atau rentang tumpang tindih.
     *
     * @return array<int,string>
     */
    protected function rubricWarnings(Criterion $criterion): array
    {
        if (! $criterion->usesRubric()) {
            return [];
        }

        $levels = $criterion->levels;

        if ($levels->isEmpty()) {
            return ['Kriteria ini memakai rubrik tetapi belum punya level. Laptop tidak bisa dihitung sebelum rubrik diisi.'];
        }

        $warnings = [];
        $categorical = $criterion->spec_field === 'storage_type';

        foreach ($levels as $level) {
            $hasRange = $level->min_value !== null || $level->max_value !== null;

            if ($categorical && $hasRange) {
                $warnings[] = "Level \"{$level->label}\" memakai Min/Max, padahal kriteria ini kategorikal (dicocokkan lewat label).";
            }

            if (! $categorical && ! $hasRange) {
                $warnings[] = "Level \"{$level->label}\" belum punya Min/Max, padahal kriteria ini numerik.";
            }
        }

        if (! $categorical) {
            $ranged = $levels->filter(fn ($l) => $l->min_value !== null || $l->max_value !== null)->values();

            for ($i = 0; $i < $ranged->count(); $i++) {
                for ($j = $i + 1; $j < $ranged->count(); $j++) {
                    $a = $ranged[$i];
                    $b = $ranged[$j];

                    $overlap = ($a->min_value ?? -INF) <= ($b->max_value ?? INF)
                        && ($b->min_value ?? -INF) <= ($a->max_value ?? INF);

                    if ($overlap) {
                        $warnings[] = "Rentang \"{$a->label}\" dan \"{$b->label}\" tumpang tindih. Nilai di irisannya akan memakai level dengan skor lebih rendah.";
                    }
                }
            }
        }

        return $warnings;
    }
}
