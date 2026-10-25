@extends('layouts.app')

@section('title', 'Tambah Kriteria')

@section('content')
    <h1 class="text-2xl font-bold mb-1 text-slate-800">Tambah Kriteria</h1>
    <p class="text-sm text-slate-500 mb-6">Setelah disimpan, kalian bisa melengkapi rubriknya di halaman berikutnya.</p>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-6 max-w-3xl">
        @include('criteria._form', [
            'action' => route('criteria.store'),
            'method' => 'POST',
            'submitLabel' => 'Simpan Kriteria',
        ])
    </div>
@endsection
