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

        $percent = fn (array $weights) => $criteria
            ->map(fn ($criterion) => round(($weights[$criterion->id] ?? 0) * 100, 2))
            ->values()
            ->all();

        return view('weights.index', [
            'criteria' => $criteria,
            'survey' => $survey,
            'correlation' => $survey['fallback'] ? null : $this->weights->rankCorrelation($survey['roc'], $survey['likert']),
            'chart' => [
                'labels' => $criteria->pluck('name')->values()->all(),
                'roc' => $percent($survey['roc']),
                'likert' => $percent($survey['likert']),
                'equal' => $percent($survey['equal']),
            ],
        ]);
    }
}
