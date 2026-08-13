@extends('layouts.app')
@section('title', 'Edit Pengeluaran')
@section('content')
<div class="max-w-xl">
    <x-card class="p-6">
        <form method="POST" action="{{ route('expenses.update', $expense) }}" class="space-y-4">
            @csrf @method('PUT')
            <x-input name="title" label="Judul" :value="$expense->title" required />
            <x-input name="category" label="Kategori" :value="$expense->category" />
            <x-input name="amount" label="Jumlah" type="number" min="0" step="100" :value="$expense->amount" required />
            <x-input name="expense_date" label="Tanggal" type="date" :value="$expense->expense_date->format('Y-m-d')" required />
            <x-textarea name="notes" label="Catatan" :value="$expense->notes" />
            <div class="flex gap-2">
                <x-btn type="submit">Update</x-btn>
                <x-btn href="{{ route('expenses.index') }}" variant="secondary">Batal</x-btn>
            </div>
        </form>
    </x-card>
</div>
@endsection
