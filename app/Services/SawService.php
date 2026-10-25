<?php

namespace App\Services;

use App\Models\Criterion;
use App\Models\Laptop;
use Illuminate\Support\Collection;

class SawService
{
    /** Kolom tabel laptops yang boleh dipakai sebagai sumber nilai kriteria. */
    public const SPEC_FIELDS = ['price', 'processor_score', 'ram_gb', 'storage_gb', 'storage_type'];

    public function __construct(protected WeightService $weights)
    {
    }

    /**
     * Jalankan SAW dan kembalikan semua tahap perhitungan (untuk halaman yang transparan).
     *
     * Tahapan:
     *  1. matrix     x_ij : nilai tiap laptop per kriteria (nilai asli atau skor rubrik)
     *  2. normalized r_ij : benefit = x / max(kolom) ; cost = min(kolom) / x
     *  3. weighted   v_ij : w_j * r_ij
     *  4. totals     V_i  : jumlah v_ij, lalu diurutkan menjadi ranking
     *
     * Laptop yang datanya belum lengkap atau tidak punya rubrik yang cocok TIDAK dihitung,
     * dan alasannya dikembalikan di 'excluded'.
     */
    public function calculate(string $weightMethod = 'roc'): array
    {
        $criteria = Criterion::with('levels')->orderBy('sort_order')->get();

        $survey = $this->weights->fromSurvey($criteria);
        $method = $this->weights->normalizeMethod($weightMethod);
        $weights = $survey[$method];

        $matrix = [];
        $valid = new Collection();
        $excluded = [];

        foreach (Laptop::orderBy('name')->get() as $laptop) {
            $row = [];
            $problems = [];

            foreach ($criteria as $criterion) {
                try {
                    $row[$criterion->id] = $this->criterionValue($criterion, $laptop);
                } catch (\RuntimeException $e) {
                    $problems[] = "{$criterion->name}: {$e->getMessage()}";
                }
            }

            if ($problems !== []) {
                $excluded[] = ['laptop' => $laptop, 'problems' => $problems];
                continue;
            }

            $matrix[$laptop->id] = $row;
            $valid->push($laptop);
        }

        $normalized = $this->normalize($matrix, $criteria);

        $weighted = [];
        $totals = [];
        foreach ($matrix as $laptopId => $row) {
            $total = 0.0;
            foreach ($criteria as $criterion) {
                $value = $weights[$criterion->id] * $normalized[$laptopId][$criterion->id];
                $weighted[$laptopId][$criterion->id] = $value;
                $total += $value;
            }
            $totals[$laptopId] = $total;
        }

        return [
            'criteria' => $criteria,
            'weight_method' => $method,
            'weights' => $weights,
            'survey' => $survey,
            'weight_fallback' => $survey['fallback'],
            'laptops' => $valid,
            'matrix' => $matrix,
            'normalized' => $normalized,
            'weighted' => $weighted,
            'totals' => $totals,
            'ranking' => $this->buildRanking($valid, $totals),
            'excluded' => $excluded,
        ];
    }

    /**
     * Nilai x_ij satu laptop pada satu kriteria.
     *
     * @throws \RuntimeException jika data tidak bisa dihitung
     */
    protected function criterionValue(Criterion $criterion, Laptop $laptop): float
    {
        if (! in_array($criterion->spec_field, self::SPEC_FIELDS, true)) {
            throw new \RuntimeException("kolom spesifikasi \"{$criterion->spec_field}\" tidak dikenal");
        }

        $value = $laptop->getAttribute($criterion->spec_field);

        if ($value === null || $value === '') {
            throw new \RuntimeException('nilai spesifikasi belum diisi');
        }

        if (! $criterion->usesRubric()) {
            if (! is_numeric($value)) {
                throw new \RuntimeException('nilai spesifikasi harus berupa angka');
            }

            if ($criterion->isCost() && (float) $value <= 0) {
                throw new \RuntimeException('nilai kriteria cost harus lebih dari 0');
            }

            return (float) $value;
        }

        return (float) $this->rubricScore($criterion, $value);
    }

    /**
     * Cari skor rubrik. Level numerik (min/max terisi) dicocokkan dengan rentang inklusif;
     * level kategorikal (min dan max kosong) dicocokkan dengan label (tanpa beda huruf besar/kecil).
     */
    protected function rubricScore(Criterion $criterion, mixed $value): int
    {
        if ($criterion->levels->isEmpty()) {
            throw new \RuntimeException('rubrik belum diisi');
        }

        foreach ($criterion->levels as $level) {
            if ($level->min_value === null && $level->max_value === null) {
                if (mb_strtolower(trim($level->label)) === mb_strtolower(trim((string) $value))) {
                    return $level->score;
                }
                continue;
            }

            if (! is_numeric($value)) {
                continue;
            }

            $number = (float) $value;
            $aboveMin = $level->min_value === null || $number >= $level->min_value;
            $belowMax = $level->max_value === null || $number <= $level->max_value;

            if ($aboveMin && $belowMax) {
                return $level->score;
            }
        }

        throw new \RuntimeException("tidak ada rubrik yang cocok untuk nilai \"{$value}\"");
    }

    /**
     * @return array<int,array<int,float>> [laptop_id][criterion_id] => r_ij
     */
    protected function normalize(array $matrix, Collection $criteria): array
    {
        $normalized = [];

        if ($matrix === []) {
            return $normalized;
        }

        foreach ($criteria as $criterion) {
            $column = array_column($matrix, $criterion->id);
            $max = max($column);
            $min = min($column);

            foreach ($matrix as $laptopId => $row) {
                $x = $row[$criterion->id];

                if ($criterion->isCost()) {
                    $normalized[$laptopId][$criterion->id] = $x > 0 ? $min / $x : 0.0;
                } else {
                    $normalized[$laptopId][$criterion->id] = $max > 0 ? $x / $max : 0.0;
                }
            }
        }

        return $normalized;
    }

    /**
     * Urutkan dari skor tertinggi. Skor yang sama (dibulatkan 10 desimal) berbagi peringkat
     * yang sama (1, 2, 2, 4, ...).
     *
     * @return array<int,array{rank:int, laptop:Laptop, score:float}>
     */
    protected function buildRanking(Collection $laptops, array $totals): array
    {
        $sorted = $laptops->sort(function (Laptop $a, Laptop $b) use ($totals) {
            $diff = round($totals[$b->id], 10) <=> round($totals[$a->id], 10);

            return $diff !== 0 ? $diff : strcmp($a->name, $b->name);
        })->values();

        $ranking = [];
        $previousScore = null;
        $previousRank = 0;

        foreach ($sorted as $index => $laptop) {
            $score = round($totals[$laptop->id], 10);
            $rank = ($previousScore !== null && $score === $previousScore) ? $previousRank : $index + 1;

            $ranking[] = ['rank' => $rank, 'laptop' => $laptop, 'score' => $totals[$laptop->id]];

            $previousScore = $score;
            $previousRank = $rank;
        }

        return $ranking;
    }
}
