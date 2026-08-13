@extends('layouts.app')
@section('title', 'Edit Transaksi')
@section('content')
<div class="max-w-3xl">
    <x-card class="p-6">
        <form method="POST" action="{{ route('transactions.update', $transaction) }}" class="space-y-6">
            @csrf @method('PUT')

            <x-select name="customer_id" label="Pelanggan" required>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" @selected(old('customer_id', $transaction->customer_id)==$c->id)>{{ $c->name }} ({{ $c->phone }})</option>
                @endforeach
            </x-select>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold">Item Layanan</h3>
                    <button type="button" id="add-item" class="text-sm text-sky-700 font-medium hover:underline">+ Item</button>
                </div>
                <div id="items" class="space-y-2"></div>
                <div class="text-right text-lg font-bold">Total: Rp <span id="grand-total">0</span></div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <x-select name="payment_status" label="Status Pembayaran" required>
                    <option value="belum_lunas" @selected(old('payment_status',$transaction->payment_status)==='belum_lunas')>Belum Lunas</option>
                    <option value="lunas" @selected(old('payment_status',$transaction->payment_status)==='lunas')>Lunas</option>
                </x-select>
                <x-select name="payment_method" label="Metode Bayar">
                    @foreach(['tunai','transfer','qris','lainnya'] as $m)
                        <option value="{{ $m }}" @selected(old('payment_method',$transaction->payment_method)===$m)>{{ ucfirst($m) }}</option>
                    @endforeach
                </x-select>
                <x-select name="work_status" label="Status Pengerjaan" required>
                    @foreach(['diterima','dicuci','disetrika','selesai','diambil'] as $s)
                        <option value="{{ $s }}" @selected(old('work_status',$transaction->work_status)===$s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </x-select>
                <x-input name="estimated_ready_at" label="Estimasi Selesai" type="datetime-local"
                    :value="old('estimated_ready_at', optional($transaction->estimated_ready_at)->format('Y-m-d\TH:i'))" />
                <div class="sm:col-span-2">
                    <x-textarea name="notes" label="Catatan" :value="$transaction->notes" />
                </div>
            </div>

            <div class="flex gap-2">
                <x-btn type="submit">Update</x-btn>
                <x-btn href="{{ route('transactions.show', $transaction) }}" variant="secondary">Batal</x-btn>
            </div>
        </form>
    </x-card>
</div>

@php
$servicesJson = $services->map(fn($s) => [
    'id' => $s->id,
    'name' => $s->name,
    'unit' => $s->unit,
    'price' => (float) $s->price,
])->values();
$existing = $transaction->items->map(fn($i) => [
    'service_id' => $i->service_id,
    'quantity' => (float) $i->quantity,
]);
@endphp

@push('scripts')
<script>
const services = @json($servicesJson);
const existing = @json($existing);
const itemsEl = document.getElementById('items');

function formatRp(n) {
    return new Intl.NumberFormat('id-ID').format(Math.round(n));
}
function recalc() {
    let total = 0;
    itemsEl.querySelectorAll('.item-row').forEach(row => {
        const sid = row.querySelector('.svc').value;
        const qty = parseFloat(row.querySelector('.qty').value) || 0;
        const svc = services.find(s => s.id == sid);
        const sub = svc ? svc.price * qty : 0;
        row.querySelector('.sub').textContent = formatRp(sub);
        total += sub;
    });
    document.getElementById('grand-total').textContent = formatRp(total);
}
function addItem(prefill = {}) {
    const idx = itemsEl.children.length;
    const opts = services.map(s =>
        `<option value="${s.id}" ${prefill.service_id==s.id?'selected':''}>${s.name} — Rp ${formatRp(s.price)}/${s.unit}</option>`
    ).join('');
    const div = document.createElement('div');
    div.className = 'item-row grid grid-cols-12 gap-2 items-end bg-slate-50 p-3 rounded-lg border border-slate-200';
    div.innerHTML = `
        <div class="col-span-12 sm:col-span-6">
            <select name="items[${idx}][service_id]" class="svc w-full rounded-lg border-slate-300 text-sm" required>${opts}</select>
        </div>
        <div class="col-span-6 sm:col-span-3">
            <input type="number" step="0.1" min="0.1" name="items[${idx}][quantity]" value="${prefill.quantity||1}" class="qty w-full rounded-lg border-slate-300 text-sm" required>
        </div>
        <div class="col-span-4 sm:col-span-2 text-right font-semibold">Rp <span class="sub">0</span></div>
        <div class="col-span-2 sm:col-span-1 text-right">
            <button type="button" class="rm text-red-600 text-sm">Hapus</button>
        </div>`;
    itemsEl.appendChild(div);
    div.querySelector('.svc').addEventListener('change', recalc);
    div.querySelector('.qty').addEventListener('input', recalc);
    div.querySelector('.rm').addEventListener('click', () => { div.remove(); reindex(); recalc(); });
    recalc();
}
function reindex() {
    [...itemsEl.querySelectorAll('.item-row')].forEach((row, i) => {
        row.querySelector('.svc').name = `items[${i}][service_id]`;
        row.querySelector('.qty').name = `items[${i}][quantity]`;
    });
}
document.getElementById('add-item').addEventListener('click', () => addItem());
if (existing.length) existing.forEach(i => addItem(i));
else addItem();
</script>
@endpush
@endsection
