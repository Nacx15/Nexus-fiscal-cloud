<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Fiscal\CreateInvoice;
use App\Domain\Fiscal\Exceptions\CompanyNotFiscalReady;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Tenancy\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly CompanyContext $companyContext
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $invoices =
            Invoice::query()
                ->forCurrentCompany()
                ->latest()
                ->paginate(20);

        return InvoiceResource::collection(
            $invoices
        );
    }

    public function store(
        StoreInvoiceRequest $request,
        CreateInvoice $createInvoice
    ) {
        try {
            $invoice =
                $createInvoice->execute(
                    $request->user(),
                    $request->validated()
                );
        } catch (
            CompanyNotFiscalReady $exception
        ) {
            return new JsonResponse([
                'message' =>
                    'Company is not fiscally ready.',

                'code' =>
                    'company_not_fiscal_ready',

                'checks' =>
                    $exception->checks,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return (
            new InvoiceResource(
                $invoice
            )
        )
            ->response()
            ->setStatusCode(
                Response::HTTP_CREATED
            );
    }

    public function show(
        int $invoice
    ): InvoiceResource {
        $invoiceModel =
            Invoice::query()
                ->forCurrentCompany()
                ->with(
                    'items.taxes'
                )
                ->findOrFail(
                    $invoice
                );

        return new InvoiceResource(
            $invoiceModel
        );
    }
}
