<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\HolidayRequest;
use App\Models\Company;
use App\Models\Holiday;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use App\Traits\ToggleStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class HolidayController extends Controller
{
    use QueryParams, PaginateQuery, ToggleStatus;

    public function index()
    {
        return Inertia::render('Backend/Holiday/index');
    }

    public function store(HolidayRequest $request)
    {
        try {
            Holiday::create($request->validated());
            return to_route('holidays.index');
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

        return Inertia::render('Backend/Holiday/create', [
            'companies' => $companies
        ]);
    }

    public function get(Request $request)
    {
        $query  = Holiday::query()
            ->with('company:id,name')
            ->orderBy('day_at', 'desc');
        $query  = $this->commonQueryWithoutTrash($query, $request);
        $rows   = $request->get('per_page', 10);
        $result = $this->paginateOrFetchAll($query, $rows);
        return response()->json($result);
    }

    public function edit(Holiday $holiday)
    {
        $companies = Company::where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $holiday->load('company:id,name');

        return Inertia::render('Backend/Holiday/edit', [
            'item'      => $holiday,
            'companies' => $companies
        ]);
    }

    public function update(HolidayRequest $request, Holiday $holiday)
    {
        try {
            $holiday->fill($request->validated())->save();
            return to_route('holidays.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function destroy(Holiday $holiday)
    {
        try {
            $holiday->delete();
            return redirect()->back();
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }

    public function toggleStatus(Holiday $holiday)
    {
        return $this->toggleModelStatus($holiday);
    }
}
