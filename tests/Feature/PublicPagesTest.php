<?php

namespace Tests\Feature;

use App\Models\Criterion;
use App\Models\Laptop;
use App\Models\SurveyRespondent;
use Database\Seeders\CriterionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CriterionSeeder::class);
    }

    public function test_public_pages_open_without_login_and_without_data(): void
    {
        $this->get('/')->assertOk()->assertSee('Hasil rekomendasi belum tersedia');
        $this->get('/rekomendasi')->assertOk()->assertSee('Hasil belum tersedia');
        $this->get('/katalog')->assertOk()->assertSee('Belum ada laptop di katalog');
    }

    public function test_results_stay_hidden_while_there_is_no_valid_survey_data(): void
    {
        // Laptop sudah ada, tapi belum ada responden valid: bobot sama rata tidak boleh dipublikasikan sebagai hasil.
        $this->addLaptops();

        $this->get('/rekomendasi')
            ->assertOk()
            ->assertSee('Hasil belum tersedia')
            ->assertDontSee('Berdasarkan prioritas')
            ->assertDontSee('Seberapa Kokoh Hasil Ini?');
        $this->get('/katalog')->assertOk()->assertSee('Laptop Alfa');
    }

    public function test_results_are_shown_once_survey_data_exists(): void
    {
        $this->addLaptops();
        $this->addRespondents();

        $this->get('/')
            ->assertOk()
            ->assertSee('Rekomendasi Teratas')
            ->assertSee('Laptop Beta');   // peringkat 1 pada data contoh

        $this->get('/rekomendasi')
            ->assertOk()
            ->assertSee('Laptop Beta')
            ->assertSee('Laptop Alfa')
            ->assertSee('Laptop Gamma')
            ->assertSee('Seberapa Kokoh Hasil Ini?')
            ->assertSee('Rp 8.200.000')
            ->assertDontSee('Laptop Tanpa Skor'); // data belum lengkap, tidak ikut peringkat

        $this->get('/katalog')
            ->assertOk()
            ->assertSee('Laptop Tanpa Skor');     // katalog menampilkan semua laptop
    }

    public function test_public_pages_link_to_admin_login_and_use_configured_brand(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee(route('login'), false)
            ->assertSee(config('brand.name'));
    }

    // ---------------------------------------------------------------- helper

    private function addLaptops(): void
    {
        $laptops = [
            ['Laptop Alfa', 6500000, 12000, 8, 512, 'SSD NVMe'],
            ['Laptop Beta', 8200000, 21000, 16, 512, 'SSD SATA'],
            ['Laptop Gamma', 5400000, 7000, 8, 256, 'HDD'],
            ['Laptop Tanpa Skor', 7000000, null, 8, 512, 'SSD NVMe'],
        ];

        foreach ($laptops as [$name, $price, $cpu, $ram, $gb, $type]) {
            Laptop::create([
                'name' => $name, 'price' => $price, 'processor_name' => 'CPU uji', 'processor_score' => $cpu,
                'ram_gb' => $ram, 'storage_type' => $type, 'storage_gb' => $gb,
            ]);
        }
    }

    private function addRespondents(): void
    {
        $respondents = [
            ['R1', [1, 2, 3, 4, 5], [5, 5, 5, 4, 3]],
            ['R2', [2, 1, 3, 5, 4], [4, 5, 4, 3, 3]],
            ['R3', [1, 3, 2, 4, 5], [5, 4, 5, 4, 2]],
            ['R4', [3, 1, 2, 5, 4], [4, 5, 4, 3, 3]],
        ];

        foreach ($respondents as [$name, $ranks, $likerts]) {
            $respondent = SurveyRespondent::create(['name' => $name, 'source' => 'manual']);

            foreach (Criterion::orderBy('sort_order')->get()->values() as $i => $criterion) {
                $respondent->responses()->create([
                    'criterion_id' => $criterion->id,
                    'likert' => $likerts[$i],
                    'priority_rank' => $ranks[$i],
                ]);
            }
        }
    }
}
