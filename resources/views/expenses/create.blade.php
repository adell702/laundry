@extends('layouts.app')
@section('title', 'Tambah Pengeluaran')
@section('content')
<div class="max-w-xl">
    <x-card class="p-6">
        <form method="POST" action="{{ route('expenses.store') }}" class="space-y-4">
            @csrf
            <x-input name="title" label="Judul" required />
            <x-input name="category" label="Kategori" placeholder="deterjen, listrik, gaji..." />
            <x-input name="amount" label="Jumlah" type="number" min="0" step="100" required />
            <x-input name="expense_date" label="Tanggal" type="date" :value="now()->toDateString()" required />
            <x-textarea name="notes" label="Catatan" />
            <div class="flex gap-2">
                <x-btn type="submit">Simpan</x-btn>
                <x-btn href="{{ route('expenses.index') }}" variant="secondary">Batal</x-btn>
            </div>
        </form>
    </x-card>
</div>
@endsection
