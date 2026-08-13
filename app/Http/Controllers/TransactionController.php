<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $transactions = Transaction::with(['customer', 'user'])
            ->when($request->search, function ($q, $search) {
                $q->where('invoice_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            })
            ->when($request->payment_status, fn ($q, $s) => $q->where('payment_status', $s))
            ->when($request->work_status, fn ($q, $s) => $q->where('work_status', $s))
            ->when($request->date_from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('transactions.index', compact('transactions'));
    }

    public function create(): View
    {
        $customers = Customer::orderBy('name')->get();
        $services = Service::where('is_active', true)->orderBy('name')->get();

        return view('transactions.create', compact('customers', 'services'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'new_customer_name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'new_customer_phone' => ['required_without:customer_id', 'nullable', 'string', 'max:20'],
            'new_customer_address' => ['nullable', 'string', 'max:255'],
            'payment_status' => ['required', 'in:belum_lunas,lunas'],
            'payment_method' => ['nullable', 'in:tunai,transfer,qris,lainnya'],
            'notes' => ['nullable', 'string'],
            'estimated_ready_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['required', 'exists:services,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.1'],
        ]);

        $transaction = DB::transaction(function () use ($data, $request) {
            if (! empty($data['customer_id'])) {
                $customerId = $data['customer_id'];
            } else {
                $customer = Customer::firstOrCreate(
                    ['phone' => $data['new_customer_phone']],
                    [
                        'name' => $data['new_customer_name'],
                        'address' => $data['new_customer_address'] ?? null,
                    ]
                );
                $customerId = $customer->id;
            }

            $transaction = Transaction::create([
                'customer_id' => $customerId,
                'user_id' => auth()->id(),
                'payment_status' => $data['payment_status'],
                'payment_method' => $data['payment_status'] === 'lunas'
                    ? ($data['payment_method'] ?? 'tunai')
                    : null,
                'notes' => $data['notes'] ?? null,
                'estimated_ready_at' => $data['estimated_ready_at'] ?? null,
                'paid_at' => $data['payment_status'] === 'lunas' ? now() : null,
                'work_status' => 'diterima',
                'total' => 0,
            ]);

            $total = 0;
            foreach ($data['items'] as $item) {
                $service = Service::findOrFail($item['service_id']);
                $qty = (float) $item['quantity'];
                $subtotal = $qty * (float) $service->price;
                $total += $subtotal;

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'service_id' => $service->id,
                    'quantity' => $qty,
                    'unit_price' => $service->price,
                    'subtotal' => $subtotal,
                ]);
            }

            $transaction->update(['total' => $total]);

            return $transaction->fresh(['customer', 'items.service', 'user']);
        });

        ActivityLog::record(
            'create',
            "Input transaksi {$transaction->invoice_code} total Rp ".number_format($transaction->total, 0, ',', '.'),
            $transaction
        );

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Transaksi berhasil disimpan.');
    }

    public function show(Transaction $transaction): View
    {
        $transaction->load(['customer', 'user', 'items.service']);

        return view('transactions.show', compact('transaction'));
    }

    public function edit(Transaction $transaction): View
    {
        $transaction->load(['customer', 'items.service']);
        $customers = Customer::orderBy('name')->get();
        $services = Service::where('is_active', true)->orderBy('name')->get();

        return view('transactions.edit', compact('transaction', 'customers', 'services'));
    }

    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'payment_status' => ['required', 'in:belum_lunas,lunas'],
            'payment_method' => ['nullable', 'in:tunai,transfer,qris,lainnya'],
            'work_status' => ['required', 'in:diterima,dicuci,disetrika,selesai,diambil'],
            'notes' => ['nullable', 'string'],
            'estimated_ready_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['required', 'exists:services,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.1'],
        ]);

        DB::transaction(function () use ($data, $transaction) {
            $transaction->items()->delete();

            $total = 0;
            foreach ($data['items'] as $item) {
                $service = Service::findOrFail($item['service_id']);
                $qty = (float) $item['quantity'];
                $subtotal = $qty * (float) $service->price;
                $total += $subtotal;

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'service_id' => $service->id,
                    'quantity' => $qty,
                    'unit_price' => $service->price,
                    'subtotal' => $subtotal,
                ]);
            }

            $transaction->update([
                'customer_id' => $data['customer_id'],
                'payment_status' => $data['payment_status'],
                'payment_method' => $data['payment_status'] === 'lunas'
                    ? ($data['payment_method'] ?? $transaction->payment_method ?? 'tunai')
                    : null,
                'work_status' => $data['work_status'],
                'notes' => $data['notes'] ?? null,
                'estimated_ready_at' => $data['estimated_ready_at'] ?? null,
                'total' => $total,
                'paid_at' => $data['payment_status'] === 'lunas'
                    ? ($transaction->paid_at ?? now())
                    : null,
                'completed_at' => in_array($data['work_status'], ['selesai', 'diambil'], true)
                    ? ($transaction->completed_at ?? now())
                    : null,
            ]);
        });

        ActivityLog::record('update', "Ubah transaksi {$transaction->invoice_code}", $transaction);

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Transaction $transaction): RedirectResponse
    {
        $data = $request->validate([
            'work_status' => ['nullable', 'in:diterima,dicuci,disetrika,selesai,diambil'],
            'payment_status' => ['nullable', 'in:belum_lunas,lunas'],
            'payment_method' => ['nullable', 'in:tunai,transfer,qris,lainnya'],
        ]);

        $updates = [];

        if (! empty($data['work_status'])) {
            $updates['work_status'] = $data['work_status'];
            if (in_array($data['work_status'], ['selesai', 'diambil'], true)) {
                $updates['completed_at'] = $transaction->completed_at ?? now();
            }
        }

        if (! empty($data['payment_status'])) {
            $updates['payment_status'] = $data['payment_status'];
            if ($data['payment_status'] === 'lunas') {
                $updates['paid_at'] = $transaction->paid_at ?? now();
                $updates['payment_method'] = $data['payment_method'] ?? $transaction->payment_method ?? 'tunai';
            } else {
                $updates['paid_at'] = null;
            }
        }

        $transaction->update($updates);

        ActivityLog::record(
            'status',
            "Update status transaksi {$transaction->invoice_code}: kerja={$transaction->work_status}, bayar={$transaction->payment_status}",
            $transaction
        );

        return back()->with('success', 'Status berhasil diperbarui.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $code = $transaction->invoice_code;
        $transaction->delete();

        ActivityLog::record('delete', "Hapus transaksi {$code}");

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil dihapus.');
    }

    public function nota(Transaction $transaction): View
    {
        $transaction->load(['customer', 'user', 'items.service']);

        return view('transactions.nota', compact('transaction'));
    }
}
