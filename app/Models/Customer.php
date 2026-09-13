<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'phone', 'email', 'address', 'notes'])]
class Customer extends Model
{
    public function whatsappNumber(): string
    {
        $number = preg_replace('/\D+/', '', $this->phone) ?? '';

        if (str_starts_with($number, '0')) {
            return '62'.substr($number, 1);
        }

        if (str_starts_with($number, '8')) {
            return '62'.$number;
        }

        return $number;
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
