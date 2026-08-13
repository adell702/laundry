<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'invoice_code',
    'customer_id',
    'user_id',
    'total',
    'payment_status',
    'payment_method',
    'work_status',
    'notes',
    'paid_at',
    'estimated_ready_at',
    'completed_at',
])]
class Transaction extends Model
{
    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
            'estimated_ready_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            if (empty($transaction->invoice_code)) {
                $transaction->invoice_code = self::generateInvoiceCode();
            }
        });
    }

    public static function generateInvoiceCode(): string
    {
        do {
            $code = 'SCL-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
        } while (self::where('invoice_code', $code)->exists());

        return $code;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function recalculateTotal(): void
    {
        $this->update([
            'total' => $this->items()->sum('subtotal'),
        ]);
    }

    public function workStatusLabel(): string
    {
        return match ($this->work_status) {
            'diterima' => 'Diterima',
            'dicuci' => 'Dicuci',
            'disetrika' => 'Disetrika',
            'selesai' => 'Selesai',
            'diambil' => 'Diambil',
            default => $this->work_status,
        };
    }

    public function paymentStatusLabel(): string
    {
        return $this->payment_status === 'lunas' ? 'Lunas' : 'Belum Lunas';
    }
}
