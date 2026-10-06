<?php

namespace App\Tenancy;

use App\Models\Company;
use LogicException;

final class CompanyContext
{
    private ?Company $company = null;

    public function set(Company $company): void
    {
        if (
            $this->company !== null &&
            !$this->company->is($company)
        ) {
            throw new LogicException(
                'Company context has already been resolved.'
            );
        }

        $this->company = $company;
    }

    public function company(): Company
    {
        if ($this->company === null) {
            throw new LogicException(
                'Company context has not been resolved.'
            );
        }

        return $this->company;
    }

    public function id(): int
    {
        return (int) $this->company()->getKey();
    }

    public function hasCompany(): bool
    {
        return $this->company !== null;
    }
}
