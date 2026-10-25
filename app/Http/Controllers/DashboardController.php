<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Models\Laptop;
use App\Models\SurveyRespondent;
use App\Services\WeightService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected WeightService $weights)
    {
    }

    public function index(): View
    {
        $survey = $this->weights->fromSurvey();

        return view('dashboard.index', [
            'criteriaCount' => Criterion::count(),
            'criteriaWithoutRubric' => Criterion::where('scoring_mode', 'rubric')->doesntHave('levels')->count(),
            'laptopCount' => Laptop::count(),
            'respondentCount' => SurveyRespondent::count(),
            'validRespondents' => $survey['respondents_used'],
        ]);
    }
}
