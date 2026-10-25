<?php

namespace App\Http\Controllers;

use App\Services\SawService;
use App\Services\SensitivityService;
use App\Services\WeightService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SawController extends Controller
{
    public const METHOD_LABELS = [
        'roc' => 'ROC Kolektif (utama)',
        'roc_individual' => 'ROC Individu',
        'likert' => 'Likert',
        'equal' => 'Sama rata',
    ];

    public function __construct(
        protected SawService $saw,
        protected SensitivityService $sensitivity,
        protected WeightService $weights,
    ) {
    }

    public function index(Request $request): View
    {
        $method = $this->weights->normalizeMethod((string) $request->query('method', 'roc'));
        $result = $this->saw->calculate($method);

        return view('saw.index', [
            'result' => $result,
            'method' => $method,
            'methodLabels' => self::METHOD_LABELS,
            'comparison' => $this->compareMethods($method, $result),
            'perturbation' => $this->sensitivity->perturbWeights($result),
        ]);
    }

    /**
     * Bandingkan hasil SAW pada semua cara pembobotan (bagian 6 halaman).
     * Korelasi Spearman dihitung terhadap bobot utama ('roc' = ROC Kolektif).
     *
     * @return array{
     *   methods: array<string,array{totals:array<int,float>, ranks:array<int,int>}>,
     *   top_same: bool,
     *   correlations: array<string,?float>
     * }
     */
    protected function compareMethods(string $currentMethod, array $currentResult): array
    {
        $methods = [];
        $topIds = [];

        foreach (array_keys(self::METHOD_LABELS) as $key) {
            $result = $key === $currentMethod ? $currentResult : $this->saw->calculate($key);

            $totals = [];
            $ranks = [];
            foreach ($result['ranking'] as $row) {
                $id = $row['laptop']->id;
                $totals[$id] = $row['score'];
                $ranks[$id] = $row['rank'];
            }

            // urutan kunci sama untuk semua metode (syarat rankCorrelation)
            ksort($totals);
            ksort($ranks);

            $methods[$key] = ['totals' => $totals, 'ranks' => $ranks];
            $topIds[$key] = array_keys(array_filter($ranks, fn ($rank) => $rank === 1));
        }

        $correlations = [];
        foreach ($methods as $key => $data) {
            if ($key !== 'roc') {
                $correlations[$key] = $this->weights->rankCorrelation($methods['roc']['totals'], $data['totals']);
            }
        }

        return [
            'methods' => $methods,
            'top_same' => count(array_unique(array_map('json_encode', $topIds))) === 1,
            'correlations' => $correlations,
        ];
    }
}
