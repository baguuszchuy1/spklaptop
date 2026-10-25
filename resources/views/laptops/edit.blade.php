@extends('layouts.app')

@section('title', 'Edit Laptop')

@section('content')
    <h1 class="text-2xl font-bold mb-1 text-slate-800">Edit Laptop</h1>
    <p class="text-sm text-slate-500 mb-6">{{ $laptop->name }}</p>

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
        @include('laptops._form', [
            'action' => route('laptops.update', $laptop),
            'method' => 'PUT',
            'submitLabel' => 'Simpan Perubahan',
        ])
    </div>
@endsection
