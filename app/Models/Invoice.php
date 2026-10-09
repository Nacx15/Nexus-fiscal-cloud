<?php

namespace App\Models;

use App\Domain\Fiscal\Enums\InvoiceStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\CarbonInterface;
use LogicException;

class Invoice extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'created_by',
        'client_id',
        'fiscal_sequence_id',

        'series',
        'folio',
        'internal_folio',

        'uuid',
        'issued_at',
        'stamped_at',

        'issuer_rfc',
        'issuer_name',
        'issuer_tax_regime',
        'issuer_postal_code',

        'receiver_rfc',
        'receiver_name',
        'receiver_tax_regime',
        'receiver_postal_code',
        'receiver_email',

        'payment_form',
        'payment_method',
        'cfdi_use',

        'currency',
        'exchange_rate',

        'subtotal',
        'discount',
        'transferred_taxes',
        'withheld_taxes',
        'total',

	'stamp_attempts',
	'last_stamp_attempt_at',
	'stamp_error_code',
	'stamp_error_message',

	'status',
    ];

    protected function casts(): array
    {
        return [
            'folio' => 'integer',

            'exchange_rate' =>
                'decimal:6',

            'subtotal' =>
                'decimal:6',

            'discount' =>
                'decimal:6',

            'transferred_taxes' =>
                'decimal:6',

            'withheld_taxes' =>
                'decimal:6',

            'total' =>
                'decimal:6',

            'issued_at' =>
                'datetime',

            'stamped_at' =>
                'datetime',

     	    'stamp_attempts' => 'integer',

	    'last_stamp_attempt_at' =>
		    'datetime',

            'status' =>
                InvoiceStatus::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(
            Client::class
        );
    }

    public function fiscalSequence(): BelongsTo
    {
        return $this->belongsTo(
            FiscalSequence::class
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            InvoiceItem::class
        );
    }

	public function canBeStamped(): bool
	{
	    return in_array(
	        $this->status,
	        [
	            InvoiceStatus::Ready,
	            InvoiceStatus::StampFailed,
	        ],
	        true
	    );
	}

	public function markStamping(): void
	{
	    if (!$this->canBeStamped()) {
	        throw new LogicException(
	            sprintf(
	                'Invoice in status [%s] cannot start stamping.',
	                $this->status->value
	            )
	        );
	    }

	    $this->status =
	        InvoiceStatus::Stamping;

	    $this->stamp_attempts++;

	    $this->last_stamp_attempt_at =
	        now();

	    $this->stamp_error_code =
	        null;

	    $this->stamp_error_message =
	        null;

	    if ($this->issued_at === null) {
	        $this->issued_at =
	            now();
	    }
	}

	public function markStamped(
	    string $uuid,
	    CarbonInterface $stampedAt
	): void {
	    if (
	        $this->status
	        !== InvoiceStatus::Stamping
	    ) {
	        throw new LogicException(
	            'Only a stamping invoice can be marked as stamped.'
	        );
	    }

	    $this->uuid =
	        strtoupper($uuid);

	    $this->stamped_at =
	        $stampedAt;

	    $this->status =
	        InvoiceStatus::Stamped;

	    $this->stamp_error_code =
	        null;

	    $this->stamp_error_message =
	        null;
	}

	public function markStampFailed(
	    ?string $errorCode,
	    string $errorMessage
	): void {
	    if (
	        $this->status
	        !== InvoiceStatus::Stamping
	    ) {
	        throw new LogicException(
	            'Only a stamping invoice can fail stamping.'
	        );
	    }

	    $this->status =
	        InvoiceStatus::StampFailed;

	    $this->stamp_error_code =
	        $errorCode;

	    $this->stamp_error_message =
	        $errorMessage;
	}


}
