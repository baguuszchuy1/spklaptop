<?php

namespace App\Services;

class SensitivityService
{
    public function __construct(
        protected SawService $saw,
        protected WeightService $weights,
    ) {
    }

    /**
     * Bandingkan hasil SAW dengan tiga cara pembobotan (ROC, Likert, sama rata).
     *
     * @return array{
     *   methods: array<string,array{weights:array,totals:array,ranking:array,ranks:array}>,
     *   correlations: array{likert:?float, equal:?float},
     *   top_same: bool
     * }
     */
    public function compareMethods(): array
    {
        $methods = [];

        foreach (WeightService::METHODS as $method) {
            $result = $this->saw->calculate($method);

            $methods[$method] = [
                'weights' => $result['weights'],
                'totals' => $result['totals'],
                'ranking' => $result['ranking'],
                'ranks' => collect($result['ranking'])->mapWithKeys(fn ($row) => [$row['laptop']->id => $row['rank']])->all(),
            ];
        }

        $roc = $methods['roc']['totals'];
        $topRoc = $this->topIds($roc);

        return [
            'methods' => $methods,
            'correlations' => [
                'likert' => $this->weights->rankCorrelation($roc, $methods['likert']['totals']),
                'equal' => $this->weights->rankCorrelation($roc, $methods['equal']['totals']),
            ],
            'top_same' => $topRoc === $this->topIds($methods['likert']['totals'])
                && $topRoc === $this->topIds($methods['equal']['totals']),
        ];
    }

    /**
     * Analisis sensitivitas satu-per-satu: bobot tiap kriteria dinaikkan dan diturunkan sebesar
     * $delta (relatif, mis. 0,2 = 20%), bobot lain disesuaikan agar total tetap 1, lalu ranking
     * dihitung ulang memakai matriks ternormalisasi yang sama.
     *
     * @param  array  $result  keluaran SawService::calculate()
     * @return array{
     *   delta: float,
     *   scenarios: array<int,array>,
     *   stable_top: bool, stable_order: bool, min_correlation: ?float
     * }
     */
    public function perturbWeights(array $result, float $delta = 0.2): array
    {
        $criteria = $result['criteria'];
        $weights = $result['weights'];
        $normalized = $result['normalized'];
        $baseTotals = $result['totals'];

        $empty = ['delta' => $delta, 'scenarios' => [], 'stable_top' => true, 'stable_order' => true, 'min_correlation' => null];

        if ($normalized === [] || $criteria->isEmpty()) {
            return $empty;
        }

        $baseTop = $this->topIds($baseTotals);
        $baseOrder = $this->orderIds($baseTotals);
        $scenarios = [];

        foreach ($criteria as $criterion) {
            foreach ([-$delta, $delta] as $change) {
                $modified = $weights;
                $modified[$criterion->id] = $weights[$criterion->id] * (1 + $change);

                $sum = array_sum($modified);
                if ($sum <= 0) {
                    continue;
                }

                $modified = array_map(fn ($weight) => $weight / $sum, $modified);

                $totals = [];
                foreach ($normalized as $laptopId => $row) {
                    $total = 0.0;
                    foreach ($criteria as $other) {
                        $total += $modified[$other->id] * $row[$other->id];
                    }
                    $totals[$laptopId] = $total;
                }

                $scenarios[] = [
                    'criterion' => $criterion,
                    'change' => $change,
                    'weights' => $modified,
                    'totals' => $totals,
                    'top_ids' => $this->topIds($totals),
                    'top_same' => $this->topIds($totals) === $baseTop,
                    'same_order' => $this->orderIds($totals) === $baseOrder,
                    'correlation' => $this->weights->rankCorrelation($baseTotals, $totals),
                ];
            }
        }

        $correlations = array_filter(array_column($scenarios, 'correlation'), fn ($value) => $value !== null);

        return [
            'delta' => $delta,
            'scenarios' => $scenarios,
            'stable_top' => ! in_array(false, array_column($scenarios, 'top_same'), true),
            'stable_order' => ! in_array(false, array_column($scenarios, 'same_order'), true),
            'min_correlation' => $correlations === [] ? null : min($correlations),
        ];
    }

    /**
     * Id laptop dengan skor tertinggi (bisa lebih dari satu kalau seri), diurutkan naik.
     *
     * @return array<int,int>
     */
    protected function topIds(array $totals): array
    {
        if ($totals === []) {
            return [];
        }

        $rounded = array_map(fn ($total) => round($total, 10), $totals);
        $max = max($rounded);

        $ids = array_keys(array_filter($rounded, fn ($total) => $total === $max));
        sort($ids);

        return $ids;
    }

    /**
     * Id laptop dari skor tertinggi ke terendah (seri diurutkan menurut id).
     *
     * @return array<int,int>
     */
    protected function orderIds(array $totals): array
    {
        $ids = array_keys($totals);

        usort($ids, function ($a, $b) use ($totals) {
            $diff = round($totals[$b], 10) <=> round($totals[$a], 10);

            return $diff !== 0 ? $diff : $a <=> $b;
        });

        return $ids;
    }
}
