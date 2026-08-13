@extends('layouts.app')
@section('title', 'Tambah User')
@section('content')
<div class="max-w-xl">
    <x-card class="p-6">
        <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
            @csrf
            <x-input name="name" label="Nama" required />
            <x-input name="email" label="Email" type="email" required />
            <x-input name="password" label="Password" type="password" required />
            <x-input name="password_confirmation" label="Konfirmasi Password" type="password" required />
            <x-select name="role" label="Role" required>
                <option value="kasir">Kasir</option>
                <option value="admin">Admin / Owner</option>
            </x-select>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-sky-600"> Aktif
            </label>
            <div class="flex gap-2">
                <x-btn type="submit">Simpan</x-btn>
                <x-btn href="{{ route('users.index') }}" variant="secondary">Batal</x-btn>
            </div>
        </form>
    </x-card>
</div>
@endsection
