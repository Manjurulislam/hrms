<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use App\Services\Backend\CompanyService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function __construct(
        protected readonly CompanyService $service
    ) {}

    public function index(): Response
    {
        return Inertia::render('Backend/Company/index');
    }

    public function get(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request));
    }

    public function create(): Response
    {
        return Inertia::render('Backend/Company/create');
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        try {
            $this->service->create($request->validated());

            return to_route('companies.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create company.']);
        }
    }

    public function edit(Company $company): Response
    {
        return Inertia::render('Backend/Company/edit', $this->service->formData($company));
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        try {
            $this->service->update($company, $request->validated());

            return to_route('companies.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to update company.']);
        }
    }

    public function destroy(Company $company): RedirectResponse
    {
        try {
            $this->service->delete($company);

            return to_route('companies.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to delete company.']);
        }
    }

    public function toggleStatus(Company $company): JsonResponse
    {
        try {
            $status = $this->service->toggle($company);

            return response()->json(['success' => true, 'status' => $status]);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return response()->json(['success' => false], 500);
        }
    }
}
