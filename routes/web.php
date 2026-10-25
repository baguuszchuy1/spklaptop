<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CriterionController;
use App\Http\Controllers\CriterionLevelController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaptopController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\SawController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\WeightController;
use Illuminate\Support\Facades\Route;

// Halaman publik (tanpa login)
Route::get('/', [PublicController::class, 'home'])->name('public.home');
Route::get('/rekomendasi', [PublicController::class, 'recommendation'])->name('public.recommendation');
Route::get('/katalog', [PublicController::class, 'catalog'])->name('public.catalog');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Kriteria
    Route::resource('criteria', CriterionController::class)
        ->except(['show'])
        ->parameters(['criteria' => 'criterion']);

    // Laptop (alternatif)
    Route::resource('laptops', LaptopController::class)->except(['show']);

    // Data survei (impor CSV dari Google Form). Rute spesifik harus di atas survey/{respondent}.
    Route::get('survey', [SurveyController::class, 'index'])->name('survey.index');
    Route::post('survey/import', [SurveyController::class, 'import'])->name('survey.import');
    Route::delete('survey/bulk', [SurveyController::class, 'bulkDestroy'])->name('survey.bulkDestroy');
    Route::delete('survey/all', [SurveyController::class, 'destroyAll'])->name('survey.destroyAll');
    Route::delete('survey/{respondent}', [SurveyController::class, 'destroy'])->name('survey.destroy');

    // Bobot kriteria (ROC vs Likert)
    Route::get('weights', [WeightController::class, 'index'])->name('weights.index');

    // Perhitungan SAW (transparan) + analisis sensitivitas
    Route::get('saw', [SawController::class, 'index'])->name('saw.index');

    // Rubrik (level) per kriteria
    Route::post('criteria/{criterion}/levels', [CriterionLevelController::class, 'store'])->name('criteria.levels.store');
    Route::put('levels/{level}', [CriterionLevelController::class, 'update'])->name('levels.update');
    Route::delete('levels/{level}', [CriterionLevelController::class, 'destroy'])->name('levels.destroy');
});
