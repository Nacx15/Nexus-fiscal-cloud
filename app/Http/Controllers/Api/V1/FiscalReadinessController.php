<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Fiscal\CheckFiscalReadiness;
use App\Http\Controllers\Controller;
use App\Tenancy\CompanyContext;
use Illuminate\Http\JsonResponse;

class FiscalReadinessController extends Controller
{
    public function __construct(
        private readonly CompanyContext $companyContext
    ) {
    }

    public function show(
        CheckFiscalReadiness $checker
    ): JsonResponse {
        $result =
            $checker->execute(
                $this->companyContext->company()
            );

        return response()->json([
            'data' => [
                'ready' =>
                    $result->ready,

                'checks' =>
                    $result->checks,
            ],
        ]);
    }
}
