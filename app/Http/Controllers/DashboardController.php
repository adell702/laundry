<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Transaction;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = now()->toDateString();

        $todayIncome = Transaction::query()
            ->whereDate('created_at', $today)
            ->where('payment_status', 'lunas')
            ->sum('total');

        $todayTransactions = Transaction::query()
            ->whereDate('created_at', $today)
            ->count();

        $pendingPayment = Transaction::query()
            ->where('payment_status', 'belum_lunas')
            ->sum('total');

        $inProgress = Transaction::query()
            ->whereNotIn('work_status', ['diambil'])
            ->count();

        $todayExpense = Expense::query()
            ->whereDate('expense_date', $today)
            ->sum('amount');

        $recentTransactions = Transaction::with(['customer', 'user'])
            ->latest()
            ->limit(8)
            ->get();

        $monthlyIncome = Transaction::query()
            ->where('payment_status', 'lunas')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        $customerCount = Customer::count();

        return view('dashboard', compact(
            'todayIncome',
            'todayTransactions',
            'pendingPayment',
            'inProgress',
            'todayExpense',
            'recentTransactions',
            'monthlyIncome',
            'customerCount',
        ));
    }
}
