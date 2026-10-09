<?php

namespace App\Application\Fiscal;

use App\Domain\Fiscal\Enums\FiscalDocumentType;
use App\Domain\Fiscal\Enums\InvoiceStatus;
use App\Domain\Fiscal\Exceptions\CompanyNotFiscalReady;
use App\Models\Client;
use App\Models\FiscalSequence;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Tenancy\CompanyContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateInvoice
{
    private const SCALE = 6;

    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly CheckFiscalReadiness $readinessChecker,
        private readonly AllocateFiscalFolio $folioAllocator,
        private readonly CalculateInvoiceLine $lineCalculator,
    ) {
    }

    public function execute(
        User $user,
        array $data
    ): Invoice {
        $company =
            $this->companyContext->company();

        $readiness =
            $this->readinessChecker
                ->execute($company);

        if (!$readiness->ready) {
            throw new CompanyNotFiscalReady(
                $readiness->checks
            );
        }

        return DB::transaction(
            function () use (
                $company,
                $user,
                $data
            ): Invoice {

                $fiscalProfile =
                    $company
                        ->fiscalProfile()
                        ->where(
                            'status',
                            'active'
                        )
                        ->firstOrFail();

                $client =
                    Client::query()
                        ->forCompany($company)
                        ->whereKey(
                            $data['client_id']
                        )
                        ->where(
                            'status',
                            'active'
                        )
                        ->firstOrFail();

                $sequence =
                    FiscalSequence::query()
                        ->forCompany($company)
                        ->whereKey(
                            $data[
                                'fiscal_sequence_id'
                            ]
                        )
                        ->where(
                            'document_type',
                            FiscalDocumentType::Invoice->value
                        )
                        ->where(
                            'status',
                            'active'
                        )
                        ->firstOrFail();

                $folio =
                    $this->folioAllocator
                        ->executeWithinTransaction(
                            $sequence->id,
                            $company->id
                        );

                $invoice =
                    new Invoice();

                $invoice->company_id =
                    $company->id;

                $invoice->created_by =
                    $user->id;

                $invoice->client_id =
                    $client->id;

                $invoice->fiscal_sequence_id =
                    $sequence->id;

                $invoice->series =
                    $sequence->series;

                $invoice->folio =
                    $folio;

                $invoice->internal_folio =
                    $sequence->series
                    . '-'
                    . $folio;

                /*
                |--------------------------------------------------------------------------
                | Issuer snapshot
                |--------------------------------------------------------------------------
                */

                $invoice->issuer_rfc =
                    $fiscalProfile->rfc;

                $invoice->issuer_name =
                    $fiscalProfile->legal_name;

                $invoice->issuer_tax_regime =
                    $fiscalProfile->tax_regime;

                $invoice->issuer_postal_code =
                    $fiscalProfile->postal_code;

                /*
                |--------------------------------------------------------------------------
                | Receiver snapshot
                |--------------------------------------------------------------------------
                */

                $invoice->receiver_rfc =
                    $client->rfc;

                $invoice->receiver_name =
                    $client->tax_name;

                $invoice->receiver_tax_regime =
                    $client->tax_regime;

                $invoice->receiver_postal_code =
                    $client->postal_code;

                $invoice->receiver_email =
                    $client->email;

                /*
                |--------------------------------------------------------------------------
                | CFDI options
                |--------------------------------------------------------------------------
                */

                $invoice->payment_form =
                    $data['payment_form'];

                $invoice->payment_method =
                    strtoupper(
                        $data['payment_method']
                    );

                $invoice->cfdi_use =
                    strtoupper(
                        $data['cfdi_use']
                    );

                $invoice->currency =
                    'MXN';

                $invoice->exchange_rate =
                    '1.000000';

                $invoice->status =
                    InvoiceStatus::Ready;

                $invoice->subtotal =
                    '0.000000';

                $invoice->discount =
                    '0.000000';

                $invoice->transferred_taxes =
                    '0.000000';

                $invoice->withheld_taxes =
                    '0.000000';

                $invoice->total =
                    '0.000000';

                $invoice->save();

                /*
                |--------------------------------------------------------------------------
                | Totals
                |--------------------------------------------------------------------------
                */

                $subtotal =
                    '0.000000';

                $discount =
                    '0.000000';

                $transferred =
                    '0.000000';

                $withheld =
                    '0.000000';

                $total =
                    '0.000000';

                foreach (
                    $data['items']
                    as $index => $itemData
                ) {
                    $product =
                        Product::query()
                            ->forCompany($company)
                            ->whereKey(
                                $itemData[
                                    'product_id'
                                ]
                            )
                            ->where(
                                'status',
                                'active'
                            )
                            ->firstOrFail();

                    $calculation =
                        $this->lineCalculator
                            ->execute(
                                $product,
                                (string)
                                $itemData[
                                    'quantity'
                                ],
                                (string)
                                (
                                    $itemData[
                                        'discount'
                                    ]
                                    ?? '0'
                                )
                            );

                    $item =
                        $invoice
                            ->items()
                            ->create([
                                'product_id' =>
                                    $product->id,

                                'line_number' =>
                                    $index + 1,

                                'sat_product_code' =>
                                    $product
                                        ->sat_product_code,

                                'unit_code' =>
                                    $product
                                        ->unit_code,

                                'description' =>
                                    $product
                                        ->description,

                                'tax_object' =>
                                    $product
                                        ->tax_object,

                                'quantity' =>
                                    $itemData[
                                        'quantity'
                                    ],

                                'unit_price' =>
                                    $product
                                        ->unit_price,

                                'subtotal' =>
                                    $calculation
                                        ->subtotal,

                                'discount' =>
                                    $calculation
                                        ->discount,

                                'taxable_base' =>
                                    $calculation
                                        ->taxableBase,

                                'transferred_taxes' =>
                                    $calculation
                                        ->transferredTaxes,

                                'withheld_taxes' =>
                                    $calculation
                                        ->withheldTaxes,

                                'total' =>
                                    $calculation
                                        ->total,
                            ]);

                    foreach (
                        $calculation->taxes
                        as $tax
                    ) {
                        $item
                            ->taxes()
                            ->create($tax);
                    }

                    $subtotal = bcadd(
                        $subtotal,
                        $calculation->subtotal,
                        self::SCALE
                    );

                    $discount = bcadd(
                        $discount,
                        $calculation->discount,
                        self::SCALE
                    );

                    $transferred = bcadd(
                        $transferred,
                        $calculation
                            ->transferredTaxes,
                        self::SCALE
                    );

                    $withheld = bcadd(
                        $withheld,
                        $calculation
                            ->withheldTaxes,
                        self::SCALE
                    );

                    $total = bcadd(
                        $total,
                        $calculation->total,
                        self::SCALE
                    );
                }

                $invoice->subtotal =
                    $subtotal;

                $invoice->discount =
                    $discount;

                $invoice->transferred_taxes =
                    $transferred;

                $invoice->withheld_taxes =
                    $withheld;

                $invoice->total =
                    $total;

                $invoice->save();

                return $invoice->load(
                    'items.taxes'
                );
            },
            attempts: 3
        );
    }
}
