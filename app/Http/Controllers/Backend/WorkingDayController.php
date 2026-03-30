<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkingDayRequest;
use App\Models\Company;
use App\Models\CompanyWorkingDay;
use App\Services\Backend\WorkingDayService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class WorkingDayController extends Controller
{
    public function __construct(
        protected readonly WorkingDayService $service,
    ) {}

    public function index(Company $company): Response
    {
        return Inertia::render('Backend/WorkingDay/index', [
            'company' => $company->only('id', 'name'),
        ]);
    }

    public function get(Company $company, Request $request): JsonResponse
    {
        return response()->json($this->service->list($company, $request));
    }

    public function create(Company $company): Response
    {
        return Inertia::render('Backend/WorkingDay/create', [
            'company' => $company->only('id', 'name'),
        ]);
    }

    public function store(Company $company, WorkingDayRequest $request): RedirectResponse
    {
        try {
            $this->service->create($company, $request->validated());

            return to_route('working-days.index', $company);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create working day.']);
        }
    }

    public function edit(Company $company, CompanyWorkingDay $workingDay): Response
    {
        return Inertia::render('Backend/WorkingDay/edit', [
            'company' => $company->only('id', 'name'),
            'item'    => $workingDay,
        ]);
    }

    public function update(Company $company, WorkingDayRequest $request, CompanyWorkingDay $workingDay): RedirectResponse
    {
        try {
            $this->service->update($workingDay, $request->validated());

            return to_route('working-days.index', $company);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to update working day.']);
        }
    }

    public function destroy(Company $company, CompanyWorkingDay $workingDay): RedirectResponse
    {
        try {
            $this->service->delete($workingDay);

            return to_route('working-days.index', $company);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to delete working day.']);
        }
    }

    public function toggleStatus(Company $company, CompanyWorkingDay $workingDay): JsonResponse
    {
        try {
            $status = $this->service->toggle($workingDay);

            return response()->json(['success' => true, 'is_working' => $status]);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return response()->json(['success' => false], 500);
        }
    }
}
