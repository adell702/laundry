@extends('layouts.app')
@section('title', 'Edit User')
@section('content')
<div class="max-w-xl">
    <x-card class="p-6">
        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-4">
            @csrf @method('PUT')
            <x-input name="name" label="Nama" :value="$user->name" required />
            <x-input name="email" label="Email" type="email" :value="$user->email" required />
            <x-input name="password" label="Password baru (kosongkan jika tidak ubah)" type="password" />
            <x-input name="password_confirmation" label="Konfirmasi Password" type="password" />
            <x-select name="role" label="Role" required>
                <option value="kasir" @selected($user->role==='kasir')>Kasir</option>
                <option value="admin" @selected($user->role==='admin')>Admin / Owner</option>
            </x-select>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked($user->is_active) class="rounded border-slate-300 text-sky-600"> Aktif
            </label>
            <div class="flex gap-2">
                <x-btn type="submit">Update</x-btn>
                <x-btn href="{{ route('users.index') }}" variant="secondary">Batal</x-btn>
            </div>
        </form>
    </x-card>
</div>
@endsection
