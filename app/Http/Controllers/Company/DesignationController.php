<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\DesignationRequest;
use App\Models\Designation;
use App\Services\Backend\DesignationService;
use App\Traits\CompanyAuth;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DesignationController extends Controller
{
    use CompanyAuth;

    public function __construct(
        protected readonly DesignationService $service
    ) {}

    public function index(): Response
    {
        return Inertia::render('Company/Designation/index');
    }

    public function get(Request $request): JsonResponse
    {
        $request->merge(['company_id' => $this->activeCompanyId()]);

        return response()->json($this->service->list($request));
    }

    public function create(): Response
    {
        $companyId = $this->activeCompanyId();

        return Inertia::render('Company/Designation/create', [
            'parentDesignations' => $this->service->formData()['parentDesignations'] ?? [],
        ]);
    }

    public function store(DesignationRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['company_id'] = $this->activeCompanyId();

            $this->service->create($data);

            return to_route('company.designations.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create designation.']);
        }
    }

    public function edit(Designation $designation): Response
    {
        $formData = $this->service->formData($designation);

        return Inertia::render('Company/Designation/edit', [
            'item'               => $formData['item'],
            'parentDesignations' => $formData['parentDesignations'] ?? [],
        ]);
    }

    public function update(DesignationRequest $request, Designation $designation): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['company_id'] = $this->activeCompanyId();

            $this->service->update($designation, $data);

            return to_route('company.designations.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to update designation.']);
        }
    }

    public function destroy(Designation $designation): RedirectResponse
    {
        try {
            $this->service->delete($designation);

            return to_route('company.designations.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to delete designation.']);
        }
    }

    public function toggleStatus(Designation $designation): JsonResponse
    {
        try {
            $status = $this->service->toggle($designation);

            return response()->json(['success' => true, 'status' => $status]);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return response()->json(['success' => false], 500);
        }
    }
}
