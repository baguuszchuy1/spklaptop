<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_respondents', function (Blueprint $table) {
            $table->id();
            $table->string('name');                            // nama/inisial
            $table->string('gender', 20)->nullable();
            $table->unsignedSmallInteger('cohort_year')->nullable(); // angkatan, mis. 2024
            $table->string('laptop_used')->nullable();         // merk + seri laptop yang dipakai (isian bebas)

            $table->string('source', 20)->default('manual');   // manual | csv | google_form
            $table->string('external_ref')->nullable()->unique(); // penanda unik baris impor (mis. Timestamp Google Form)
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_respondents');
    }
};
