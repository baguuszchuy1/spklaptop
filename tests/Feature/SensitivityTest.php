<?php

namespace Tests\Feature;

use App\Models\Criterion;
use App\Models\Laptop;
use App\Models\SurveyRespondent;
use App\Services\SensitivityService;
use App\Services\SawService;
use Database\Seeders\CriterionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SensitivityTest extends TestCase
{
    use RefreshDatabase;

    private const DELTA = 0.00001;

    /**
     * Data sintetis yang sengaja membuat peringkat 1 berubah:
     * P unggul di kriteria 1, Q unggul di kriteria 2, dan bobotnya hampir imbang.
     */
    public function test_perturbation_detects_when_the_top_laptop_changes(): void
    {
        $c1 = new Criterion();
        $c1->id = 1;
        $c2 = new Criterion();
        $c2->id = 2;

        $result = [
            'criteria' => collect([$c1, $c2]),
            'weights' => [1 => 0.52, 2 => 0.48],
            'normalized' => [10 => [1 => 1.0, 2 => 0.5], 20 => [1 => 0.5, 2 => 1.0]],
            'totals' => [10 => 0.76, 20 => 0.74], // P (10) menang
        ];

        $out = app(SensitivityService::class)->perturbWeights($result, 0.2);
        $scenarios = collect($out['scenarios']);

        $this->assertCount(4, $scenarios);

        // Bobot kriteria 1 turun 20% -> w = (0,464286; 0,535714) -> Q (20) naik jadi peringkat 1.
        $down = $scenarios->first(fn ($s) => $s['criterion']->id === 1 && $s['change'] < 0);
        $this->assertFalse($down['top_same']);
        $this->assertFalse($down['same_order']);
        $this->assertSame([20], $down['top_ids']);
        $this->assertEqualsWithDelta(0.732143, $down['totals'][10], self::DELTA);
        $this->assertEqualsWithDelta(0.767857, $down['totals'][20], self::DELTA);
        $this->assertEqualsWithDelta(-1.0, $down['correlation'], 0.000000001);

        // Bobot kriteria 1 naik 20% -> P tetap peringkat 1.
        $up = $scenarios->first(fn ($s) => $s['criterion']->id === 1 && $s['change'] > 0);
        $this->assertTrue($up['top_same']);
        $this->assertTrue($up['same_order']);
        $this->assertEqualsWithDelta(1.0, $up['correlation'], 0.000000001);

        $this->assertFalse($out['stable_top']);
        $this->assertFalse($out['stable_order']);
        $this->assertEqualsWithDelta(-1.0, $out['min_correlation'], 0.000000001);

        foreach ($scenarios as $scenario) {
            $this->assertEqualsWithDelta(1.0, array_sum($scenario['weights']), 0.000000001);
        }
    }

    public function test_reference_dataset_is_stable_and_matches_python_reference(): void
    {
        $this->seed(CriterionSeeder::class);
        $this->addFourRespondents();
        $this->addLaptop('A', 6500000, 12000, 8, 512, 'SSD NVMe');
        $this->addLaptop('B', 8200000, 21000, 16, 512, 'SSD SATA');
        $this->addLaptop('C', 5400000, 7000, 8, 256, 'HDD');

        $service = app(SensitivityService::class);

        // Perbandingan metode bobot: urutan B, A, C di ketiganya.
        $comparison = $service->compareMethods();
        $this->assertTrue($comparison['top_same']);
        $this->assertEqualsWithDelta(1.0, $comparison['correlations']['likert'], 0.000001);
        $this->assertEqualsWithDelta(1.0, $comparison['correlations']['equal'], 0.000001);

        // Perubahan bobot +-20%: 5 kriteria x 2 arah = 10 skenario, urutan tidak pernah berubah.
        $result = app(SawService::class)->calculate('roc');
        $out = $service->perturbWeights($result, 0.2);

        $this->assertCount(10, $out['scenarios']);
        $this->assertTrue($out['stable_top']);
        $this->assertTrue($out['stable_order']);

        // Skenario Harga (C1) naik 20%, nilai dari sensitivity_ref.py.
        $c1 = Criterion::where('code', 'C1')->first();
        $scenario = collect($out['scenarios'])->first(fn ($s) => $s['criterion']->id === $c1->id && $s['change'] > 0);

        $byName = Laptop::pluck('id', 'name');
        $this->assertEqualsWithDelta(0.734898, $scenario['totals'][$byName['A']], self::DELTA);
        $this->assertEqualsWithDelta(0.848169, $scenario['totals'][$byName['B']], self::DELTA);
        $this->assertEqualsWithDelta(0.656611, $scenario['totals'][$byName['C']], self::DELTA);
    }

    // ---------------------------------------------------------------- helper

    private function addFourRespondents(): void
    {
        $this->addRespondent('R1', [1, 2, 3, 4, 5], [5, 5, 5, 4, 3]);
        $this->addRespondent('R2', [2, 1, 3, 5, 4], [4, 5, 4, 3, 3]);
        $this->addRespondent('R3', [1, 3, 2, 4, 5], [5, 4, 5, 4, 2]);
        $this->addRespondent('R4', [3, 1, 2, 5, 4], [4, 5, 4, 3, 3]);
    }

    private function addRespondent(string $name, array $ranks, array $likerts): void
    {
        $respondent = SurveyRespondent::create(['name' => $name, 'source' => 'manual']);

        foreach (Criterion::orderBy('sort_order')->get()->values() as $i => $criterion) {
            $respondent->responses()->create([
                'criterion_id' => $criterion->id,
                'likert' => $likerts[$i],
                'priority_rank' => $ranks[$i],
            ]);
        }
    }

    private function addLaptop(string $name, int $price, int $cpuScore, int $ramGb, int $storageGb, string $storageType): void
    {
        Laptop::create([
            'name' => $name,
            'price' => $price,
            'processor_name' => 'CPU uji',
            'processor_score' => $cpuScore,
            'ram_gb' => $ramGb,
            'storage_type' => $storageType,
            'storage_gb' => $storageGb,
        ]);
    }
}
