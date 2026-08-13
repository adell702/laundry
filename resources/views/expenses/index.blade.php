@extends('layouts.app')
@section('title', 'Pengeluaran')
@section('content')
<div class="space-y-4">
    <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari..." class="rounded-lg border-slate-300 text-sm">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-lg border-slate-300 text-sm">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-lg border-slate-300 text-sm">
            <x-btn type="submit" variant="secondary">Filter</x-btn>
        </form>
        <x-btn href="{{ route('expenses.create') }}">+ Pengeluaran</x-btn>
    </div>

    <x-card class="p-4">
        <div class="text-sm text-slate-500">Total filter</div>
        <div class="text-xl font-bold text-rose-600">Rp {{ number_format($total, 0, ',', '.') }}</div>
    </x-card>

    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-5 py-3 font-medium">Tanggal</th>
                        <th class="px-5 py-3 font-medium">Judul</th>
                        <th class="px-5 py-3 font-medium">Kategori</th>
                        <th class="px-5 py-3 font-medium">Jumlah</th>
                        <th class="px-5 py-3 font-medium">Oleh</th>
                        <th class="px-5 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($expenses as $e)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">{{ $e->expense_date->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 font-medium">{{ $e->title }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $e->category ?? '-' }}</td>
                            <td class="px-5 py-3 font-medium">Rp {{ number_format($e->amount, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $e->user?->name }}</td>
                            <td class="px-5 py-3 text-right space-x-2">
                                <a href="{{ route('expenses.edit', $e) }}" class="text-sky-700 hover:underline">Edit</a>
                                <form method="POST" action="{{ route('expenses.destroy', $e) }}" class="inline" onsubmit="return confirm('Hapus?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t">{{ $expenses->links() }}</div>
    </x-card>
</div>
@endsection
