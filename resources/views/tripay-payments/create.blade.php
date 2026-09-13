@extends('layouts.app')
@section('title', 'Pembayaran Online')
@section('content')
<div class="max-w-3xl space-y-4">
    <div>
        <h2 class="text-xl font-bold">Pilih channel pembayaran</h2>
        <p class="text-sm text-slate-500 mt-1">
            {{ $transaction->invoice_code }} · Rp {{ number_format($transaction->total, 0, ',', '.') }} · {{ ucfirst(config('services.tripay.mode', 'sandbox')) }}
        </p>
    </div>

    <x-card class="p-6">
        <form method="POST" action="{{ route('tripay-payments.store', $transaction) }}" class="space-y-5">
            @csrf

            <x-input
                name="customer_email"
                type="email"
                label="Email pelanggan"
                :value="$transaction->customer?->email"
                autocomplete="email"
                required
            />
            <p class="-mt-3 text-xs text-slate-500">Email ini digunakan untuk proses pembayaran dan disimpan pada profil pelanggan.</p>

            <x-select name="method" label="Metode pembayaran" required>
                <option value="">— Pilih channel —</option>
                @foreach($channels as $group => $groupChannels)
                    <optgroup label="{{ $group }}">
                        @foreach($groupChannels as $channel)
                            @php
                                $minimum = (int) ($channel['minimum_amount'] ?? 0);
                                $maximum = (int) ($channel['maximum_amount'] ?? PHP_INT_MAX);
                                $eligible = (float) $transaction->total >= $minimum
                                    && (float) $transaction->total <= $maximum;
                                $flatFee = (int) data_get($channel, 'total_fee.flat', 0);
                                $percentFee = (float) data_get($channel, 'total_fee.percent', 0);
                            @endphp
                            <option value="{{ $channel['code'] }}" @selected(old('method') === $channel['code']) @disabled(!$eligible)>
                                {{ $channel['name'] }}
                                @if($flatFee || $percentFee)
                                    — biaya Rp {{ number_format($flatFee, 0, ',', '.') }}{{ $percentFee ? ' + '.rtrim(rtrim(number_format($percentFee, 2), '0'), '.').'%' : '' }}
                                @endif
                                @unless($eligible)
                                    — nominal tidak memenuhi batas
                                @endunless
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </x-select>

            @if(config('services.tripay.mode') === 'sandbox')
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Mode uji tidak memindahkan uang sungguhan.
                </div>
            @endif

            <div class="flex flex-wrap gap-2">
                <x-btn type="submit">Buat Pembayaran</x-btn>
                <x-btn href="{{ route('transactions.show', $transaction) }}" variant="secondary">Batal</x-btn>
            </div>
        </form>
    </x-card>
</div>
@endsection
