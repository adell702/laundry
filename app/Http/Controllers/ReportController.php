<?php

namespace App\Http\Controllers;

use App\Exports\TransactionsExport;
use App\Models\Expense;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo = $request->date_to ?? now()->toDateString();

        $transactions = Transaction::with(['customer', 'user'])
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->latest()
            ->get();

        $income = $transactions->where('payment_status', 'lunas')->sum('total');
        $unpaid = $transactions->where('payment_status', 'belum_lunas')->sum('total');
        $count = $transactions->count();

        $expense = Expense::query()
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->sum('amount');

        $net = $income - $expense;

        return view('reports.index', compact(
            'transactions',
            'dateFrom',
            'dateTo',
            'income',
            'unpaid',
            'count',
            'expense',
            'net',
        ));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo = $request->date_to ?? now()->toDateString();

        $filename = "laporan-transaksi-{$dateFrom}-{$dateTo}.xlsx";

        return Excel::download(new TransactionsExport($dateFrom, $dateTo), $filename);
    }
}
