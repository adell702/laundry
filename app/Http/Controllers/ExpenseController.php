<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $expenses = Expense::with('user')
            ->when($request->date_from, fn ($q, $d) => $q->whereDate('expense_date', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->whereDate('expense_date', '<=', $d))
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->latest('expense_date')
            ->paginate(15)
            ->withQueryString();

        $total = Expense::query()
            ->when($request->date_from, fn ($q, $d) => $q->whereDate('expense_date', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->whereDate('expense_date', '<=', $d))
            ->sum('amount');

        return view('expenses.index', compact('expenses', 'total'));
    }

    public function create(): View
    {
        return view('expenses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['user_id'] = auth()->id();
        $expense = Expense::create($data);

        ActivityLog::record('create', "Tambah pengeluaran {$expense->title}", $expense);

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil ditambahkan.');
    }

    public function edit(Expense $expense): View
    {
        return view('expenses.edit', compact('expense'));
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $expense->update($data);

        ActivityLog::record('update', "Ubah pengeluaran {$expense->title}", $expense);

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $title = $expense->title;
        $expense->delete();

        ActivityLog::record('delete', "Hapus pengeluaran {$title}");

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil dihapus.');
    }
}
