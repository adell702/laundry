<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'transaction_id',
    'merchant_ref',
    'reference',
    'payment_method',
    'payment_name',
    'customer_email',
    'status',
    'amount',
    'fee_merchant',
    'fee_customer',
    'total_fee',
    'amount_received',
    'pay_code',
    'pay_url',
    'checkout_url',
    'qr_string',
    'qr_url',
    'instructions',
    'provider_data',
    'last_callback',
    'failure_message',
    'expired_at',
    'paid_at',
])]
class TripayPayment extends Model
{
    public function getRouteKeyName(): string
    {
        return 'merchant_ref';
    }

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'fee_merchant' => 'integer',
            'fee_customer' => 'integer',
            'total_fee' => 'integer',
            'amount_received' => 'integer',
            'instructions' => 'array',
            'provider_data' => 'array',
            'last_callback' => 'array',
            'expired_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['CREATING', 'UNPAID'], true);
    }

    public function isPayable(): bool
    {
        return $this->status === 'UNPAID'
            && ($this->expired_at === null || $this->expired_at->isFuture());
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'CREATING' => 'Membuat pembayaran',
            'UNPAID' => 'Menunggu pembayaran',
            'PAID' => 'Lunas',
            'FAILED' => 'Gagal',
            'EXPIRED' => 'Kedaluwarsa',
            'REFUND' => 'Dikembalikan',
            'ERROR' => 'Gagal dibuat',
            default => $this->status,
        };
    }

    public function badgeColor(): string
    {
        return match ($this->status) {
            'PAID' => 'green',
            'UNPAID', 'CREATING' => 'yellow',
            'FAILED', 'EXPIRED', 'ERROR' => 'red',
            'REFUND' => 'purple',
            default => 'slate',
        };
    }

    public function safeCheckoutUrl(): ?string
    {
        return $this->safeTripayUrl($this->checkout_url);
    }

    public function safeQrUrl(): ?string
    {
        return $this->safeTripayUrl($this->qr_url);
    }

    private function safeTripayUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $scheme === 'https'
            && ($host === 'tripay.co.id' || str_ends_with($host, '.tripay.co.id'))
                ? $url
                : null;
    }
}
