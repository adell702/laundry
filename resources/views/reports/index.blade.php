@extends('layouts.app')
@section('title', 'Laporan')
@section('content')
<div class="space-y-4">
    <form method="GET" class="flex flex-wrap gap-2 items-end">
        <div>
            <label class="text-xs text-slate-500">Dari</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="block rounded-lg border-slate-300 text-sm">
        </div>
        <div>
            <label class="text-xs text-slate-500">Sampai</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="block rounded-lg border-slate-300 text-sm">
        </div>
        <x-btn type="submit" variant="secondary">Tampilkan</x-btn>
        <x-btn href="{{ route('reports.export', ['date_from'=>$dateFrom,'date_to'=>$dateTo]) }}" variant="success">Export Excel</x-btn>
    </form>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat label="Jumlah Transaksi" :value="$count" color="sky" />
        <x-stat label="Pemasukan (Lunas)" value="Rp {{ number_format($income, 0, ',', '.') }}" color="emerald" />
        <x-stat label="Belum Lunas" value="Rp {{ number_format($unpaid, 0, ',', '.') }}" color="amber" />
        <x-stat label="Laba Bersih*" value="Rp {{ number_format($net, 0, ',', '.') }}" hint="Pemasukan − Pengeluaran (Rp {{ number_format($expense,0,',','.') }})" color="violet" />
    </div>

    <x-card>
        <div class="px-5 py-4 border-b font-semibold">Detail Transaksi Periode</div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Invoice</th>
                        <th class="px-5 py-3">Pelanggan</th>
                        <th class="px-5 py-3">Kasir</th>
                        <th class="px-5 py-3">Total</th>
                        <th class="px-5 py-3">Bayar</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($transactions as $t)
                        <tr>
                            <td class="px-5 py-3">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3">
                                <a href="{{ route('transactions.show', $t) }}" class="text-sky-700 hover:underline">{{ $t->invoice_code }}</a>
                            </td>
                            <td class="px-5 py-3">{{ $t->customer?->name }}</td>
                            <td class="px-5 py-3">{{ $t->user?->name }}</td>
                            <td class="px-5 py-3 font-medium">Rp {{ number_format($t->total, 0, ',', '.') }}</td>
                            <td class="px-5 py-3">{{ $t->paymentStatusLabel() }}</td>
                            <td class="px-5 py-3">{{ $t->workStatusLabel() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-slate-400">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
@endsection
