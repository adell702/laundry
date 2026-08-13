<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hasil Lacak — AA Laundry</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-sky-50 to-slate-100 font-sans antialiased flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg border border-slate-200 p-8">
        <div class="text-center mb-6">
            <div class="text-2xl font-bold text-sky-900">AA Laundry</div>
            <p class="text-sm text-slate-500 mt-1">Hasil pelacakan</p>
        </div>

        @if(!$transaction)
            <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm text-center">
                Data tidak ditemukan. Cek kode invoice dan nomor telepon.
            </div>
        @else
            @php
                $steps = ['diterima','dicuci','disetrika','selesai','diambil'];
                $current = array_search($transaction->work_status, $steps);
            @endphp
            <div class="space-y-4">
                <div class="text-center">
                    <div class="font-mono font-bold text-lg">{{ $transaction->invoice_code }}</div>
                    <div class="text-sm text-slate-500">{{ $transaction->customer?->name }}</div>
                </div>

                <div class="space-y-2">
                    @foreach($steps as $i => $step)
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                                {{ $i <= $current ? 'bg-sky-600 text-white' : 'bg-slate-200 text-slate-500' }}">
                                {{ $i+1 }}
                            </div>
                            <div class="text-sm {{ $i <= $current ? 'font-semibold text-slate-800' : 'text-slate-400' }}">
                                {{ ucfirst($step) }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="border-t pt-4 text-sm space-y-1">
                    <div class="flex justify-between"><span class="text-slate-500">Total</span><span class="font-semibold">Rp {{ number_format($transaction->total, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Pembayaran</span><span>{{ $transaction->paymentStatusLabel() }}</span></div>
                    @if($transaction->estimated_ready_at)
                        <div class="flex justify-between"><span class="text-slate-500">Estimasi</span><span>{{ $transaction->estimated_ready_at->format('d/m/Y H:i') }}</span></div>
                    @endif
                </div>
            </div>
        @endif

        <a href="{{ route('tracking.index') }}" class="block text-center mt-6 text-sm text-sky-700 hover:underline">Lacak lagi</a>
    </div>
</body>
</html>
