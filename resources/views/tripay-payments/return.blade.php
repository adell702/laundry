<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Status Pembayaran {{ $transaction->invoice_code }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-sky-50 to-slate-100 font-sans antialiased flex items-center justify-center p-4">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-lg border border-slate-200 p-8">
        <div class="text-center">
            <div class="text-sm font-semibold uppercase tracking-wide text-sky-700">{{ config('app.name') }}</div>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Status Pembayaran</h1>
            <p class="mt-1 font-mono text-sm text-slate-500">{{ $transaction->invoice_code }}</p>
        </div>

        <div class="my-6 rounded-xl p-5 text-center {{ $payment->status === 'PAID' ? 'bg-emerald-50 text-emerald-900' : ($payment->isPayable() ? 'bg-amber-50 text-amber-900' : 'bg-slate-100 text-slate-800') }}">
            <div class="text-sm">{{ $payment->payment_name ?? $payment->payment_method }}</div>
            <div class="mt-1 text-xl font-bold">{{ $payment->statusLabel() }}</div>
            <div class="mt-2 text-lg">Rp {{ number_format($payment->amount + ($payment->fee_customer ?? 0), 0, ',', '.') }}</div>
        </div>

        @if($syncError)
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Status terbaru belum dapat diambil. Sistem pembayaran tetap akan memperbarui transaksi secara otomatis.
            </div>
        @endif

        @if($payment->pay_code && $payment->isPayable())
            <div class="mb-4 rounded-lg bg-slate-100 p-4 text-center">
                <div class="text-xs uppercase tracking-wide text-slate-500">Kode bayar / Virtual Account</div>
                <div class="mt-1 font-mono text-xl font-bold break-all">{{ $payment->pay_code }}</div>
            </div>
        @endif

        @if($qrUrl && $payment->isPayable())
            <img src="{{ $qrUrl }}" alt="Kode QR pembayaran" class="mx-auto mb-4 max-w-64 rounded-lg border border-slate-200">
        @endif

        <div class="flex flex-col gap-2">
            @if($payment->isPayable() && $checkoutUrl)
                <a href="{{ $checkoutUrl }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-sky-700 text-white text-sm font-semibold hover:bg-sky-800">Kembali ke Pembayaran</a>
            @endif
            <a href="{{ route('tracking.show', ['invoice_code' => $transaction->invoice_code, 'phone' => $transaction->customer?->phone]) }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50">Lacak Cucian</a>
        </div>
    </div>
</body>
</html>
