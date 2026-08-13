@extends('layouts.app')
@section('title', 'Edit Layanan')
@section('content')
<div class="max-w-xl">
    <x-card class="p-6">
        <form method="POST" action="{{ route('services.update', $service) }}" class="space-y-4">
            @csrf @method('PUT')
            <x-input name="name" label="Nama Layanan" :value="$service->name" required />
            <x-input name="unit" label="Satuan" :value="$service->unit" required />
            <x-input name="price" label="Harga" type="number" step="100" min="0" :value="$service->price" required />
            <x-textarea name="description" label="Deskripsi" :value="$service->description" />
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $service->is_active)) class="rounded border-slate-300 text-sky-600"> Aktif
            </label>
            <div class="flex gap-2">
                <x-btn type="submit">Update</x-btn>
                <x-btn href="{{ route('services.index') }}" variant="secondary">Batal</x-btn>
            </div>
        </form>
    </x-card>
</div>
@endsection
