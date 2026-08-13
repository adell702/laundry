@extends('layouts.app')
@section('title', 'Tambah Layanan')
@section('content')
<div class="max-w-xl">
    <x-card class="p-6">
        <form method="POST" action="{{ route('services.store') }}" class="space-y-4">
            @csrf
            <x-input name="name" label="Nama Layanan" required />
            <x-input name="unit" label="Satuan" value="kg" required />
            <x-input name="price" label="Harga" type="number" step="100" min="0" required />
            <x-textarea name="description" label="Deskripsi" />
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-sky-600"> Aktif
            </label>
            <div class="flex gap-2">
                <x-btn type="submit">Simpan</x-btn>
                <x-btn href="{{ route('services.index') }}" variant="secondary">Batal</x-btn>
            </div>
        </form>
    </x-card>
</div>
@endsection
