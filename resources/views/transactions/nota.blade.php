<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Nota {{ $transaction->invoice_code }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12px; color: #111; padding: 16px; max-width: 320px; margin: 0 auto; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .sub { text-align: center; color: #555; margin-bottom: 12px; font-size: 11px; }
        .line { border-top: 1px dashed #999; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .r { text-align: right; }
        .bold { font-weight: 700; }
        .mt { margin-top: 8px; }
        @media print { body { padding: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:12px;text-align:center">
        <button onclick="window.print()" style="padding:8px 16px;cursor:pointer">Cetak</button>
    </div>

    <h1>SUPER CLEAN LAUNDRY</h1>
    <div class="sub">
        Perum Dukuh Zamrud, Ruko Blok L No 1<br>
        Padurenan, Kota Bekasi
    </div>

    <div class="line"></div>
    <table>
        <tr><td>Invoice</td><td class="r">{{ $transaction->invoice_code }}</td></tr>
        <tr><td>Tanggal</td><td class="r">{{ $transaction->created_at->format('d/m/Y H:i') }}</td></tr>
        <tr><td>Kasir</td><td class="r">{{ $transaction->user?->name }}</td></tr>
        <tr><td>Pelanggan</td><td class="r">{{ $transaction->customer?->name }}</td></tr>
        <tr><td>Telepon</td><td class="r">{{ $transaction->customer?->phone }}</td></tr>
    </table>
    <div class="line"></div>

    <table>
        @foreach($transaction->items as $item)
            <tr>
                <td colspan="2" class="bold">{{ $item->service?->name }}</td>
            </tr>
            <tr>
                <td>{{ $item->quantity }} {{ $item->service?->unit }} x {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="r">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>

    <div class="line"></div>
    <table>
        <tr class="bold">
            <td>TOTAL</td>
            <td class="r">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Status Bayar</td>
            <td class="r">{{ $transaction->paymentStatusLabel() }}</td>
        </tr>
        <tr>
            <td>Status Kerja</td>
            <td class="r">{{ $transaction->workStatusLabel() }}</td>
        </tr>
        @if($transaction->estimated_ready_at)
        <tr>
            <td>Estimasi</td>
            <td class="r">{{ $transaction->estimated_ready_at->format('d/m/Y H:i') }}</td>
        </tr>
        @endif
    </table>

    @if($transaction->notes)
        <div class="line"></div>
        <div>Catatan: {{ $transaction->notes }}</div>
    @endif

    <div class="line"></div>
    <div class="sub mt">
        Terima kasih!<br>
        Lacak: {{ url('/lacak') }}<br>
        Kode + no. telepon pelanggan
    </div>
</body>
</html>
