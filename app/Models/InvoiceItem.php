<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceItem extends Model
{
    protected $fillable = [
        'product_id',
        'line_number',

        'sat_product_code',
        'unit_code',
        'description',
        'tax_object',

        'quantity',
        'unit_price',

        'subtotal',
        'discount',
        'taxable_base',

        'transferred_taxes',
        'withheld_taxes',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'unit_price' => 'decimal:6',

            'subtotal' => 'decimal:6',
            'discount' => 'decimal:6',
            'taxable_base' => 'decimal:6',

            'transferred_taxes' => 'decimal:6',
            'withheld_taxes' => 'decimal:6',

            'total' => 'decimal:6',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(
            Invoice::class
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class
        );
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(
            InvoiceItemTax::class
        );
    }
}
