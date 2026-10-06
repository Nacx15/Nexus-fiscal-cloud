<?php

namespace App\Http\Middleware;

use App\Tenancy\CompanyContext;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCompany
{
    private const COMPANY_HEADER = 'X-Company-ID';

    public function __construct(
        private readonly CompanyContext $companyContext
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if ($user === null) {
            return new JsonResponse([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $companyId = $request->header(
            self::COMPANY_HEADER
        );

        if (
            $companyId === null ||
            !ctype_digit((string) $companyId) ||
            (int) $companyId <= 0
        ) {
            return new JsonResponse([
                'message' => 'Company context is required.',
                'code' => 'company_context_required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $company = $user
            ->companies()
            ->whereKey((int) $companyId)
            ->where('companies.status', 'active')
            ->wherePivot('status', 'active')
            ->first();

        if ($company === null) {
            return new JsonResponse([
                'message' => 'You do not have access to this company.',
                'code' => 'company_access_denied',
            ], Response::HTTP_FORBIDDEN);
        }

        $this->companyContext->set($company);

        return $next($request);
    }
}
