<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveTypeRequest;
use App\Models\Company;
use App\Models\LeaveType;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use App\Traits\ToggleStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class LeaveTypeController extends Controller
{
    use QueryParams, PaginateQuery, ToggleStatus;

    public function index()
    {
        return Inertia::render('Backend/LeaveType/index');
    }

    public function store(LeaveTypeRequest $request)
    {
        try {
            LeaveType::create($request->validated());
            return to_route('leave-types.index');
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

        return Inertia::render('Backend/LeaveType/create', [
            'companies' => $companies
        ]);
    }

    public function get(Request $request)
    {
        $query  = LeaveType::query()
            ->with('company:id,name')
            ->orderBy('name');
        $query  = $this->commonQueryWithoutTrash($query, $request);
        $rows   = $request->get('per_page', 10);
        $result = $this->paginateOrFetchAll($query, $rows);
        return response()->json($result);
    }

    public function edit(LeaveType $leaveType)
    {
        $companies = Company::where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $leaveType->load('company:id,name');

        return Inertia::render('Backend/LeaveType/edit', [
            'item'      => $leaveType,
            'companies' => $companies
        ]);
    }

    public function update(LeaveTypeRequest $request, LeaveType $leaveType)
    {
        try {
            $leaveType->fill($request->validated())->save();
            return to_route('leave-types.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function destroy(LeaveType $leaveType)
    {
        try {
            $leaveType->delete();
            return redirect()->back();
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function toggleStatus(LeaveType $leaveType)
    {
        return $this->toggleModelStatus($leaveType);
    }
}
