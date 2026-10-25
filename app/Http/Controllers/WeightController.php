<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Services\WeightService;
use Illuminate\View\View;

class WeightController extends Controller
{
    public function __construct(protected WeightService $weights)
    {
    }

    public function index(): View
    {
        $criteria = Criterion::orderBy('sort_order')->get();
        $survey = $this->weights->fromSurvey($criteria);
        $ready = ! $survey['fallback'];

        $percent = fn (array $weights) => $criteria
            ->map(fn ($criterion) => round(($weights[$criterion->id] ?? 0) * 100, 2))
            ->values()
            ->all();

        return view('weights.index', [
            'criteria' => $criteria,
            'survey' => $survey,
            // ROC Kolektif (bobot utama) vs Likert
            'correlation' => $ready ? $this->weights->rankCorrelation($survey['roc'], $survey['likert']) : null,
            // ROC Individu vs Likert
            'correlationIndividual' => $ready ? $this->weights->rankCorrelation($survey['roc_individual'], $survey['likert']) : null,
            'chart' => [
                'labels' => $criteria->pluck('name')->values()->all(),
                'roc' => $percent($survey['roc']),
                'roc_individual' => $percent($survey['roc_individual']),
                'likert' => $percent($survey['likert']),
                'equal' => $percent($survey['equal']),
            ],
        ]);
    }
}
