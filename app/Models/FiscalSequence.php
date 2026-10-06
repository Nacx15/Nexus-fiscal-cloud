<?php

namespace App\Models;

use App\Domain\Fiscal\Enums\FiscalDocumentType;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiscalSequence extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'document_type',
        'series',
        'next_number',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'document_type' =>
                FiscalDocumentType::class,

            'next_number' =>
                'integer',
        ];
    }
}
