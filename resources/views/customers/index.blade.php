@extends('layouts.app')
@section('title', 'Pelanggan')
@section('content')
<div class="space-y-4">
    <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / telepon..." class="rounded-lg border-slate-300 text-sm w-64">
            <x-btn type="submit" variant="secondary">Cari</x-btn>
        </form>
        <x-btn href="{{ route('customers.create') }}">+ Pelanggan</x-btn>
    </div>

    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-5 py-3 font-medium">Nama</th>
                        <th class="px-5 py-3 font-medium">Telepon / WA</th>
                        <th class="px-5 py-3 font-medium">Alamat</th>
                        <th class="px-5 py-3 font-medium">Transaksi</th>
                        <th class="px-5 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium">{{ $customer->name }}</td>
                            <td class="px-5 py-3">{{ $customer->phone }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $customer->address ?? '-' }}</td>
                            <td class="px-5 py-3">{{ $customer->transactions_count }}</td>
                            <td class="px-5 py-3 text-right space-x-2">
                                <a href="{{ route('customers.show', $customer) }}" class="text-sky-700 hover:underline">Detail</a>
                                <a href="{{ route('customers.edit', $customer) }}" class="text-slate-600 hover:underline">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-slate-100">{{ $customers->links() }}</div>
    </x-card>
</div>
@endsection
