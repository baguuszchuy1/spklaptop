<?php

namespace App\Http\Controllers;

use App\Models\Laptop;
use App\Services\SawService;
use App\Services\SensitivityService;
use Carbon\Carbon;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function __construct(
        protected SawService $saw,
        protected SensitivityService $sensitivity,
    ) {
    }

    public function home(): View
    {
        $result = $this->saw->calculate('roc');

        return view('public.home', [
            'ready' => $this->isReady($result),
            'top' => array_slice($result['ranking'], 0, 3),
            'weightBars' => $this->weightBars($result),
            'survey' => $result['survey'],
            'laptopCount' => count($result['ranking']),
            'criteriaCount' => $result['criteria']->count(),
        ]);
    }

    public function recommendation(): View
    {
        $result = $this->saw->calculate('roc');
        $ready = $this->isReady($result);

        return view('public.recommendation', [
            'ready' => $ready,
            'ranking' => $result['ranking'],
            'weightBars' => $this->weightBars($result),
            'survey' => $result['survey'],
            'perturbation' => $ready ? $this->sensitivity->perturbWeights($result) : null,
            'sourceDates' => $this->sourceDates(),
        ]);
    }

    public function catalog(): View
    {
        return view('public.catalog', [
            'laptops' => Laptop::orderBy('name')->get(),
            'sourceDates' => $this->sourceDates(),
        ]);
    }

    /** Hasil baru ditampilkan ke publik kalau bobot berasal dari data survei nyata dan ada laptop yang terhitung. */
    protected function isReady(array $result): bool
    {
        return ! $result['weight_fallback'] && $result['ranking'] !== [];
    }

    /**
     * Bobot per kriteria (persen), dari yang terbesar.
     *
     * @return array<int,array{code:string,name:string,type:string,percent:float}>
     */
    protected function weightBars(array $result): array
    {
        return $result['criteria']
            ->map(fn ($criterion) => [
                'code' => $criterion->code,
                'name' => $criterion->name,
                'type' => $criterion->type,
                'percent' => round($result['weights'][$criterion->id] * 100, 2),
            ])
            ->sortByDesc('percent')
            ->values()
            ->all();
    }

    /** Rentang tanggal akses data spesifikasi dan harga laptop (untuk catatan transparansi). */
    protected function sourceDates(): ?array
    {
        $first = Laptop::whereNotNull('spec_accessed_on')->min('spec_accessed_on');
        $last = Laptop::whereNotNull('spec_accessed_on')->max('spec_accessed_on');

        if ($first === null || $last === null) {
            return null;
        }

        return ['first' => Carbon::parse($first), 'last' => Carbon::parse($last)];
    }
}
