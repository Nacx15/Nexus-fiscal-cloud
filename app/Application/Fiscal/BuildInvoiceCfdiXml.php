<?php

namespace App\Application\Fiscal;

use App\Application\Fiscal\Data\CfdiBuildResult;
use App\Domain\Fiscal\Enums\FiscalCertificateStatus;
use App\Domain\Fiscal\Exceptions\CfdiBuildException;
use App\Infrastructure\Cfdi\CfdiCreatorFactory;
use App\Models\FiscalCertificate;
use App\Models\Invoice;
use CfdiUtils\Certificado\Certificado;
use Illuminate\Support\Facades\Storage;
use PhpCfdi\Credentials\Credential;
use Throwable;

final class BuildInvoiceCfdiXml
{
    public function __construct(
        private readonly CfdiCreatorFactory $creatorFactory
    ) {
    }

    public function execute(
        Invoice $invoice,
        FiscalCertificate $certificate
    ): CfdiBuildResult {
        /*
        |--------------------------------------------------------------------------
        | Preconditions
        |--------------------------------------------------------------------------
        */

        if (
            $invoice->company_id
            !== $certificate->company_id
        ) {
            throw new CfdiBuildException(
                'Invoice and fiscal certificate belong to different companies.'
            );
        }

        if (
            $certificate->status
            !== FiscalCertificateStatus::Active
        ) {
            throw new CfdiBuildException(
                'Fiscal certificate is not active.'
            );
        }

        if (!$certificate->isCurrentlyValid()) {
            throw new CfdiBuildException(
                'Fiscal certificate is not currently valid.'
            );
        }

        if ($invoice->issued_at === null) {
            throw new CfdiBuildException(
                'Invoice must have an issued_at value before building CFDI XML.'
            );
        }

        $invoice->loadMissing(
            'items.taxes'
        );

        if ($invoice->items->isEmpty()) {
            throw new CfdiBuildException(
                'Invoice does not contain concepts.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Load CSD
        |--------------------------------------------------------------------------
        */

        try {
            $storage =
                Storage::disk(
                    'fiscal_certificates'
                );

            $certificateContents =
                $storage->get(
                    $certificate->certificate_path
                );

            $privateKeyContents =
                $storage->get(
                    $certificate->private_key_path
                );

            $credential =
                Credential::create(
                    $certificateContents,
                    $privateKeyContents,
                    $certificate
                        ->private_key_password
                );
        } catch (Throwable $exception) {
            throw new CfdiBuildException(
                'Unable to open fiscal credential.',
                previous: $exception
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Comprobante
        |--------------------------------------------------------------------------
        */

        $creator =
            $this->creatorFactory->make([
                'Version' =>
                    $invoice->cfdi_version,

                'Serie' =>
                    $invoice->series,

                'Folio' =>
                    (string) $invoice->folio,

                'Fecha' =>
                    $invoice
                        ->issued_at
                        ->format(
                            'Y-m-d\TH:i:s'
                        ),

                'FormaPago' =>
                    $invoice->payment_form,

                'Moneda' =>
                    $invoice->currency,

                'TipoDeComprobante' =>
                    'I',

                'Exportacion' =>
                    $invoice->export_code,

                'MetodoPago' =>
                    $invoice->payment_method,

                'LugarExpedicion' =>
                    $invoice
                        ->issuer_postal_code,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Certificate
        |--------------------------------------------------------------------------
        |
        | false evita que CfdiUtils reemplace los snapshots del emisor.
        |
        */

        $creator->putCertificado(
            new Certificado(
                $credential
                    ->certificate()
                    ->pem()
            ),
            false
        );

        $comprobante =
            $creator->comprobante();

        /*
        |--------------------------------------------------------------------------
        | Emisor
        |--------------------------------------------------------------------------
        */

        $comprobante->addEmisor([
            'Rfc' =>
                $invoice->issuer_rfc,

            'Nombre' =>
                $invoice->issuer_name,

            'RegimenFiscal' =>
                $invoice
                    ->issuer_tax_regime,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Receptor
        |--------------------------------------------------------------------------
        */

        $comprobante->addReceptor([
            'Rfc' =>
                $invoice->receiver_rfc,

            'Nombre' =>
                $invoice->receiver_name,

            'DomicilioFiscalReceptor' =>
                $invoice
                    ->receiver_postal_code,

            'RegimenFiscalReceptor' =>
                $invoice
                    ->receiver_tax_regime,

            'UsoCFDI' =>
                $invoice->cfdi_use,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Conceptos
        |--------------------------------------------------------------------------
        */

        foreach (
            $invoice->items
            as $invoiceItem
        ) {
            $attributes = [
                'ClaveProdServ' =>
                    $invoiceItem
                        ->sat_product_code,

                'Cantidad' =>
                    (string)
                    $invoiceItem->quantity,

                'ClaveUnidad' =>
                    $invoiceItem
                        ->unit_code,

                'Descripcion' =>
                    $invoiceItem
                        ->description,

                'ValorUnitario' =>
                    (string)
                    $invoiceItem
                        ->unit_price,

                'Importe' =>
                    (string)
                    $invoiceItem
                        ->subtotal,

                'ObjetoImp' =>
                    $invoiceItem
                        ->tax_object,
            ];

            if (
                bccomp(
                    (string)
                    $invoiceItem->discount,
                    '0',
                    6
                ) === 1
            ) {
                $attributes['Descuento'] =
                    (string)
                    $invoiceItem->discount;
            }

            $concepto =
                $comprobante->addConcepto(
                    $attributes
                );

            foreach (
                $invoiceItem->taxes
                as $tax
            ) {
                $taxAttributes = [
                    'Base' =>
                        (string)
                        $tax->base,

                    'Impuesto' =>
                        $tax->tax_code,

                    'TipoFactor' =>
                        $tax->factor_type,

                    'TasaOCuota' =>
                        (string)
                        $tax->rate_or_quota,

                    'Importe' =>
                        (string)
                        $tax->amount,
                ];

                if (
                    $tax->tax_type
                    === 'transferred'
                ) {
                    $concepto->addTraslado(
                        $taxAttributes
                    );

                    continue;
                }

                if (
                    $tax->tax_type
                    === 'withheld'
                ) {
                    $concepto->addRetencion(
                        $taxAttributes
                    );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Totals and global taxes
        |--------------------------------------------------------------------------
        |
        | CfdiUtils groups the concept taxes and generates SubTotal,
        | Descuento, Total and Comprobante/Impuestos.
        |
        */

        $creator->addSumasConceptos(
            null,
            2
        );

        $this->assertTotalsMatch(
            $invoice,
            $comprobante
        );

        /*
        |--------------------------------------------------------------------------
        | Cadena original + sello
        |--------------------------------------------------------------------------
        */

        try {
            $originalString =
                $creator
                    ->buildCadenaDeOrigen();

            $creator->addSello(
                $credential
                    ->privateKey()
                    ->pem(),

                $credential
                    ->privateKey()
                    ->passPhrase()
            );

            $creator
                ->moveSatDefinitionsToComprobante();
        } catch (Throwable $exception) {
            throw new CfdiBuildException(
                'Unable to sign CFDI.',
                previous: $exception
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CFDI validation
        |--------------------------------------------------------------------------
        */

        $asserts =
            $creator->validate();

        if ($asserts->hasErrors()) {
            $errors = [];

            foreach (
                $asserts->errors()
                as $error
            ) {
                $errors[] = [
                    'code' =>
                        $error->getCode(),

                    'title' =>
                        $error->getTitle(),

                    'explanation' =>
                        $error
                            ->getExplanation(),
                ];
            }

            throw new CfdiBuildException(
                'Generated CFDI did not pass validation.',
                $errors
            );
        }

        return new CfdiBuildResult(
            xml:
                $creator->asXml(),

            originalString:
                $originalString,

            certificateId:
                $certificate->id,

            certificateNumber:
                $credential
                    ->certificate()
                    ->serialNumber()
                    ->bytes(),
        );
    }

    private function assertTotalsMatch(
        Invoice $invoice,
        mixed $comprobante
    ): void {
        $expectedSubtotal =
            $this->roundCurrency(
                (string) $invoice->subtotal
            );

        $expectedDiscount =
            $this->roundCurrency(
                (string) $invoice->discount
            );

        $expectedTotal =
            $this->roundCurrency(
                (string) $invoice->total
            );

        $cfdiSubtotal =
            (string)
            $comprobante['SubTotal'];

        $cfdiDiscount =
            isset(
                $comprobante['Descuento']
            )
                ? (string)
                    $comprobante[
                        'Descuento'
                    ]
                : '0.00';

        $cfdiTotal =
            (string)
            $comprobante['Total'];

        if (
            bccomp(
                $expectedSubtotal,
                $cfdiSubtotal,
                2
            ) !== 0 ||
            bccomp(
                $expectedDiscount,
                $cfdiDiscount,
                2
            ) !== 0 ||
            bccomp(
                $expectedTotal,
                $cfdiTotal,
                2
            ) !== 0
        ) {
            throw new CfdiBuildException(
                'Invoice totals do not match CFDI calculated totals.',
                [
                    'invoice' => [
                        'subtotal' =>
                            $expectedSubtotal,

                        'discount' =>
                            $expectedDiscount,

                        'total' =>
                            $expectedTotal,
                    ],

                    'cfdi' => [
                        'subtotal' =>
                            $cfdiSubtotal,

                        'discount' =>
                            $cfdiDiscount,

                        'total' =>
                            $cfdiTotal,
                    ],
                ]
            );
        }
    }

    private function roundCurrency(
        string $value
    ): string {
        return bcadd(
            $value,
            '0.005',
            2
        );
    }
}
