<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyFiscalProfile extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyFiscalProfileFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'rfc',
        'legal_name',
        'tax_regime',
        'postal_code',
        'email',
        'status',
    ];
}
