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
            <x-btn href="{{ route('transactions.edit', $transaction) }}" variant="secondary">Edit</x-btn>
            @if(auth()->user()->isAdmin())
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
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/','', $transaction->customer->phone) }}?text={{ urlencode('Halo '.$transaction->customer->name.', nota laundry '.$transaction->invoice_code.' total Rp '.number_format($transaction->total,0,',','.').'. Status: '.$transaction->workStatusLabel()) }}"
                   target="_blank" class="inline-block mt-2 text-sm text-emerald-700 font-medium hover:underline">
                    Kirim e-nota via WhatsApp
                </a>
            @endif
        </x-card>

        <x-card class="p-5 space-y-3">
            <h3 class="font-semibold text-slate-700">Update Status</h3>
            <form method="POST" action="{{ route('transactions.status', $transaction) }}" class="grid grid-cols-2 gap-3">
                @csrf @method('PATCH')
                <x-select name="work_status" label="Status Kerja">
                    @foreach(['diterima','dicuci','disetrika','selesai','diambil'] as $s)
                        <option value="{{ $s }}" @selected($transaction->work_status===$s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </x-select>
                <x-select name="payment_status" label="Status Bayar">
                    <option value="belum_lunas" @selected($transaction->payment_status==='belum_lunas')>Belum Lunas</option>
                    <option value="lunas" @selected($transaction->payment_status==='lunas')>Lunas</option>
                </x-select>
                <x-select name="payment_method" label="Metode">
                    @foreach(['tunai','transfer','qris','lainnya'] as $m)
                        <option value="{{ $m }}" @selected($transaction->payment_method===$m)>{{ ucfirst($m) }}</option>
                    @endforeach
                </x-select>
                <div class="flex items-end">
                    <x-btn type="submit" class="w-full">Simpan Status</x-btn>
                </div>
            </form>
        </x-card>
    </div>

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
                        <td class="px-5 py-3">{{ $item->service?->name }}</td>
                        <td class="px-5 py-3">{{ $item->quantity }} {{ $item->service?->unit }}</td>
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
        <x-badge color="blue">Kerja: {{ $transaction->workStatusLabel() }}</x-badge>
        @if($transaction->estimated_ready_at)
            <x-badge color="purple">Estimasi: {{ $transaction->estimated_ready_at->format('d/m/Y H:i') }}</x-badge>
        @endif
    </div>
</div>
@endsection
