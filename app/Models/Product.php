<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'sat_product_code',
        'description',
        'unit_code',
        'unit_price',
        'tax_object',
        'default_tax_rate',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:6',
            'default_tax_rate' => 'decimal:6',
        ];
    }
}
