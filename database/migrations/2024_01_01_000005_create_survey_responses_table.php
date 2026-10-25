<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_respondent_id')->constrained('survey_respondents')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();

            $table->unsignedTinyInteger('likert');         // 1-5: "Seberapa penting ...?"
            $table->unsignedTinyInteger('priority_rank');  // 1-5: urutan prioritas (1 = paling penting)

            $table->timestamps();

            // Satu jawaban per kriteria untuk tiap responden.
            $table->unique(['survey_respondent_id', 'criterion_id']);
            // Satu prioritas hanya boleh dipakai sekali per responden (urutan harus valid).
            // Catatan: kolom sengaja bernama priority_rank, bukan "rank", karena RANK
            // adalah kata cadangan di MySQL 8.
            $table->unique(['survey_respondent_id', 'priority_rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
