<?php

namespace Tests\Feature;

use App\Models\Criterion;
use App\Models\Laptop;
use App\Models\SurveyRespondent;
use App\Models\User;
use Database\Seeders\CriterionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Tes "asap": membuka tiap halaman admin supaya salah ketik di Blade/controller langsung ketahuan.
 */
class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CriterionSeeder::class);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_all_admin_pages_open_without_data(): void
    {
        $this->actingAs(User::factory()->create());

        $urls = [
            route('dashboard'),
            route('criteria.index'),
            route('criteria.create'),
            route('laptops.index'),
            route('laptops.create'),
            route('survey.index'),
            route('weights.index'),
            route('saw.index'),
        ];

        foreach (Criterion::all() as $criterion) {
            $urls[] = route('criteria.edit', $criterion); // termasuk kriteria raw (Harga) dan kategorikal (Tipe)
        }

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_pages_open_and_show_results_when_data_exists(): void
    {
        $this->actingAs(User::factory()->create());
        $this->addData();

        $this->get(route('laptops.index'))
            ->assertOk()
            ->assertSee('Siap dihitung')
            ->assertSee('Belum bisa dihitung');

        $this->get(route('laptops.edit', Laptop::where('name', 'Laptop Alfa')->first()))->assertOk();
        $this->get(route('survey.index'))->assertOk()->assertSee('Valid');
        $this->get(route('weights.index'))->assertOk();
        $this->get(route('dashboard'))->assertOk();

        foreach (['roc', 'likert', 'equal'] as $method) {
            $this->get(route('saw.index', ['method' => $method]))
                ->assertOk()
                ->assertSee('Peringkat akhir')
                ->assertSee('Laptop Beta');
        }
    }

    public function test_criteria_count_is_locked_once_survey_data_exists(): void
    {
        $this->actingAs(User::factory()->create());
        $this->addData();

        $this->get(route('criteria.create'))->assertRedirect(route('criteria.index'));

        $this->post(route('criteria.store'), [
            'code' => 'C9', 'name' => 'Baru', 'type' => 'benefit', 'spec_field' => 'ram_gb',
            'scoring_mode' => 'rubric', 'sort_order' => 9,
        ])->assertRedirect(route('criteria.index'));
        $this->assertDatabaseMissing('criteria', ['code' => 'C9']);

        $criterion = Criterion::where('code', 'C3')->first();
        $this->delete(route('criteria.destroy', $criterion))->assertRedirect(route('criteria.index'));
        $this->assertDatabaseHas('criteria', ['code' => 'C3']);
    }

    public function test_rubric_level_rules_for_numeric_and_categorical_criteria(): void
    {
        $this->actingAs(User::factory()->create());

        $numeric = Criterion::where('code', 'C3')->first();       // RAM
        $categorical = Criterion::where('code', 'C5')->first();   // Tipe penyimpanan

        // Numerik tanpa Min dan Max ditolak.
        $this->post(route('criteria.levels.store', $numeric), ['label' => 'Tanpa batas', 'score' => 3])
            ->assertSessionHasErrors('min_value');

        // Max lebih kecil dari Min ditolak.
        $this->post(route('criteria.levels.store', $numeric), ['label' => 'Terbalik', 'min_value' => 10, 'max_value' => 5, 'score' => 3])
            ->assertSessionHasErrors('max_value');

        // Kategorikal dengan Min ditolak.
        $this->post(route('criteria.levels.store', $categorical), ['label' => 'SSD X', 'min_value' => 1, 'score' => 3])
            ->assertSessionHasErrors('min_value');

        // Level yang benar diterima.
        $this->post(route('criteria.levels.store', $numeric), ['label' => '32 GB', 'min_value' => 25, 'score' => 5])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('criterion_levels', ['criterion_id' => $numeric->id, 'label' => '32 GB']);
    }

    public function test_laptop_form_validates_and_saves(): void
    {
        $this->actingAs(User::factory()->create());

        $valid = [
            'name' => 'Laptop Uji', 'brand' => 'Uji', 'price' => 7000000,
            'processor_name' => 'CPU uji', 'processor_score' => 12000, 'ram_gb' => 8,
            'storage_type' => 'SSD NVMe', 'storage_gb' => 512,
            'spec_source' => 'toko uji', 'spec_accessed_on' => now()->toDateString(),
        ];

        // Tipe penyimpanan yang tidak ada di rubrik ditolak.
        $this->post(route('laptops.store'), array_merge($valid, ['storage_type' => 'SSD NVME']))
            ->assertSessionHasErrors('storage_type');

        $this->post(route('laptops.store'), $valid)->assertRedirect(route('laptops.index'));
        $this->assertDatabaseHas('laptops', ['name' => 'Laptop Uji', 'price' => 7000000]);

        // Nama laptop harus unik.
        $this->post(route('laptops.store'), $valid)->assertSessionHasErrors('name');
    }

    public function test_survey_csv_can_be_uploaded_through_the_page(): void
    {
        $this->actingAs(User::factory()->create());

        $file = UploadedFile::fake()->createWithContent('survei.csv', $this->csvContent());

        $this->post(route('survey.import'), ['file' => $file])
            ->assertRedirect(route('survey.index'))
            ->assertSessionHas('import_report', fn ($report) => $report['imported'] === 1 && $report['failed'] === []);

        $this->assertSame(1, SurveyRespondent::count());
    }

    // ---------------------------------------------------------------- helper

    /** Tiga laptop lengkap, satu laptop tanpa skor processor, dan empat responden valid. */
    private function addData(): void
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

    private function csvContent(): string
    {
        $question = 'Dari kelima kriteria berikut, urutkan berdasarkan tingkat kepentingannya. (Setiap prioritas hanya boleh dipakai satu kali)';

        $headers = [
            'Timestamp',
            'Nama/Inisial',
            'Seberapa penting harga dalam memilih laptop?',
            'Seberapa penting spesifikasi processor dalam memilih laptop?',
            'Seberapa penting kapasitas RAM dalam memilih laptop?',
            'Seberapa penting kapasitas penyimpanan dalam memilih laptop?',
            'Seberapa penting tipe penyimpanan (SSD atau HDD) dalam memilih laptop?',
            "{$question} [Harga Laptop]",
            "{$question} [Processor]",
            "{$question} [RAM]",
            "{$question} [Kapasitas Penyimpanan]",
            "{$question} [Tipe Penyimpanan]",
        ];

        $row = ['9/11/2026 21:35:08', 'Budi', 5, 5, 4, 3, 3, 'Prioritas 1', 'Prioritas 2', 'Prioritas 3', 'Prioritas 4', 'Prioritas 5'];

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers, ',', '"', '');
        fputcsv($handle, $row, ',', '"', '');
        rewind($handle);

        return (string) stream_get_contents($handle);
    }
}
