@extends('layouts.app')
@section('title', 'Edit Pelanggan')
@section('content')
<div class="max-w-xl">
    <x-card class="p-6">
        <form method="POST" action="{{ route('customers.update', $customer) }}" class="space-y-4">
            @csrf @method('PUT')
            <x-input name="name" label="Nama" :value="$customer->name" required />
            <x-input name="phone" label="No. WhatsApp / Telepon" :value="$customer->phone" required />
            <x-input name="address" label="Alamat" :value="$customer->address" />
            <x-textarea name="notes" label="Catatan" :value="$customer->notes" />
            <div class="flex gap-2 pt-2">
                <x-btn type="submit">Update</x-btn>
                <x-btn href="{{ route('customers.index') }}" variant="secondary">Batal</x-btn>
            </div>
        </form>
    </x-card>
</div>
@endsection
