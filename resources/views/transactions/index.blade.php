@extends('layouts.app')
@section('title', 'Transaksi')
@section('content')
<div class="space-y-4">
    <div class="flex flex-col lg:flex-row gap-3 lg:items-end lg:justify-between">
        <form method="GET" class="grid grid-cols-2 md:grid-cols-5 gap-2 flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Invoice / nama..." class="rounded-lg border-slate-300 text-sm">
            <select name="payment_status" class="rounded-lg border-slate-300 text-sm">
                <option value="">Semua bayar</option>
                <option value="lunas" @selected(request('payment_status')==='lunas')>Lunas</option>
                <option value="belum_lunas" @selected(request('payment_status')==='belum_lunas')>Belum Lunas</option>
            </select>
            <select name="work_status" class="rounded-lg border-slate-300 text-sm">
                <option value="">Semua status</option>
                @foreach(['diterima','dicuci','disetrika','selesai','diambil'] as $s)
                    <option value="{{ $s }}" @selected(request('work_status')===$s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-lg border-slate-300 text-sm">
            <div class="flex gap-2">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-lg border-slate-300 text-sm flex-1">
                <x-btn type="submit" variant="secondary">Filter</x-btn>
            </div>
        </form>
        <x-btn href="{{ route('transactions.create') }}">+ Transaksi</x-btn>
    </div>

    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-5 py-3 font-medium">Invoice</th>
                        <th class="px-5 py-3 font-medium">Pelanggan</th>
                        <th class="px-5 py-3 font-medium">Total</th>
                        <th class="px-5 py-3 font-medium">Bayar</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium">Kasir</th>
                        <th class="px-5 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $t)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="font-medium">{{ $t->invoice_code }}</div>
                                <div class="text-xs text-slate-400">{{ $t->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="px-5 py-3">
                                <div>{{ $t->customer?->name }}</div>
                                <div class="text-xs text-slate-400">{{ $t->customer?->phone }}</div>
                            </td>
                            <td class="px-5 py-3 font-medium">Rp {{ number_format($t->total, 0, ',', '.') }}</td>
                            <td class="px-5 py-3">
                                <x-badge :color="$t->payment_status === 'lunas' ? 'green' : 'yellow'">{{ $t->paymentStatusLabel() }}</x-badge>
                            </td>
                            <td class="px-5 py-3"><x-badge color="blue">{{ $t->workStatusLabel() }}</x-badge></td>
                            <td class="px-5 py-3 text-slate-500">{{ $t->user?->name }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('transactions.show', $t) }}" class="text-sky-700 hover:underline">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-slate-400">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t">{{ $transactions->links() }}</div>
    </x-card>
</div>
@endsection
