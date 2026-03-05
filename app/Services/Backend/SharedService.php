<?php

namespace App\Services\Backend;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;

class SharedService
{
    public function companies()
    {
        return Company::select('id', 'name')
            ->where('status', true)
            ->orderBy('id')
            ->get();
    }

    public function departments(?int $companyId = null)
    {
        return Department::select('id', 'name', 'company_id')
            ->where('status', true)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get();
    }

    public function designations(?int $excludeId = null, ?int $companyId = null)
    {
        return Designation::select('id', 'title', 'level', 'company_id', 'parent_id')
            ->where('status', true)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->orderBy('level')
            ->orderBy('title')
            ->get();
    }

    public function employees(?int $excludeId = null, ?int $companyId = null)
    {
        return Employee::select('id', 'first_name', 'last_name', 'company_id')
            ->where('status', true)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->orderBy('first_name')
            ->get();
    }
}
