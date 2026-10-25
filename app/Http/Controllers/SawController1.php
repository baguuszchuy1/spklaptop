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
        'roc' => 'ROC (utama)',
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
            'comparison' => $this->sensitivity->compareMethods(),
            'perturbation' => $this->sensitivity->perturbWeights($result),
        ]);
    }
}
