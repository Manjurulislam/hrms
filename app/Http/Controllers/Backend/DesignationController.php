<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\DesignationRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use App\Traits\ToggleStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DesignationController extends Controller
{
    use QueryParams, PaginateQuery, ToggleStatus;

    public function index()
    {
        return Inertia::render('Backend/Designation/index');
    }

    public function store(DesignationRequest $request)
    {
        try {
            Designation::create($request->validated());
            return to_route('designations.index');
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

        $departments = Department::where('status', true)
            ->with('company:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'company_id']);

        $parentDesignations = Designation::where('status', true)
            ->orderBy('title')
            ->get(['id', 'title', 'company_id', 'department_id']);

        return Inertia::render('Backend/Designation/create', [
            'companies'          => $companies,
            'departments'        => $departments,
            'parentDesignations' => $parentDesignations
        ]);
    }

    public function get(Request $request)
    {
        $query  = Designation::query()
            ->with([
                'company:id,name',
                'department:id,name',
                'parent:id,title'
            ])
            ->orderBy('title');
        $query  = $this->commonQueryWithoutTrash($query, $request);
        $rows   = $request->get('per_page', 10);
        $result = $this->paginateOrFetchAll($query, $rows);
        return response()->json($result);
    }

    public function edit(Designation $designation)
    {
        $companies = Company::where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $departments = Department::where('status', true)
            ->with('company:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'company_id']);

        $parentDesignations = Designation::where('status', true)
            ->where('id', '!=', $designation->id)
            ->orderBy('title')
            ->get(['id', 'title', 'company_id', 'department_id']);

        $designation->load([
            'company:id,name',
            'department:id,name',
            'parent:id,title'
        ]);

        return Inertia::render('Backend/Designation/edit', [
            'item'               => $designation,
            'companies'          => $companies,
            'departments'        => $departments,
            'parentDesignations' => $parentDesignations
        ]);
    }

    public function update(DesignationRequest $request, Designation $designation)
    {
        try {
            $designation->fill($request->validated())->save();
            return to_route('designations.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function destroy(Designation $designation)
    {
        try {
            $designation->delete();
            return redirect()->back();
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function toggleStatus(Designation $designation)
    {
        return $this->toggleModelStatus($designation);
    }
}
