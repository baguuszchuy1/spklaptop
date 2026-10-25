<?php

namespace App\Services;

use App\Models\Criterion;
use App\Models\SurveyRespondent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SurveyImportService
{
    /**
     * Impor file CSV hasil unduhan Google Sheets (respons Google Form).
     *
     * Kolom dikenali dari JUDULNYA, bukan urutannya:
     *  - "Timestamp", yang memuat "nama", "jenis kelamin", "angkatan", "merk/merek"
     *  - kolom Likert  : judul memuat "seberapa penting" + nama kriteria
     *  - kolom urutan  : judul memuat "urutkan" dan diakhiri [nama kriteria]
     *
     * Baris yang sudah pernah diimpor (Timestamp sama) dilewati, baris yang tidak valid
     * dilaporkan dan tidak disimpan. Jadi file yang sama aman diimpor berkali-kali.
     *
     * @return array{imported:int, skipped:int, failed:array<int,string>}
     *
     * @throws \RuntimeException jika file tidak bisa dibaca atau kolom wajib tidak ditemukan
     */
    public function importFile(string $path): array
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            throw new \RuntimeException('File tidak bisa dibaca.');
        }

        try {
            $delimiter = $this->detectDelimiter($handle);
            $headers = fgetcsv($handle, 0, $delimiter, '"', '');

            if ($headers === false || $headers === [null]) {
                throw new \RuntimeException('File kosong atau baris judul tidak terbaca.');
            }

            $headers[0] = $this->stripBom((string) $headers[0]);

            $criteria = Criterion::orderBy('sort_order')->get();
            $columns = $this->mapColumns($headers, $criteria);

            $imported = 0;
            $skipped = 0;
            $failed = [];
            $rowNumber = 1;

            while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $rowNumber++;

                if ($this->isBlankRow($row)) {
                    continue;
                }

                $reference = trim((string) ($row[$columns['timestamp']] ?? ''));

                if ($reference === '') {
                    $failed[] = "Baris {$rowNumber}: kolom Timestamp kosong.";
                    continue;
                }

                if (SurveyRespondent::where('external_ref', $reference)->exists()) {
                    $skipped++;
                    continue;
                }

                try {
                    $data = $this->parseRow($row, $columns, $criteria);
                    $this->store($data, $reference);
                    $imported++;
                } catch (\RuntimeException $e) {
                    $failed[] = "Baris {$rowNumber}: {$e->getMessage()}";
                }
            }

            return compact('imported', 'skipped', 'failed');
        } finally {
            fclose($handle);
        }
    }

    /** Tebak pemisah kolom (koma, titik koma, atau tab) dari 4 KB pertama. */
    protected function detectDelimiter($handle): string
    {
        $sample = (string) fread($handle, 4096);
        rewind($handle);

        $counts = [
            ',' => substr_count($sample, ','),
            ';' => substr_count($sample, ';'),
            "\t" => substr_count($sample, "\t"),
        ];

        arsort($counts);

        return array_key_first($counts) ?: ',';
    }

    protected function stripBom(string $text): string
    {
        return str_starts_with($text, "\xEF\xBB\xBF") ? substr($text, 3) : $text;
    }

    protected function isBlankRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function normalize(string $text): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $text)));
    }

    /** Apakah nama kriteria muncul sebagai kata utuh di teks (tidak menempel pada huruf/angka lain). */
    protected function mentions(string $text, string $criterionName): bool
    {
        $name = preg_quote($this->normalize($criterionName), '/');

        return (bool) preg_match('/(?<![\p{L}\p{N}])' . $name . '(?![\p{L}\p{N}])/u', $text);
    }

    /** Cari kriteria yang disebut di teks. Nama terpanjang dicoba dulu supaya yang lebih spesifik menang. */
    protected function matchCriterion(string $text, Collection $criteria): ?Criterion
    {
        foreach ($criteria->sortByDesc(fn ($c) => mb_strlen($c->name)) as $criterion) {
            if ($this->mentions($text, $criterion->name)) {
                return $criterion;
            }
        }

        return null;
    }

    /**
     * @return array{timestamp:int,name:int,gender:?int,cohort:?int,laptop:?int,likert:array<int,int>,rank:array<int,int>}
     *         likert/rank berkunci criterion_id, bernilai indeks kolom
     */
    protected function mapColumns(array $headers, Collection $criteria): array
    {
        $map = ['timestamp' => null, 'name' => null, 'gender' => null, 'cohort' => null, 'laptop' => null, 'likert' => [], 'rank' => []];
        $problems = [];

        foreach ($headers as $index => $raw) {
            $header = $this->normalize((string) $raw);

            if ($header === '') {
                continue;
            }

            if ($map['timestamp'] === null && in_array($header, ['timestamp', 'stempel waktu', 'cap waktu'], true)) {
                $map['timestamp'] = $index;
            } elseif (str_contains($header, 'urutkan')) {
                if (! preg_match('/\[([^\]]+)\]\s*$/u', $header, $match)) {
                    $problems[] = 'Kolom urutan prioritas harus diakhiri nama kriteria dalam tanda [ ] (format bawaan Google Form kisi pilihan ganda).';
                    continue;
                }

                $criterion = $this->matchCriterion($match[1], $criteria);

                if ($criterion === null) {
                    $problems[] = "Label \"{$match[1]}\" pada kolom urutan tidak cocok dengan nama kriteria mana pun.";
                } elseif (isset($map['rank'][$criterion->id])) {
                    $problems[] = "Kolom urutan untuk kriteria \"{$criterion->name}\" ditemukan lebih dari satu.";
                } else {
                    $map['rank'][$criterion->id] = $index;
                }
            } elseif (str_contains($header, 'seberapa penting')) {
                $criterion = $this->matchCriterion($header, $criteria);

                if ($criterion === null) {
                    $problems[] = 'Ada kolom "Seberapa penting ..." yang tidak menyebut nama kriteria mana pun.';
                } elseif (isset($map['likert'][$criterion->id])) {
                    $problems[] = "Kolom Likert untuk kriteria \"{$criterion->name}\" ditemukan lebih dari satu.";
                } else {
                    $map['likert'][$criterion->id] = $index;
                }
            } elseif ($map['gender'] === null && str_contains($header, 'jenis kelamin')) {
                $map['gender'] = $index;
            } elseif ($map['cohort'] === null && str_contains($header, 'angkatan')) {
                $map['cohort'] = $index;
            } elseif ($map['laptop'] === null && preg_match('/(?<![\p{L}\p{N}])(merk|merek)(?![\p{L}\p{N}])/u', $header)) {
                $map['laptop'] = $index;
            } elseif ($map['name'] === null && preg_match('/(?<![\p{L}\p{N}])nama(?![\p{L}\p{N}])/u', $header)) {
                $map['name'] = $index;
            }
        }

        if ($map['timestamp'] === null) {
            $problems[] = 'Kolom "Timestamp" tidak ditemukan.';
        }

        if ($map['name'] === null) {
            $problems[] = 'Kolom nama (judul memuat kata "Nama") tidak ditemukan.';
        }

        foreach ($criteria as $criterion) {
            if (! isset($map['likert'][$criterion->id])) {
                $problems[] = "Kolom Likert untuk kriteria \"{$criterion->name}\" tidak ditemukan.";
            }

            if (! isset($map['rank'][$criterion->id])) {
                $problems[] = "Kolom urutan prioritas untuk kriteria \"{$criterion->name}\" tidak ditemukan.";
            }
        }

        if ($problems !== []) {
            throw new \RuntimeException(
                "Judul kolom tidak sesuai. " . implode(' ', array_unique($problems))
                . ' Pastikan nama kriteria (menu Kriteria & Rubrik) muncul di judul kolom, dan file diekspor dari Google Sheets sebagai CSV.'
            );
        }

        return $map;
    }

    /**
     * @return array<string,mixed>
     *
     * @throws \RuntimeException jika isi baris tidak valid
     */
    protected function parseRow(array $row, array $columns, Collection $criteria): array
    {
        $name = trim((string) ($row[$columns['name']] ?? ''));

        if ($name === '') {
            throw new \RuntimeException('nama kosong.');
        }

        $answers = [];
        $ranks = [];

        foreach ($criteria as $criterion) {
            $likert = trim((string) ($row[$columns['likert'][$criterion->id]] ?? ''));

            if (! preg_match('/^[1-5]$/', $likert)) {
                throw new \RuntimeException("nilai Likert \"{$likert}\" untuk {$criterion->name} tidak valid (harus 1-5).");
            }

            $rankText = (string) ($row[$columns['rank'][$criterion->id]] ?? '');

            if (! preg_match('/prioritas\s*(\d+)/iu', $rankText, $match)) {
                throw new \RuntimeException("urutan prioritas untuk {$criterion->name} tidak terbaca (\"{$rankText}\").");
            }

            $rank = (int) $match[1];
            $ranks[] = $rank;

            $answers[$criterion->id] = ['likert' => (int) $likert, 'priority_rank' => $rank];
        }

        sort($ranks);

        if ($ranks !== range(1, $criteria->count())) {
            throw new \RuntimeException('urutan prioritas tidak valid (setiap prioritas 1 sampai ' . $criteria->count() . ' harus dipakai tepat sekali).');
        }

        $cohort = $this->cell($row, $columns['cohort']);

        return [
            'name' => $name,
            'gender' => $this->cell($row, $columns['gender']),
            'cohort_year' => ($cohort !== null && ctype_digit($cohort)) ? (int) $cohort : null,
            'laptop_used' => $this->cell($row, $columns['laptop']),
            'submitted_at' => $this->parseTimestamp((string) $row[$columns['timestamp']]),
            'answers' => $answers,
        ];
    }

    protected function cell(array $row, ?int $index): ?string
    {
        if ($index === null) {
            return null;
        }

        $value = trim((string) preg_replace('/\s+/u', ' ', (string) ($row[$index] ?? '')));

        return $value === '' ? null : $value;
    }

    /**
     * Format Google Sheets yang terlihat di data kalian adalah bulan/tanggal/tahun (9/11/2026 = 11 September).
     * Format lain dicoba sebagai cadangan. Gagal baca tidak menggagalkan impor (submitted_at dikosongkan).
     */
    protected function parseTimestamp(string $value): ?Carbon
    {
        $value = trim($value);

        foreach (['n/j/Y G:i:s', 'j/n/Y G:i:s', 'Y-m-d G:i:s', 'n/j/Y G:i'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            $errors = \DateTimeImmutable::getLastErrors();

            $clean = $errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0);

            if ($date !== false && $clean) {
                return Carbon::instance($date);
            }
        }

        return null;
    }

    protected function store(array $data, string $reference): void
    {
        DB::transaction(function () use ($data, $reference) {
            $respondent = SurveyRespondent::create([
                'name' => $data['name'],
                'gender' => $data['gender'],
                'cohort_year' => $data['cohort_year'],
                'laptop_used' => $data['laptop_used'],
                'source' => 'csv',
                'external_ref' => $reference,
                'submitted_at' => $data['submitted_at'],
            ]);

            foreach ($data['answers'] as $criterionId => $answer) {
                $respondent->responses()->create([
                    'criterion_id' => $criterionId,
                    'likert' => $answer['likert'],
                    'priority_rank' => $answer['priority_rank'],
                ]);
            }
        });
    }
}
