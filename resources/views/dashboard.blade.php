@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <p class="text-slate-500 text-sm">Selamat datang, <span class="font-semibold text-slate-800">{{ auth()->user()->name }}</span></p>
            <p class="text-xs text-slate-400">{{ now()->translatedFormat('l, d F Y') }}</p>
        </div>
        <x-btn href="{{ route('transactions.create') }}" variant="primary">+ Transaksi Baru</x-btn>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <x-stat label="Pemasukan Hari Ini" value="Rp {{ number_format($todayIncome, 0, ',', '.') }}" color="emerald" />
        <x-stat label="Transaksi Hari Ini" value="{{ $todayTransactions }}" color="sky" />
        <x-stat label="Belum Lunas" value="Rp {{ number_format($pendingPayment, 0, ',', '.') }}" color="amber" />
        <x-stat label="Sedang Diproses" value="{{ $inProgress }}" hint="Belum diambil" color="violet" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-card class="p-5">
            <div class="text-xs text-slate-500 uppercase">Pemasukan Bulan Ini</div>
            <div class="mt-1 text-xl font-bold text-emerald-700">Rp {{ number_format($monthlyIncome, 0, ',', '.') }}</div>
        </x-card>
        <x-card class="p-5">
            <div class="text-xs text-slate-500 uppercase">Pengeluaran Hari Ini</div>
            <div class="mt-1 text-xl font-bold text-rose-600">Rp {{ number_format($todayExpense, 0, ',', '.') }}</div>
        </x-card>
        <x-card class="p-5">
            <div class="text-xs text-slate-500 uppercase">Total Pelanggan</div>
            <div class="mt-1 text-xl font-bold text-sky-700">{{ $customerCount }}</div>
        </x-card>
    </div>

    <x-card>
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-semibold">Transaksi Terbaru</h2>
            <a href="{{ route('transactions.index') }}" class="text-sm text-sky-700 hover:underline">Lihat semua</a>
        </div>
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
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentTransactions as $t)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <a href="{{ route('transactions.show', $t) }}" class="font-medium text-sky-700 hover:underline">{{ $t->invoice_code }}</a>
                                <div class="text-xs text-slate-400">{{ $t->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="px-5 py-3">{{ $t->customer?->name }}</td>
                            <td class="px-5 py-3 font-medium">Rp {{ number_format($t->total, 0, ',', '.') }}</td>
                            <td class="px-5 py-3">
                                <x-badge :color="$t->payment_status === 'lunas' ? 'green' : 'yellow'">{{ $t->paymentStatusLabel() }}</x-badge>
                            </td>
                            <td class="px-5 py-3">
                                <x-badge color="blue">{{ $t->workStatusLabel() }}</x-badge>
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $t->user?->name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-slate-400">Belum ada transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
@endsection
