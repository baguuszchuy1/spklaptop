<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criteria', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();           // C1 .. C5
            $table->string('name');                          // Harga, Processor, RAM, dst.
            $table->string('type', 10)->default('benefit');  // benefit (makin besar makin baik) | cost (makin kecil makin baik)

            // Kolom pada tabel laptops yang menjadi sumber nilai kriteria ini:
            // price | processor_score | ram_gb | storage_gb | storage_type
            $table->string('spec_field', 30);

            // raw    = nilai spesifikasi asli langsung dinormalisasi SAW (contoh: Harga)
            // rubric = nilai spesifikasi dikonversi dulu ke skor lewat rubrik (tabel criterion_levels)
            $table->string('scoring_mode', 10)->default('rubric');

            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criteria');
    }
};
