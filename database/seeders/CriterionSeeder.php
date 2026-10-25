<?php

namespace Database\Seeders;

use App\Models\Criterion;
use Illuminate\Database\Seeder;

class CriterionSeeder extends Seeder
{
    /**
     * PENTING: semua ambang dan skor rubrik di bawah ini hanya CONTOH supaya sistem bisa dicoba.
     * Sebelum dipakai untuk penelitian, ganti dengan ambang yang punya dasar yang bisa disitir
     * (mis. skor benchmark publik, rentang harga pasar) lewat halaman admin Kriteria & Rubrik.
     *
     * Urutan C1..C5 mengikuti urutan kriteria di Google Form.
     */
    public function run(): void
    {
        $criteria = [
            [
                'code' => 'C1', 'name' => 'Harga', 'type' => 'cost',
                'spec_field' => 'price', 'scoring_mode' => 'raw', 'sort_order' => 1,
                'levels' => [], // Harga memakai nilai asli (Rupiah), tanpa rubrik
            ],
            [
                'code' => 'C2', 'name' => 'Processor', 'type' => 'benefit',
                'spec_field' => 'processor_score', 'scoring_mode' => 'rubric', 'sort_order' => 2,
                'levels' => [ // berdasarkan skor benchmark CPU
                    ['label' => '< 5.000',          'min' => null,  'max' => 4999,  'score' => 1],
                    ['label' => '5.000 - 9.999',    'min' => 5000,  'max' => 9999,  'score' => 2],
                    ['label' => '10.000 - 14.999',  'min' => 10000, 'max' => 14999, 'score' => 3],
                    ['label' => '15.000 - 19.999',  'min' => 15000, 'max' => 19999, 'score' => 4],
                    ['label' => '>= 20.000',        'min' => 20000, 'max' => null,  'score' => 5],
                ],
            ],
            [
                'code' => 'C3', 'name' => 'RAM', 'type' => 'benefit',
                'spec_field' => 'ram_gb', 'scoring_mode' => 'rubric', 'sort_order' => 3,
                'levels' => [
                    ['label' => '<= 4 GB',  'min' => null, 'max' => 4,    'score' => 1],
                    ['label' => '8 GB',     'min' => 5,    'max' => 8,    'score' => 3],
                    ['label' => '12 GB',    'min' => 9,    'max' => 12,   'score' => 4],
                    ['label' => '>= 16 GB', 'min' => 13,   'max' => null, 'score' => 5],
                ],
            ],
            [
                'code' => 'C4', 'name' => 'Kapasitas Penyimpanan', 'type' => 'benefit',
                'spec_field' => 'storage_gb', 'scoring_mode' => 'rubric', 'sort_order' => 4,
                'levels' => [
                    ['label' => '<= 128 GB', 'min' => null, 'max' => 255,  'score' => 1],
                    ['label' => '256 GB',    'min' => 256,  'max' => 511,  'score' => 2],
                    ['label' => '512 GB',    'min' => 512,  'max' => 999,  'score' => 4],
                    ['label' => '>= 1 TB',   'min' => 1000, 'max' => null, 'score' => 5],
                ],
            ],
            [
                'code' => 'C5', 'name' => 'Tipe Penyimpanan', 'type' => 'benefit',
                'spec_field' => 'storage_type', 'scoring_mode' => 'rubric', 'sort_order' => 5,
                'levels' => [ // kategorikal: label harus sama persis dengan isian tipe penyimpanan laptop
                    ['label' => 'HDD',       'min' => null, 'max' => null, 'score' => 1],
                    ['label' => 'SSD SATA',  'min' => null, 'max' => null, 'score' => 3],
                    ['label' => 'SSD + HDD', 'min' => null, 'max' => null, 'score' => 4],
                    ['label' => 'SSD NVMe',  'min' => null, 'max' => null, 'score' => 5],
                ],
            ],
        ];

        foreach ($criteria as $row) {
            $levels = $row['levels'];
            unset($row['levels']);

            $criterion = Criterion::updateOrCreate(['code' => $row['code']], $row);

            // Rubrik hanya diisi kalau masih kosong, supaya seeder tidak menimpa
            // rubrik yang sudah kalian sesuaikan lewat halaman admin.
            if ($criterion->levels()->count() === 0) {
                foreach ($levels as $level) {
                    $criterion->levels()->create([
                        'label' => $level['label'],
                        'min_value' => $level['min'],
                        'max_value' => $level['max'],
                        'score' => $level['score'],
                    ]);
                }
            }
        }
    }
}
