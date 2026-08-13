<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    public function index(): View
    {
        return view('tracking.index');
    }

    public function show(Request $request): View
    {
        $request->validate([
            'invoice_code' => ['required', 'string'],
            'phone' => ['required', 'string'],
        ]);

        $transaction = Transaction::with(['customer', 'items.service'])
            ->where('invoice_code', $request->invoice_code)
            ->whereHas('customer', fn ($q) => $q->where('phone', $request->phone))
            ->first();

        return view('tracking.show', compact('transaction'));
    }
}
