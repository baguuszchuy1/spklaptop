@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1 class="text-2xl font-bold mb-1 text-slate-800">Dashboard</h1>
    <p class="text-sm text-slate-500 mb-6">Ringkasan data dan kesiapan perhitungan SPK pemilihan laptop (SAW dengan pembobotan ROC).</p>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5">
            <p class="text-xs text-slate-500 mb-1">Kriteria</p>
            <p class="text-2xl font-bold text-slate-800">{{ $criteriaCount }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5">
            <p class="text-xs text-slate-500 mb-1">Laptop (alternatif)</p>
            <p class="text-2xl font-bold text-slate-800">{{ $laptopCount }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5">
            <p class="text-xs text-slate-500 mb-1">Responden survei</p>
            <p class="text-2xl font-bold text-slate-800">{{ $respondentCount }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5">
            <p class="text-xs text-slate-500 mb-1">Responden valid</p>
            <p class="text-2xl font-bold {{ $validRespondents > 0 ? 'text-emerald-600' : 'text-amber-500' }}">{{ $validRespondents }}</p>
        </div>
    </div>

    @php
        $checks = [
            [
                'ok' => $criteriaCount > 0 && $criteriaWithoutRubric === 0,
                'label' => 'Rubrik semua kriteria sudah terisi',
                'hint' => $criteriaWithoutRubric > 0 ? "{$criteriaWithoutRubric} kriteria memakai rubrik tapi belum punya level." : 'Atur di menu Kriteria & Rubrik.',
            ],
            [
                'ok' => $laptopCount >= 5,
                'label' => 'Minimal 5 laptop sudah dimasukkan',
                'hint' => "Saat ini {$laptopCount} laptop. Tambahkan lewat menu Data Laptop.",
            ],
            [
                'ok' => $validRespondents > 0,
                'label' => 'Data survei prioritas sudah masuk',
                'hint' => $respondentCount > $validRespondents
                    ? ($respondentCount - $validRespondents) . ' responden dilewati karena jawabannya tidak valid.'
                    : 'Impor CSV dari Google Form lewat menu Data Survei.',
            ],
        ];
    @endphp

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 md:p-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">Kesiapan perhitungan</h2>
        <ul class="space-y-3">
            @foreach ($checks as $check)
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full text-xs font-bold
                                 {{ $check['ok'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $check['ok'] ? '✓' : '!' }}
                    </span>
                    <div>
                        <p class="text-sm font-medium text-slate-700">{{ $check['label'] }}</p>
                        <p class="text-xs text-slate-400">{{ $check['hint'] }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endsection
