<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laptops', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // tipe lengkap, mis. "ASUS VivoBook 14 A1404"
            $table->string('brand')->nullable();

            $table->unsignedBigInteger('price');    // Rupiah
            $table->string('processor_name');
            $table->unsignedInteger('processor_score')->nullable(); // skor benchmark CPU (sebutkan sumbernya di notes)
            $table->unsignedSmallInteger('ram_gb');
            $table->string('storage_type', 30);     // HDD | SSD SATA | SSD NVMe | SSD + HDD
            $table->unsignedInteger('storage_gb');

            // Jejak sumber data, supaya bisa dipertanggungjawabkan di naskah jurnal.
            $table->string('spec_source')->nullable();     // URL / nama sumber spesifikasi dan harga
            $table->date('spec_accessed_on')->nullable();  // tanggal data diakses (harga berubah-ubah)
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laptops');
    }
};
