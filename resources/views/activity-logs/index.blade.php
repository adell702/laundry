@extends('layouts.app')
@section('title', 'Log Aktivitas')
@section('content')
<div class="space-y-4">
    <form method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari log..." class="rounded-lg border-slate-300 text-sm w-72">
        <x-btn type="submit" variant="secondary">Cari</x-btn>
    </form>

    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-5 py-3 font-medium">Waktu</th>
                        <th class="px-5 py-3 font-medium">User</th>
                        <th class="px-5 py-3 font-medium">Aksi</th>
                        <th class="px-5 py-3 font-medium">Deskripsi</th>
                        <th class="px-5 py-3 font-medium">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="px-5 py-3">{{ $log->user?->name ?? 'System' }}</td>
                            <td class="px-5 py-3"><x-badge color="blue">{{ $log->action }}</x-badge></td>
                            <td class="px-5 py-3">{{ $log->description }}</td>
                            <td class="px-5 py-3 text-slate-400">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">Belum ada log.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t">{{ $logs->links() }}</div>
    </x-card>
</div>
@endsection
