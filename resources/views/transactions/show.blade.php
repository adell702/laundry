@extends('layouts.app')
@section('title', 'Detail Transaksi')
@section('content')
<div class="space-y-4 max-w-4xl">
    <div class="flex flex-wrap gap-2 justify-between items-center">
        <div>
            <h2 class="text-xl font-bold">{{ $transaction->invoice_code }}</h2>
            <p class="text-sm text-slate-500">{{ $transaction->created_at->format('d/m/Y H:i') }} · Kasir: {{ $transaction->user?->name }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-btn href="{{ route('transactions.nota', $transaction) }}" variant="secondary" target="_blank">Cetak Nota</x-btn>
            @unless($transaction->hasLockedTripayPayment())
                <x-btn href="{{ route('transactions.edit', $transaction) }}" variant="secondary">Edit</x-btn>
            @endunless
            @if(auth()->user()->isAdmin() && $transaction->tripayPayments->isEmpty())
            <form method="POST" action="{{ route('transactions.destroy', $transaction) }}" onsubmit="return confirm('Hapus transaksi?')">
                @csrf @method('DELETE')
                <x-btn type="submit" variant="danger">Hapus</x-btn>
            </form>
            @endif
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        <x-card class="p-5 space-y-2">
            <h3 class="font-semibold text-slate-700">Pelanggan</h3>
            <div class="text-lg font-medium">{{ $transaction->customer?->name }}</div>
            <div class="text-sm text-slate-500">{{ $transaction->customer?->phone }}</div>
            <div class="text-sm text-slate-400">{{ $transaction->customer?->address }}</div>
            @if($transaction->customer?->phone)
                <a href="https://wa.me/{{ $transaction->customer->whatsappNumber() }}?text={{ urlencode('Halo '.$transaction->customer->name.', nota laundry '.$transaction->invoice_code.' total Rp '.number_format($transaction->total,0,',','.').'. Status: '.$transaction->workStatusLabel()) }}"
                   target="_blank" class="inline-block mt-2 text-sm text-emerald-700 font-medium hover:underline">
                    Kirim e-nota via WhatsApp
                </a>
            @endif
        </x-card>

        <x-card class="p-5 space-y-3">
            <h3 class="font-semibold text-slate-700">Update Status</h3>
            <form method="POST" action="{{ route('transactions.status', $transaction) }}" class="grid grid-cols-2 gap-3">
                @csrf @method('PATCH')
                <div class="{{ $transaction->hasLockedTripayPayment() ? 'col-span-2' : '' }}">
                    <x-select name="work_status" label="Status Kerja">
                        @foreach(['diterima','dicuci','disetrika','selesai','diambil'] as $s)
                            <option value="{{ $s }}" @selected($transaction->work_status===$s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </x-select>
                </div>
                @if($transaction->hasLockedTripayPayment())
                    <div class="col-span-2 rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-xs text-sky-800">
                        Status pembayaran dikelola otomatis oleh sistem pembayaran online.
                    </div>
                @else
                    <x-select name="payment_status" label="Status Bayar">
                        <option value="belum_lunas" @selected($transaction->payment_status==='belum_lunas')>Belum Lunas</option>
                        <option value="lunas" @selected($transaction->payment_status==='lunas')>Lunas</option>
                    </x-select>
                    <x-select name="payment_method" label="Metode">
                        @foreach(['tunai','transfer','qris','lainnya'] as $m)
                            <option value="{{ $m }}" @selected($transaction->payment_method===$m)>{{ ucfirst($m) }}</option>
                        @endforeach
                    </x-select>
                @endif
                <div class="flex items-end {{ $transaction->hasLockedTripayPayment() ? 'col-span-2' : '' }}">
                    <x-btn type="submit" class="w-full">Simpan Status</x-btn>
                </div>
            </form>
        </x-card>
    </div>

    <x-card class="p-5 space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="font-semibold text-slate-800">Pembayaran Online</h3>
                <p class="text-sm text-slate-500 mt-1">Pembayaran digital melalui channel bank, e-wallet, atau gerai yang tersedia.</p>
            </div>
            @if($transaction->payment_status === 'belum_lunas' && !$transaction->latestTripayPayment?->isPending() && $tripayConfigured)
                <x-btn href="{{ route('tripay-payments.create', $transaction) }}">Buat Pembayaran Online</x-btn>
            @endif
        </div>

        @if(!$tripayConfigured)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Pembayaran online belum tersedia. Hubungi administrator aplikasi.
            </div>
        @elseif($transaction->tripayPayments->isEmpty())
            <p class="text-sm text-slate-500">Belum ada pembayaran online untuk transaksi ini.</p>
        @else
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-2 font-medium">Referensi</th>
                            <th class="px-4 py-2 font-medium">Channel</th>
                            <th class="px-4 py-2 font-medium">Status</th>
                            <th class="px-4 py-2 font-medium text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($transaction->tripayPayments as $payment)
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs">{{ $payment->reference ?? $payment->merchant_ref }}</td>
                                <td class="px-4 py-2">{{ $payment->payment_name ?? $payment->payment_method }}</td>
                                <td class="px-4 py-2"><x-badge :color="$payment->badgeColor()">{{ $payment->statusLabel() }}</x-badge></td>
                                <td class="px-4 py-2 text-right"><a href="{{ route('tripay-payments.show', [$transaction, $payment]) }}" class="text-sky-700 hover:underline">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <x-card>
        <div class="px-5 py-4 border-b font-semibold">Item</div>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-5 py-3">Layanan</th>
                    <th class="px-5 py-3">Qty</th>
                    <th class="px-5 py-3">Harga</th>
                    <th class="px-5 py-3 text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($transaction->items as $item)
                    <tr>
                        <td class="px-5 py-3">{{ $item->service_name ?? $item->service?->name }}</td>
                        <td class="px-5 py-3">{{ $item->quantity }} {{ $item->service_unit ?? $item->service?->unit }}</td>
                        <td class="px-5 py-3">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right font-medium">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2">
                    <td colspan="3" class="px-5 py-3 text-right font-semibold">Total</td>
                    <td class="px-5 py-3 text-right text-lg font-bold text-sky-800">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
        @if($transaction->notes)
            <div class="px-5 py-3 border-t text-sm text-slate-600"><span class="font-medium">Catatan:</span> {{ $transaction->notes }}</div>
        @endif
    </x-card>

    <div class="flex flex-wrap gap-3 text-sm">
        <x-badge :color="$transaction->payment_status === 'lunas' ? 'green' : 'yellow'">Bayar: {{ $transaction->paymentStatusLabel() }}</x-badge>
        @if($transaction->payment_method)
            <x-badge color="slate">Metode: {{ $transaction->payment_method === 'tripay' ? 'Online' : ucfirst($transaction->payment_method) }}</x-badge>
        @endif
        <x-badge color="blue">Kerja: {{ $transaction->workStatusLabel() }}</x-badge>
        @if($transaction->estimated_ready_at)
            <x-badge color="purple">Estimasi: {{ $transaction->estimated_ready_at->format('d/m/Y H:i') }}</x-badge>
        @endif
    </div>
</div>
@endsection
