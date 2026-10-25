<?php

namespace App\Services;

use App\Models\Criterion;
use App\Models\SurveyRespondent;
use Illuminate\Support\Collection;

class WeightService
{
    public const METHODS = ['roc', 'likert', 'equal'];

    /**
     * Bobot Rank Order Centroid (ROC) untuk n kriteria:
     *   w_k = (1/n) * SUM_{i=k..n} (1/i)
     *
     * @return array<int,float> kunci = urutan prioritas (1..n), nilai = bobot
     */
    public function rocWeights(int $n): array
    {
        $weights = [];

        for ($k = 1; $k <= $n; $k++) {
            $sum = 0.0;
            for ($i = $k; $i <= $n; $i++) {
                $sum += 1 / $i;
            }
            $weights[$k] = $sum / $n;
        }

        return $weights;
    }

    /**
     * Hitung bobot kriteria dari data survei.
     *
     * - roc    : tiap responden mengubah urutan prioritasnya jadi bobot ROC, lalu dirata-rata
     *            antarresponden.
     * - likert : rata-rata skor kepentingan (1-5) tiap kriteria dibagi jumlah seluruh rata-rata.
     * - equal  : semua kriteria sama (1/n).
     *
     * Responden yang jawabannya tidak lengkap atau urutannya tidak valid dilewati.
     * Jika belum ada responden valid, semua metode memakai bobot sama rata dan 'fallback' = true.
     *
     * @return array{
     *   roc: array<int,float>, likert: array<int,float>, equal: array<int,float>,
     *   mean_rank: array<int,float>, mean_likert: array<int,float>,
     *   respondents_used: int, respondents_skipped: int, fallback: bool
     * }  semua array berkunci criterion_id
     */
    public function fromSurvey(?Collection $criteria = null): array
    {
        $criteria ??= Criterion::orderBy('sort_order')->get();
        $ids = $criteria->pluck('id')->all();
        $n = count($ids);

        $equal = $n > 0 ? array_fill_keys($ids, 1 / $n) : [];
        $roc = $this->rocWeights($n);

        $rocSum = array_fill_keys($ids, 0.0);
        $likertSum = array_fill_keys($ids, 0.0);
        $rankSum = array_fill_keys($ids, 0.0);
        $used = 0;
        $skipped = 0;

        foreach (SurveyRespondent::with('responses')->get() as $respondent) {
            $answers = $respondent->responses->keyBy('criterion_id');

            if (! $this->hasValidAnswers($answers, $ids, $n)) {
                $skipped++;
                continue;
            }

            foreach ($ids as $id) {
                $answer = $answers[$id];
                $rocSum[$id] += $roc[$answer->priority_rank];
                $likertSum[$id] += $answer->likert;
                $rankSum[$id] += $answer->priority_rank;
            }

            $used++;
        }

        if ($used === 0) {
            return [
                'roc' => $equal,
                'likert' => $equal,
                'equal' => $equal,
                'mean_rank' => [],
                'mean_likert' => [],
                'respondents_used' => 0,
                'respondents_skipped' => $skipped,
                'fallback' => true,
            ];
        }

        $meanLikert = array_map(fn ($sum) => $sum / $used, $likertSum);
        $likertTotal = array_sum($meanLikert);

        return [
            'roc' => array_map(fn ($sum) => $sum / $used, $rocSum),
            'likert' => array_map(fn ($mean) => $mean / $likertTotal, $meanLikert),
            'equal' => $equal,
            'mean_rank' => array_map(fn ($sum) => $sum / $used, $rankSum),
            'mean_likert' => $meanLikert,
            'respondents_used' => $used,
            'respondents_skipped' => $skipped,
            'fallback' => false,
        ];
    }

    public function normalizeMethod(string $method): string
    {
        return in_array($method, self::METHODS, true) ? $method : 'roc';
    }

    /**
     * Korelasi peringkat Spearman antara dua set bobot berkunci sama (mis. ROC vs Likert).
     * Nilai kembar mendapat peringkat rata-rata. Null jika tidak bisa dihitung.
     *
     * @param  array<int,float>  $a
     * @param  array<int,float>  $b
     */
    public function rankCorrelation(array $a, array $b): ?float
    {
        $n = count($a);

        if ($n < 2 || array_keys($a) !== array_keys($b)) {
            return null;
        }

        $ranksA = $this->averageRanks($a);
        $ranksB = $this->averageRanks($b);

        $meanA = array_sum($ranksA) / $n;
        $meanB = array_sum($ranksB) / $n;

        $numerator = 0.0;
        $varA = 0.0;
        $varB = 0.0;

        foreach (array_keys($a) as $key) {
            $x = $ranksA[$key] - $meanA;
            $y = $ranksB[$key] - $meanB;
            $numerator += $x * $y;
            $varA += $x * $x;
            $varB += $y * $y;
        }

        if ($varA == 0.0 || $varB == 0.0) {
            return null;
        }

        return $numerator / sqrt($varA * $varB);
    }

    /**
     * Peringkat 1 = nilai terbesar; nilai yang (hampir) sama berbagi rata-rata posisinya.
     *
     * @return array<int,float>
     */
    protected function averageRanks(array $values): array
    {
        arsort($values);

        $keys = array_keys($values);
        $numbers = array_values($values);
        $count = count($numbers);
        $ranks = [];

        $i = 0;
        while ($i < $count) {
            $j = $i;
            while ($j + 1 < $count && abs($numbers[$j + 1] - $numbers[$i]) < 1e-12) {
                $j++;
            }

            $average = ($i + $j) / 2 + 1;
            for ($k = $i; $k <= $j; $k++) {
                $ranks[$keys[$k]] = $average;
            }

            $i = $j + 1;
        }

        return $ranks;
    }

    /**
     * Jawaban valid = semua kriteria terjawab, Likert 1-5, dan urutan prioritas
     * adalah permutasi 1..n (tiap prioritas dipakai tepat sekali).
     */
    public function hasValidAnswers(Collection $answers, array $ids, int $n): bool
    {
        $ranks = [];

        foreach ($ids as $id) {
            $answer = $answers->get($id);

            if (! $answer || $answer->likert < 1 || $answer->likert > 5) {
                return false;
            }

            $ranks[] = $answer->priority_rank;
        }

        sort($ranks);

        return $ranks === range(1, $n);
    }
}
