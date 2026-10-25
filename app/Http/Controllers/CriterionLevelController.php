<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Models\CriterionLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CriterionLevelController extends Controller
{
    public function store(Request $request, Criterion $criterion): RedirectResponse
    {
        $criterion->levels()->create($this->validated($request, $criterion));

        return redirect()->route('criteria.edit', $criterion)->with('success', 'Level rubrik ditambahkan.');
    }

    public function update(Request $request, CriterionLevel $level): RedirectResponse
    {
        $level->update($this->validated($request, $level->criterion));

        return redirect()->route('criteria.edit', $level->criterion_id)->with('success', 'Level rubrik diperbarui.');
    }

    public function destroy(CriterionLevel $level): RedirectResponse
    {
        $criterionId = $level->criterion_id;
        $level->delete();

        return redirect()->route('criteria.edit', $criterionId)->with('success', 'Level rubrik dihapus.');
    }

    protected function validated(Request $request, Criterion $criterion): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'min_value' => ['nullable', 'numeric'],
            'max_value' => ['nullable', 'numeric'],
            'score' => ['required', 'integer', 'between:1,5'],
        ]);

        $min = $data['min_value'] ?? null;
        $max = $data['max_value'] ?? null;
        $categorical = $criterion->spec_field === 'storage_type';

        if ($categorical && ($min !== null || $max !== null)) {
            throw ValidationException::withMessages([
                'min_value' => 'Kriteria ini kategorikal: kosongkan Min dan Max, lalu isi Label persis sama dengan nilai spesifikasinya (mis. "SSD NVMe").',
            ]);
        }

        if (! $categorical && $min === null && $max === null) {
            throw ValidationException::withMessages([
                'min_value' => 'Kriteria ini numerik: isi minimal salah satu dari Min atau Max.',
            ]);
        }

        if ($min !== null && $max !== null && (float) $max < (float) $min) {
            throw ValidationException::withMessages([
                'max_value' => 'Max tidak boleh lebih kecil dari Min.',
            ]);
        }

        return [
            'label' => $data['label'],
            'min_value' => $min,
            'max_value' => $max,
            'score' => $data['score'],
        ];
    }
}
