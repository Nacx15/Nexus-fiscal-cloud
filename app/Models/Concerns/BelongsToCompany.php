<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Tenancy\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany(
        Builder $query,
        Company|int $company
    ): Builder {
        $companyId = $company instanceof Company
            ? $company->getKey()
            : $company;

        return $query->where(
            $query->qualifyColumn('company_id'),
            $companyId
        );
    }

    public function scopeForCurrentCompany(
        Builder $query
    ): Builder {
        return $this->scopeForCompany(
            $query,
            app(CompanyContext::class)->id()
        );
    }
}
