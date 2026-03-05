<?php

namespace App\Traits;

trait CompanyAuth
{
    protected function activeCompanyId(): int
    {
        $user = auth()->user();

        return session('active_company_id', $user->employee->company_id);
    }

    protected function managedCompanyIds(): array
    {
        $user = auth()->user();
        $ids = $user->employee->managedCompanies->pluck('id')->toArray();
        $primaryId = $user->employee->company_id;

        return array_unique(array_merge([$primaryId], $ids));
    }
}
