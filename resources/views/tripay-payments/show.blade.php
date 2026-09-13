@extends('layouts.app')
@section('title', 'Detail Pembayaran Online')
@section('content')
<div class="max-w-4xl space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-xl font-bold">{{ $payment->payment_name ?? $payment->payment_method }}</h2>
                <x-badge :color="$payment->badgeColor()">{{ $payment->statusLabel() }}</x-badge>
                <x-badge color="purple">{{ ucfirst(config('services.tripay.mode', 'sandbox')) }}</x-badge>
            </div>
            <p class="text-sm text-slate-500 mt-1">{{ $transaction->invoice_code }} · {{ $payment->merchant_ref }}</p>
        </div>
        <x-btn href="{{ route('transactions.show', $transaction) }}" variant="secondary">Kembali ke Transaksi</x-btn>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        <x-card class="p-5 space-y-3">
            <h3 class="font-semibold text-slate-800">Ringkasan</h3>
            <div class="text-sm space-y-2">
                <div class="flex justify-between gap-4"><span class="text-slate-500">Tagihan laundry</span><span>Rp {{ number_format($payment->amount, 0, ',', '.') }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-slate-500">Biaya pelanggan</span><span>Rp {{ number_format($payment->fee_customer ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between gap-4 border-t pt-2 font-semibold"><span>Total bayar</span><span>Rp {{ number_format($payment->amount + ($payment->fee_customer ?? 0), 0, ',', '.') }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-slate-500">Referensi pembayaran</span><span class="font-mono text-xs break-all text-right">{{ $payment->reference ?? '-' }}</span></div>
                @if($payment->expired_at)
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Berlaku sampai</span><span>{{ $payment->expired_at->format('d/m/Y H:i') }}</span></div>
                @endif
                @if($payment->paid_at)
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Dibayar</span><span>{{ $payment->paid_at->format('d/m/Y H:i') }}</span></div>
                @endif
            </div>

            @if($payment->pay_code)
                <div class="rounded-lg bg-slate-100 p-4 text-center">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Kode bayar / Virtual Account</div>
                    <div class="mt-1 font-mono text-xl font-bold break-all">{{ $payment->pay_code }}</div>
                </div>
            @endif

            @if($qrUrl)
                <div class="text-center">
                    <img src="{{ $qrUrl }}" alt="Kode QR pembayaran" class="mx-auto max-w-64 rounded-lg border border-slate-200">
                </div>
            @endif

            <div class="flex flex-wrap gap-2 pt-1">
                @if($payment->isPayable() && $checkoutUrl)
                    <x-btn href="{{ route('tripay-payments.checkout', [$transaction, $payment]) }}" target="_blank">Lanjutkan Pembayaran</x-btn>
                @endif
                @if($payment->reference)
                    <form method="POST" action="{{ route('tripay-payments.sync', [$transaction, $payment]) }}">
                        @csrf
                        <x-btn type="submit" variant="secondary">Sinkronkan Status</x-btn>
                    </form>
                @endif
            </div>
        </x-card>

        <x-card class="p-5">
            <h3 class="font-semibold text-slate-800 mb-3">Instruksi pembayaran</h3>
            @forelse($payment->instructions ?? [] as $instruction)
                <div class="mb-5 last:mb-0">
                    <div class="font-medium text-sm mb-2">{{ $instruction['title'] ?? 'Langkah pembayaran' }}</div>
                    <ol class="list-decimal pl-5 space-y-1 text-sm text-slate-600">
                        @foreach($instruction['steps'] ?? [] as $step)
                            <li>{{ strip_tags($step) }}</li>
                        @endforeach
                    </ol>
                </div>
            @empty
                <p class="text-sm text-slate-500">Lanjutkan ke halaman pembayaran untuk melihat instruksi metode ini.</p>
            @endforelse
        </x-card>
    </div>
</div>
@endsection
