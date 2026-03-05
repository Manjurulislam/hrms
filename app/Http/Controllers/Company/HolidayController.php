<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\HolidayRequest;
use App\Models\Holiday;
use App\Services\Backend\HolidayService;
use App\Traits\CompanyAuth;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class HolidayController extends Controller
{
    use CompanyAuth;

    public function __construct(
        protected readonly HolidayService $service
    ) {}

    public function index(): Response
    {
        return Inertia::render('Company/Holiday/index');
    }

    public function get(Request $request): JsonResponse
    {
        $request->merge(['company_id' => $this->activeCompanyId()]);

        return response()->json($this->service->list($request));
    }

    public function create(): Response
    {
        return Inertia::render('Company/Holiday/create');
    }

    public function store(HolidayRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['company_id'] = $this->activeCompanyId();

            $this->service->create($data);

            return to_route('company.holidays.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create holiday.']);
        }
    }

    public function edit(Holiday $holiday): Response
    {
        return Inertia::render('Company/Holiday/edit', [
            'item' => $holiday,
        ]);
    }

    public function update(HolidayRequest $request, Holiday $holiday): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['company_id'] = $this->activeCompanyId();

            $this->service->update($holiday, $data);

            return to_route('company.holidays.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to update holiday.']);
        }
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        try {
            $this->service->delete($holiday);

            return to_route('company.holidays.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to delete holiday.']);
        }
    }

    public function toggleStatus(Holiday $holiday): JsonResponse
    {
        try {
            $status = $this->service->toggle($holiday);

            return response()->json(['success' => true, 'status' => $status]);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return response()->json(['success' => false], 500);
        }
    }
}
