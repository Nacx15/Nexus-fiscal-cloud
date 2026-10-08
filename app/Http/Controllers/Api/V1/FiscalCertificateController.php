<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Fiscal\RegisterFiscalCertificate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreFiscalCertificateRequest;
use App\Http\Resources\FiscalCertificateResource;
use App\Models\FiscalCertificate;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class FiscalCertificateController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $certificates =
            FiscalCertificate::query()
                ->forCurrentCompany()
                ->latest()
                ->get();

        return FiscalCertificateResource::collection(
            $certificates
        );
    }

    public function store(
        StoreFiscalCertificateRequest $request,
        RegisterFiscalCertificate $register
    ) {
        $certificate =
            $register->execute(
                $request->file(
                    'certificate_file'
                ),
                $request->file(
                    'private_key_file'
                ),
                $request->string(
                    'private_key_password'
                )->toString()
            );

        return (
            new FiscalCertificateResource(
                $certificate
            )
        )
            ->response()
            ->setStatusCode(
                Response::HTTP_CREATED
            );
    }

    public function show(
        int $fiscalCertificate
    ): FiscalCertificateResource {
        $certificate =
            FiscalCertificate::query()
                ->forCurrentCompany()
                ->findOrFail(
                    $fiscalCertificate
                );

        return new FiscalCertificateResource(
            $certificate
        );
    }
}
