<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreFiscalSequenceRequest;
use App\Http\Requests\Api\V1\UpdateFiscalSequenceRequest;
use App\Http\Resources\FiscalSequenceResource;
use App\Models\FiscalSequence;
use App\Tenancy\CompanyContext;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class FiscalSequenceController extends Controller
{
    public function __construct(
        private readonly CompanyContext $companyContext
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $sequences =
            FiscalSequence::query()
                ->forCurrentCompany()
                ->orderBy('document_type')
                ->orderBy('series')
                ->get();

        return FiscalSequenceResource::collection(
            $sequences
        );
    }

    public function store(
        StoreFiscalSequenceRequest $request
    ) {
        $sequence =
            new FiscalSequence();

        $sequence->company_id =
            $this->companyContext->id();

        $sequence->fill(
            $request->validated()
        );

        $sequence->save();

        return (
            new FiscalSequenceResource(
                $sequence
            )
        )
            ->response()
            ->setStatusCode(
                Response::HTTP_CREATED
            );
    }

    public function show(
        int $fiscalSequence
    ): FiscalSequenceResource {
        return new FiscalSequenceResource(
            $this->findOrFail(
                $fiscalSequence
            )
        );
    }

    public function update(
        UpdateFiscalSequenceRequest $request,
        int $fiscalSequence
    ): FiscalSequenceResource {
        $sequence =
            $this->findOrFail(
                $fiscalSequence
            );

        $sequence->fill(
            $request->validated()
        );

        $sequence->save();

        return new FiscalSequenceResource(
            $sequence
        );
    }

    public function destroy(): Response
    {
        abort(
            Response::HTTP_METHOD_NOT_ALLOWED
        );
    }

    private function findOrFail(
        int $sequenceId
    ): FiscalSequence {
        return FiscalSequence::query()
            ->forCurrentCompany()
            ->findOrFail($sequenceId);
    }
}
