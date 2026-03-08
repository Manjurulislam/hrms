<?php

namespace App\Services\Backend;

use App\Models\Company;
use App\Services\Utility\CatchIPService;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use Illuminate\Http\Request;

class CompanyService
{
    use PaginateQuery, QueryParams;

    public function __construct(
        protected readonly CatchIPService $ipService,
    ) {}

    public function list(Request $request): array
    {
        $query = Company::query()->orderBy('name');
        $query = $this->companyQuery($query, $request);

        return $this->paginateOrFetchAll($query, $request->integer('per_page', 10));
    }

    public function create(array $data): Company
    {
        $data['office_ip'] = $this->ipService->getPublicIp() ?? $data['office_ip'] ?? null;

        return Company::create($data);
    }

    public function update(Company $company, array $data): Company
    {
        $data['office_ip'] = $this->ipService->getPublicIp() ?? $data['office_ip'] ?? $company->office_ip;

        $company->update($data);

        return $company;
    }

    public function delete(Company $company): bool
    {
        return $company->delete();
    }

    public function toggle(Company $company): bool
    {
        $company->update(['status' => !$company->status]);

        return $company->status;
    }

    public function formData(?Company $company = null): array
    {
        $data = [];

        if ($company) {
            $data['item'] = $company;
        }

        return $data;
    }
}
