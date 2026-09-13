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
        $filters = $this->validatedDates($request);
        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->toDateString();

        $transactions = Transaction::with(['customer', 'user'])
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->latest()
            ->get();

        $income = Transaction::query()
            ->where('payment_status', 'lunas')
            ->whereDate('paid_at', '>=', $dateFrom)
            ->whereDate('paid_at', '<=', $dateTo)
            ->sum('total');
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
        $filters = $this->validatedDates($request);
        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->toDateString();

        $filename = "laporan-transaksi-{$dateFrom}-{$dateTo}.xlsx";

        return Excel::download(new TransactionsExport($dateFrom, $dateTo), $filename);
    }

    /** @return array{date_from?: string, date_to?: string} */
    private function validatedDates(Request $request): array
    {
        return $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);
    }
}
