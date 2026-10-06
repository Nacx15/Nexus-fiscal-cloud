<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCompanyFiscalProfileRequest;
use App\Http\Requests\Api\V1\UpdateCompanyFiscalProfileRequest;
use App\Http\Resources\CompanyFiscalProfileResource;
use App\Models\CompanyFiscalProfile;
use App\Tenancy\CompanyContext;
use Illuminate\Http\Response;

class CompanyFiscalProfileController extends Controller
{
    public function __construct(
        private readonly CompanyContext $companyContext
    ) {
    }

    public function show(): CompanyFiscalProfileResource
    {
        $profile = CompanyFiscalProfile::query()
            ->forCurrentCompany()
            ->firstOrFail();

        return new CompanyFiscalProfileResource(
            $profile
        );
    }

    public function store(
        StoreCompanyFiscalProfileRequest $request
    ) {
        $existingProfile =
            CompanyFiscalProfile::query()
                ->forCurrentCompany()
                ->exists();

        if ($existingProfile) {
            return response()->json([
                'message' =>
                    'Fiscal profile already exists.',
                'code' =>
                    'fiscal_profile_already_exists',
            ], Response::HTTP_CONFLICT);
        }

        $profile =
            new CompanyFiscalProfile();

        $profile->company_id =
            $this->companyContext->id();

        $profile->fill(
            $request->validated()
        );

        $profile->save();

        return (
            new CompanyFiscalProfileResource(
                $profile
            )
        )
            ->response()
            ->setStatusCode(
                Response::HTTP_CREATED
            );
    }

    public function update(
        UpdateCompanyFiscalProfileRequest $request
    ): CompanyFiscalProfileResource {
        $profile =
            CompanyFiscalProfile::query()
                ->forCurrentCompany()
                ->firstOrFail();

        $profile->fill(
            $request->validated()
        );

        $profile->save();

        return new CompanyFiscalProfileResource(
            $profile
        );
    }
}
