<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItemTax extends Model
{
    protected $fillable = [
        'tax_code',
        'tax_type',
        'factor_type',
        'base',
        'rate_or_quota',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'base' => 'decimal:6',

            'rate_or_quota' =>
                'decimal:6',

            'amount' =>
                'decimal:6',
        ];
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(
            InvoiceItem::class
        );
    }
}
