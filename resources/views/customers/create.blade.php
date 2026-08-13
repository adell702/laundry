@extends('layouts.app')
@section('title', 'Tambah Pelanggan')
@section('content')
<div class="max-w-xl">
    <x-card class="p-6">
        <form method="POST" action="{{ route('customers.store') }}" class="space-y-4">
            @csrf
            <x-input name="name" label="Nama" required />
            <x-input name="phone" label="No. WhatsApp / Telepon" required />
            <x-input name="address" label="Alamat" />
            <x-textarea name="notes" label="Catatan" />
            <div class="flex gap-2 pt-2">
                <x-btn type="submit">Simpan</x-btn>
                <x-btn href="{{ route('customers.index') }}" variant="secondary">Batal</x-btn>
            </div>
        </form>
    </x-card>
</div>
@endsection
