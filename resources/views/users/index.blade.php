@extends('layouts.app')
@section('title', 'Karyawan / User')
@section('content')
<div class="space-y-4">
    <div class="flex justify-end">
        <x-btn href="{{ route('users.create') }}">+ User</x-btn>
    </div>
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-5 py-3 font-medium">Nama</th>
                        <th class="px-5 py-3 font-medium">Email</th>
                        <th class="px-5 py-3 font-medium">Role</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($users as $user)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium">{{ $user->name }}</td>
                            <td class="px-5 py-3">{{ $user->email }}</td>
                            <td class="px-5 py-3"><x-badge :color="$user->role==='admin'?'purple':'blue'">{{ strtoupper($user->role) }}</x-badge></td>
                            <td class="px-5 py-3">
                                <x-badge :color="$user->is_active?'green':'red'">{{ $user->is_active?'Aktif':'Nonaktif' }}</x-badge>
                            </td>
                            <td class="px-5 py-3 text-right space-x-2">
                                <a href="{{ route('users.edit', $user) }}" class="text-sky-700 hover:underline">Edit</a>
                                @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline" onsubmit="return confirm('Hapus user?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline">Hapus</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t">{{ $users->links() }}</div>
    </x-card>
</div>
@endsection
