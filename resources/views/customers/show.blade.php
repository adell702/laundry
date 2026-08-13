@extends('layouts.app')
@section('title', 'Detail Pelanggan')
@section('content')
<div class="space-y-4 max-w-3xl">
    <x-card class="p-6">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-xl font-bold">{{ $customer->name }}</h2>
                <p class="text-slate-500 mt-1">{{ $customer->phone }}</p>
                <p class="text-sm text-slate-400 mt-1">{{ $customer->address ?? 'Alamat belum diisi' }}</p>
                @if($customer->notes)
                    <p class="text-sm mt-3 text-slate-600">{{ $customer->notes }}</p>
                @endif
            </div>
            <div class="flex gap-2">
                <x-btn href="{{ route('customers.edit', $customer) }}" variant="secondary">Edit</x-btn>
                <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Hapus pelanggan?')">
                    @csrf @method('DELETE')
                    <x-btn type="submit" variant="danger">Hapus</x-btn>
                </form>
            </div>
        </div>
    </x-card>

    <x-card>
        <div class="px-5 py-4 border-b border-slate-100 font-semibold">Riwayat Transaksi</div>
        <div class="divide-y divide-slate-100">
            @forelse($customer->transactions as $t)
                <a href="{{ route('transactions.show', $t) }}" class="flex items-center justify-between px-5 py-3 hover:bg-slate-50">
                    <div>
                        <div class="font-medium text-sky-700">{{ $t->invoice_code }}</div>
                        <div class="text-xs text-slate-400">{{ $t->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-medium">Rp {{ number_format($t->total, 0, ',', '.') }}</div>
                        <x-badge :color="$t->payment_status === 'lunas' ? 'green' : 'yellow'">{{ $t->paymentStatusLabel() }}</x-badge>
                    </div>
                </a>
            @empty
                <div class="px-5 py-6 text-center text-slate-400 text-sm">Belum ada transaksi.</div>
            @endforelse
        </div>
    </x-card>
</div>
@endsection
