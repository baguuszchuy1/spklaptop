<?php

namespace Tests\Feature;

use App\Models\Criterion;
use App\Models\SurveyRespondent;
use App\Services\SurveyImportService;
use App\Services\WeightService;
use Database\Seeders\CriterionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyImportTest extends TestCase
{
    use RefreshDatabase;

    private const RANK_TEXT = [
        1 => 'Prioritas 1 (Paling Penting)',
        2 => 'Prioritas 2',
        3 => 'Prioritas 3',
        4 => 'Prioritas 4',
        5 => 'Prioritas 5 (Paling Kurang Penting)',
    ];

    /** @var array<int,string> file sementara yang dibuat tes ini */
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CriterionSeeder::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_valid_rows_are_imported_and_invalid_row_is_reported(): void
    {
        $result = app(SurveyImportService::class)->importFile($this->makeCsv());

        $this->assertSame(2, $result['imported']);
        $this->assertSame(0, $result['skipped']);
        $this->assertCount(1, $result['failed']);
        $this->assertStringContainsString('Baris 4', $result['failed'][0]);
        $this->assertSame(2, SurveyRespondent::count());

        $budi = SurveyRespondent::where('name', 'Budi')->first();
        $processor = Criterion::where('code', 'C2')->first();
        $answer = $budi->responses->firstWhere('criterion_id', $processor->id);

        $this->assertSame(5, $answer->likert);          // Likert Processor Budi = 5
        $this->assertSame(2, $answer->priority_rank);   // Prioritas 2
        $this->assertSame('csv', $budi->source);
        $this->assertSame(2024, $budi->cohort_year);
        $this->assertSame('Laki-laki', $budi->gender);
        $this->assertSame('2026-09-11 21:35:08', $budi->submitted_at->format('Y-m-d H:i:s'));
        $this->assertCount(5, $budi->responses);
    }

    public function test_importing_the_same_file_twice_does_not_create_duplicates(): void
    {
        $path = $this->makeCsv();

        app(SurveyImportService::class)->importFile($path);
        $second = app(SurveyImportService::class)->importFile($path);

        $this->assertSame(0, $second['imported']);
        $this->assertSame(2, $second['skipped']);
        $this->assertSame(2, SurveyRespondent::count());
    }

    public function test_columns_are_matched_by_header_not_by_position(): void
    {
        $result = app(SurveyImportService::class)->importFile($this->makeCsv(reverse: true));

        $this->assertSame(2, $result['imported']);

        $ani = SurveyRespondent::where('name', 'Ani')->first();
        $ram = Criterion::where('code', 'C3')->first();
        $answer = $ani->responses->firstWhere('criterion_id', $ram->id);

        $this->assertSame(5, $answer->likert);          // Likert RAM Ani = 5
        $this->assertSame(3, $answer->priority_rank);   // Prioritas 3
    }

    public function test_semicolon_delimiter_and_bom_are_supported(): void
    {
        $result = app(SurveyImportService::class)->importFile($this->makeCsv(delimiter: ';', bom: true));

        $this->assertSame(2, $result['imported']);
        $this->assertSame(2, SurveyRespondent::count());
    }

    public function test_missing_criterion_column_stops_the_import_with_a_clear_message(): void
    {
        $path = $this->makeCsv(replaceHeader: [
            'Seberapa penting kapasitas RAM dalam memilih laptop untuk perkuliahan Pendidikan Komputer?' => 'Pertanyaan lain',
        ]);

        try {
            app(SurveyImportService::class)->importFile($path);
            $this->fail('Seharusnya melempar RuntimeException.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('RAM', $e->getMessage());
            $this->assertStringContainsString('Likert', $e->getMessage());
        }

        $this->assertSame(0, SurveyRespondent::count());
    }

    public function test_rank_correlation_matches_reference(): void
    {
        $roc = [1 => 0.331667, 2 => 0.331667, 3 => 0.206667, 4 => 0.065, 5 => 0.065];
        $likert = [1 => 0.225, 2 => 0.2375, 3 => 0.225, 4 => 0.175, 5 => 0.1375];

        $this->assertEqualsWithDelta(0.892218, app(WeightService::class)->rankCorrelation($roc, $likert), 0.00001);
        $this->assertEqualsWithDelta(1.0, app(WeightService::class)->rankCorrelation($roc, $roc), 0.000001);
    }

    // ---------------------------------------------------------------- helper

    /**
     * Buat CSV seperti ekspor Google Sheets. Urutan kriteria di kolom: Harga, Processor, RAM,
     * Kapasitas Penyimpanan, Tipe Penyimpanan.
     * Baris 2 Budi (valid), baris 3 Ani (valid), baris 4 Citra (urutan prioritas ganda, tidak valid).
     */
    private function makeCsv(bool $reverse = false, string $delimiter = ',', bool $bom = false, array $replaceHeader = []): string
    {
        $rankQuestion = 'Dari kelima kriteria berikut, urutkan berdasarkan tingkat kepentingannya dalam memilih laptop terbaik '
            . 'untuk kebutuhan mahasiswa Pendidikan Komputer. (Setiap prioritas hanya boleh dipakai satu kali)';

        $likertHeaders = [
            'Seberapa penting harga dalam memilih laptop untuk perkuliahan Pendidikan Komputer?',
            'Seberapa penting spesifikasi processor dalam memilih laptop untuk perkuliahan Pendidikan Komputer?',
            'Seberapa penting kapasitas RAM dalam memilih laptop untuk perkuliahan Pendidikan Komputer?',
            'Seberapa penting kapasitas penyimpanan dalam memilih laptop untuk perkuliahan Pendidikan Komputer?',
            'Seberapa penting tipe penyimpanan (SSD atau HDD) dalam memilih laptop untuk perkuliahan Pendidikan Komputer?',
        ];

        $rankHeaders = array_map(
            fn ($name) => "{$rankQuestion} [{$name}]",
            ['Harga Laptop', 'Processor', 'RAM', 'Kapasitas Penyimpanan', 'Tipe Penyimpanan']
        );

        $headers = array_merge(
            ['Timestamp', 'Nama/Inisial', 'Jenis Kelamin', 'Angkatan', "Merk Beserta Seri/Model laptop yang Anda gunakan\nContoh:\nASUS VivoBook 14"],
            $likertHeaders,
            $rankHeaders
        );

        $headers = array_map(fn ($h) => $replaceHeader[$h] ?? $h, $headers);

        $rows = [
            ['9/11/2026 21:35:08', 'Budi', 'Laki-laki', '2024', 'ASUS VivoBook 14', 5, 5, 4, 3, 3, ...$this->ranks([1, 2, 3, 4, 5])],
            ['9/11/2026 21:40:19', 'Ani', 'Perempuan', '2024', 'Acer Aspire 5', 4, 5, 5, 4, 2, ...$this->ranks([2, 1, 3, 5, 4])],
            ['9/11/2026 21:41:37', 'Citra', 'Perempuan', '2025', 'HP 14s', 5, 4, 4, 4, 3, ...$this->ranks([1, 2, 2, 4, 5])],
        ];

        if ($reverse) {
            // Balik urutan 10 kolom terakhir (Likert + urutan) untuk membuktikan pencocokan berdasarkan judul.
            $flip = fn (array $row) => array_merge(array_slice($row, 0, 5), array_reverse(array_slice($row, 5)));
            $headers = $flip($headers);
            $rows = array_map($flip, $rows);
        }

        $path = tempnam(sys_get_temp_dir(), 'survei');
        $this->files[] = $path;

        $handle = fopen($path, 'wb');

        if ($bom) {
            fwrite($handle, "\xEF\xBB\xBF");
        }

        fputcsv($handle, $headers, $delimiter, '"', '');
        foreach ($rows as $row) {
            fputcsv($handle, $row, $delimiter, '"', '');
        }

        fclose($handle);

        return $path;
    }

    /** @param array<int,int> $ranks nomor prioritas per kriteria */
    private function ranks(array $ranks): array
    {
        return array_map(fn ($rank) => self::RANK_TEXT[$rank], $ranks);
    }
}
