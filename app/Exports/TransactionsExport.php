<?php

namespace App\Exports;

use App\Models\Transaction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class TransactionsExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping
{
    public function __construct(
        protected string $dateFrom,
        protected string $dateTo,
    ) {}

    public function collection(): Collection
    {
        return Transaction::with(['customer', 'user'])
            ->whereDate('created_at', '>=', $this->dateFrom)
            ->whereDate('created_at', '<=', $this->dateTo)
            ->orderBy('created_at')
            ->get();
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function headings(): array
    {
        return [
            'Kode Invoice',
            'Tanggal',
            'Pelanggan',
            'Telepon',
            'Kasir',
            'Total',
            'Status Bayar',
            'Metode Bayar',
            'Status Kerja',
            'Catatan',
        ];
    }

    /** @param  Transaction  $row */
    public function map($row): array
    {
        return [
            $row->invoice_code,
            $row->created_at->format('Y-m-d H:i'),
            $row->customer?->name,
            $row->customer?->phone,
            $row->user?->name,
            (float) $row->total,
            $row->paymentStatusLabel(),
            $row->payment_method === 'tripay' ? 'Online' : ($row->payment_method ?? '-'),
            $row->workStatusLabel(),
            $row->notes,
        ];
    }
}
