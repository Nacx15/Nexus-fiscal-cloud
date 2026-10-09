<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Fiscal\Enums\FiscalCertificateStatus;

class FiscalCertificate extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_fiscal_profile_id',
        'certificate_number',
        'certificate_path',
        'private_key_path',
        'private_key_password',
        'fingerprint_sha256',
        'valid_from',
        'valid_until',
        'status',
    ];

    protected $hidden = [
        'private_key_password',
        'certificate_path',
        'private_key_path',
    ];

    protected function casts(): array
    {
        return [
            'private_key_password' => 'encrypted',

            'valid_from' => 'datetime',

            'valid_until' => 'datetime',

	    'status' => FiscalCertificateStatus::class,
        ];
    }

    public function fiscalProfile(): BelongsTo
    {
        return $this->belongsTo(
            CompanyFiscalProfile::class,
            'company_fiscal_profile_id'
        );
    }

	public function isCurrentlyValid(): bool
	{
	    if (
	        $this->valid_from === null ||
	        $this->valid_until === null
	    ) {
	        return false;
	    }

	    $now = now();

	    return $now->greaterThanOrEqualTo(
	        $this->valid_from
	    ) && $now->lessThanOrEqualTo(
	        $this->valid_until
	    );
	}

	public function isActive(): bool
	{
	    return $this->status
	        === FiscalCertificateStatus::Active;
	}

}
