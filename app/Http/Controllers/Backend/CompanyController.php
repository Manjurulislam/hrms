<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use App\Traits\ToggleStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CompanyController extends Controller
{
    use QueryParams, PaginateQuery, ToggleStatus;

    public function index()
    {
        return Inertia::render('Backend/Company/index');
    }

    public function get(Request $request)
    {
        $query  = Company::query()->orderBy('name');
        $query  = $this->commonQueryWithoutTrash($query, $request);
        $rows   = $request->get('per_page', 10);
        $result = $this->paginateOrFetchAll($query, $rows);
        return response()->json($result);
    }

    public function store(CompanyRequest $request)
    {
        try {
            Company::create($request->validated());
            return to_route('companies.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function create()
    {
        return Inertia::render('Backend/Company/create');
    }

    public function edit(Company $company)
    {
        return Inertia::render('Backend/Company/edit', [
            'item' => $company
        ]);
    }

    public function update(CompanyRequest $request, Company $company)
    {
        try {
            $company->fill($request->validated())->save();
            return to_route('companies.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function destroy(Company $company)
    {
        try {
            $company->delete();
            return redirect()->back();
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function toggleStatus(Company $company)
    {
        return $this->toggleModelStatus($company);
    }
}
