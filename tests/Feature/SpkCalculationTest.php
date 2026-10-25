<?php

namespace Tests\Feature;

use App\Models\Criterion;
use App\Models\Laptop;
use App\Models\SurveyRespondent;
use App\Services\SawService;
use App\Services\WeightService;
use Database\Seeders\CriterionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Angka pembanding dihitung terpisah dengan skrip Python (reference.py) dan perhitungan manual,
 * sehingga tes ini sekaligus menjadi bukti "hitung manual vs sistem" untuk naskah.
 */
class SpkCalculationTest extends TestCase
{
    use RefreshDatabase;

    private const DELTA = 0.00001;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CriterionSeeder::class); // C1 Harga (cost, raw), C2-C5 (benefit, rubrik)
    }

    public function test_roc_weights_for_five_criteria(): void
    {
        $weights = app(WeightService::class)->rocWeights(5);

        $this->assertEqualsWithDelta(0.456667, $weights[1], self::DELTA);
        $this->assertEqualsWithDelta(0.256667, $weights[2], self::DELTA);
        $this->assertEqualsWithDelta(0.156667, $weights[3], self::DELTA);
        $this->assertEqualsWithDelta(0.09, $weights[4], self::DELTA);
        $this->assertEqualsWithDelta(0.04, $weights[5], self::DELTA);
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.000000001);
    }

    public function test_survey_weights_use_valid_respondents_only(): void
    {
        $this->addFourRespondents();
        $this->addRespondent('Tidak lengkap', [1, 2, 3, 4, 5], [5, 5, 5, 5, 5], 3); // hanya 3 kriteria terjawab

        $survey = app(WeightService::class)->fromSurvey();
        $ids = Criterion::orderBy('sort_order')->pluck('id')->all();

        $expectedRoc = [0.331667, 0.331667, 0.206667, 0.065, 0.065];
        $expectedLikert = [0.225, 0.2375, 0.225, 0.175, 0.1375];

        foreach ($ids as $i => $id) {
            $this->assertEqualsWithDelta($expectedRoc[$i], $survey['roc'][$id], self::DELTA);
            $this->assertEqualsWithDelta($expectedLikert[$i], $survey['likert'][$id], self::DELTA);
        }

        $this->assertEqualsWithDelta(1.0, array_sum($survey['roc']), 0.000000001);
        $this->assertEqualsWithDelta(1.0, array_sum($survey['likert']), 0.000000001);
        $this->assertSame(4, $survey['respondents_used']);
        $this->assertSame(1, $survey['respondents_skipped']);
        $this->assertFalse($survey['fallback']);
    }

    public function test_saw_scores_and_ranking_match_reference(): void
    {
        $this->addFourRespondents();
        $a = $this->addLaptop('A', 6500000, 12000, 8, 512, 'SSD NVMe');
        $b = $this->addLaptop('B', 8200000, 21000, 16, 512, 'SSD SATA');
        $this->addLaptop('C', 5400000, 7000, 8, 256, 'HDD');

        $result = app(SawService::class)->calculate('roc');

        $scores = collect($result['ranking'])->mapWithKeys(fn ($row) => [$row['laptop']->name => $row['score']]);
        $this->assertEqualsWithDelta(0.728538, $scores['A'], self::DELTA);
        $this->assertEqualsWithDelta(0.860748, $scores['B'], self::DELTA);
        $this->assertEqualsWithDelta(0.633833, $scores['C'], self::DELTA);

        $this->assertSame(['B', 'A', 'C'], collect($result['ranking'])->map(fn ($row) => $row['laptop']->name)->all());
        $this->assertSame([1, 2, 3], collect($result['ranking'])->pluck('rank')->all());

        // Harga adalah cost: min(harga) / harga. Kriteria benefit: x / max.
        $c1 = Criterion::where('code', 'C1')->first();
        $c5 = Criterion::where('code', 'C5')->first();
        $this->assertEqualsWithDelta(0.830769, $result['normalized'][$a->id][$c1->id], self::DELTA);
        $this->assertEqualsWithDelta(0.658537, $result['normalized'][$b->id][$c1->id], self::DELTA);
        $this->assertEqualsWithDelta(0.6, $result['normalized'][$b->id][$c5->id], self::DELTA);
    }

    public function test_other_weight_methods_give_reference_scores(): void
    {
        $this->addFourRespondents();
        $this->addLaptop('A', 6500000, 12000, 8, 512, 'SSD NVMe');
        $this->addLaptop('B', 8200000, 21000, 16, 512, 'SSD SATA');
        $this->addLaptop('C', 5400000, 7000, 8, 256, 'HDD');

        $likert = collect(app(SawService::class)->calculate('likert')['ranking'])
            ->mapWithKeys(fn ($row) => [$row['laptop']->name => $row['score']]);
        $this->assertEqualsWithDelta(0.776923, $likert['A'], self::DELTA);
        $this->assertEqualsWithDelta(0.868171, $likert['B'], self::DELTA);
        $this->assertEqualsWithDelta(0.57, $likert['C'], self::DELTA);

        $equal = collect(app(SawService::class)->calculate('equal')['ranking'])
            ->mapWithKeys(fn ($row) => [$row['laptop']->name => $row['score']]);
        $this->assertEqualsWithDelta(0.806154, $equal['A'], self::DELTA);
        $this->assertEqualsWithDelta(0.851707, $equal['B'], self::DELTA);
        $this->assertEqualsWithDelta(0.54, $equal['C'], self::DELTA);
    }

    public function test_laptop_with_missing_spec_is_excluded_with_reason(): void
    {
        $this->addLaptop('A', 6500000, 12000, 8, 512, 'SSD NVMe');
        $this->addLaptop('Tanpa skor CPU', 7000000, null, 8, 512, 'SSD NVMe');

        $result = app(SawService::class)->calculate('equal');

        $this->assertCount(1, $result['ranking']);
        $this->assertCount(1, $result['excluded']);
        $this->assertSame('Tanpa skor CPU', $result['excluded'][0]['laptop']->name);
        $this->assertStringContainsString('Processor', $result['excluded'][0]['problems'][0]);
    }

    public function test_equal_weights_are_used_when_there_is_no_survey_data(): void
    {
        $survey = app(WeightService::class)->fromSurvey();

        $this->assertTrue($survey['fallback']);
        $this->assertSame(0, $survey['respondents_used']);

        foreach ($survey['roc'] as $weight) {
            $this->assertEqualsWithDelta(0.2, $weight, 0.000000001);
        }
    }

    public function test_identical_laptops_share_the_same_rank(): void
    {
        $this->addLaptop('A', 6500000, 12000, 8, 512, 'SSD NVMe');
        $this->addLaptop('A kembar', 6500000, 12000, 8, 512, 'SSD NVMe');

        $result = app(SawService::class)->calculate('equal');

        $this->assertSame([1, 1], collect($result['ranking'])->pluck('rank')->all());
    }

    // ---------------------------------------------------------------- helper

    /** Empat responden contoh. Urutan kolom = C1..C5. */
    private function addFourRespondents(): void
    {
        $this->addRespondent('R1', [1, 2, 3, 4, 5], [5, 5, 5, 4, 3]);
        $this->addRespondent('R2', [2, 1, 3, 5, 4], [4, 5, 4, 3, 3]);
        $this->addRespondent('R3', [1, 3, 2, 4, 5], [5, 4, 5, 4, 2]);
        $this->addRespondent('R4', [3, 1, 2, 5, 4], [4, 5, 4, 3, 3]);
    }

    private function addRespondent(string $name, array $ranks, array $likerts, int $limit = 5): SurveyRespondent
    {
        $respondent = SurveyRespondent::create(['name' => $name, 'source' => 'manual']);

        foreach (Criterion::orderBy('sort_order')->get()->take($limit)->values() as $i => $criterion) {
            $respondent->responses()->create([
                'criterion_id' => $criterion->id,
                'likert' => $likerts[$i],
                'priority_rank' => $ranks[$i],
            ]);
        }

        return $respondent;
    }

    private function addLaptop(string $name, int $price, ?int $cpuScore, int $ramGb, int $storageGb, string $storageType): Laptop
    {
        return Laptop::create([
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
