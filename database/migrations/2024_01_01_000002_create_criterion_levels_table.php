<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criterion_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();

            // Kriteria numerik (RAM, kapasitas, skor processor): pakai min_value/max_value (inklusif,
            // kosongkan salah satunya untuk "tanpa batas"). label hanya keterangan, mis. "8 GB".
            // Kriteria kategorikal (tipe penyimpanan): min/max dikosongkan, label harus sama
            // persis dengan nilai spesifikasi, mis. "SSD NVMe".
            $table->string('label');
            $table->decimal('min_value', 14, 2)->nullable();
            $table->decimal('max_value', 14, 2)->nullable();

            $table->unsignedTinyInteger('score'); // skor rubrik, 1-5 (makin tinggi makin baik)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criterion_levels');
    }
};
