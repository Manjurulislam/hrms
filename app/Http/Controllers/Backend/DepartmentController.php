<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepartmentRequest;
use App\Models\Company;
use App\Models\Department;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use App\Traits\ToggleStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    use QueryParams, PaginateQuery, ToggleStatus;

    public function index()
    {
        return Inertia::render('Backend/Department/index');
    }

    public function store(DepartmentRequest $request)
    {
        try {
            Department::create($request->validated());
            return to_route('departments.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function create()
    {
        $companies = Company::where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Backend/Department/create', [
            'companies' => $companies
        ]);
    }

    public function get(Request $request)
    {
        $query  = Department::query()
            ->with('company:id,name')
            ->orderBy('name');
        $query  = $this->commonQueryWithoutTrash($query, $request);
        $rows   = $request->get('per_page', 10);
        $result = $this->paginateOrFetchAll($query, $rows);
        return response()->json($result);
    }

    public function edit(Department $department)
    {
        $companies = Company::where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Backend/Department/edit', [
            'item'      => $department->load('company:id,name'),
            'companies' => $companies
        ]);
    }

    public function update(DepartmentRequest $request, Department $department)
    {
        try {
            $department->fill($request->validated())->save();
            return to_route('departments.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function destroy(Department $department)
    {
        try {
            $department->delete();
            return redirect()->back();
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function toggleStatus(Department $department)
    {
        return $this->toggleModelStatus($department);
    }
}
