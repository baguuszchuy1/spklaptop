<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Models\SurveyRespondent;
use App\Services\SurveyImportService;
use App\Services\WeightService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SurveyController extends Controller
{
    public function __construct(
        protected SurveyImportService $importer,
        protected WeightService $weights,
    ) {
    }

    public function index(): View
    {
        $ids = Criterion::orderBy('sort_order')->pluck('id')->all();
        $count = count($ids);

        $respondents = SurveyRespondent::with('responses')->orderByDesc('id')->paginate(25);

        $validity = $respondents->getCollection()->mapWithKeys(
            fn ($respondent) => [
                $respondent->id => $this->weights->hasValidAnswers($respondent->responses->keyBy('criterion_id'), $ids, $count),
            ]
        );

        return view('survey.index', [
            'respondents' => $respondents,
            'validity' => $validity,
            'summary' => $this->weights->fromSurvey(),
            'total' => SurveyRespondent::count(),
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120', 'extensions:csv,txt'],
        ], [
            'file.required' => 'Pilih file CSV terlebih dahulu.',
            'file.extensions' => 'File harus berformat CSV (.csv).',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        try {
            $result = $this->importer->importFile($request->file('file')->getRealPath());
        } catch (\RuntimeException $e) {
            return redirect()->route('survey.index')->with('error', $e->getMessage());
        }

        return redirect()->route('survey.index')->with('import_report', $result);
    }

    public function destroy(SurveyRespondent $respondent): RedirectResponse
    {
        $respondent->delete();

        return redirect()->route('survey.index')->with('success', 'Responden dihapus.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = array_filter((array) $request->input('ids', []));

        if ($ids === []) {
            return redirect()->route('survey.index')->with('error', 'Tidak ada responden yang dipilih.');
        }

        $count = SurveyRespondent::whereIn('id', $ids)->count();
        SurveyRespondent::whereIn('id', $ids)->delete();

        return redirect()->route('survey.index')->with('success', "{$count} responden dihapus.");
    }

    public function destroyAll(): RedirectResponse
    {
        $count = SurveyRespondent::count();
        SurveyRespondent::query()->delete();

        return redirect()->route('survey.index')->with('success', "Seluruh data survei ({$count} responden) dihapus.");
    }
}
