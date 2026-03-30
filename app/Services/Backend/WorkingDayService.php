<?php

namespace App\Services\Backend;

use App\Models\Company;
use App\Models\CompanyWorkingDay;
use App\Traits\PaginateQuery;
use Illuminate\Http\Request;

class WorkingDayService
{
    use PaginateQuery;

    public function list(Company $company, Request $request): array
    {
        $query = CompanyWorkingDay::query()
            ->where('company_id', $company->id)
            ->orderBy('day_of_week');

        $search = $request->input('search');
        $query->when(filled($search), fn($q) => $q->where('day_label', 'like', "%{$search}%"));

        return $this->paginateOrFetchAll($query, $request->integer('per_page', 10));
    }

    public function create(Company $company, array $data): CompanyWorkingDay
    {
        $data['company_id'] = $company->id;

        return CompanyWorkingDay::create($data);
    }

    public function update(CompanyWorkingDay $workingDay, array $data): CompanyWorkingDay
    {
        $workingDay->update($data);

        return $workingDay;
    }

    public function delete(CompanyWorkingDay $workingDay): bool
    {
        return $workingDay->delete();
    }

    public function toggle(CompanyWorkingDay $workingDay): bool
    {
        $workingDay->update(['is_working' => !$workingDay->is_working]);

        return $workingDay->is_working;
    }
}
