<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\DesignationRequest;
use App\Models\Designation;
use App\Services\Backend\DesignationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DesignationController extends Controller
{
    public function __construct(
        protected readonly DesignationService $service
    ) {}

    public function index(): Response
    {
        return Inertia::render('Backend/Designation/index', [
            'companies' => $this->service->formData()['companies'],
        ]);
    }

    public function get(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request));
    }

    public function create(): Response
    {
        return Inertia::render('Backend/Designation/create', $this->service->formData());
    }

    public function store(DesignationRequest $request): RedirectResponse
    {
        try {
            $this->service->create($request->validated());

            return to_route('designations.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create designation.']);
        }
    }

    public function edit(Designation $designation): Response
    {
        return Inertia::render('Backend/Designation/edit', $this->service->formData($designation));
    }

    public function update(DesignationRequest $request, Designation $designation): RedirectResponse
    {
        try {
            $this->service->update($designation, $request->validated());

            return to_route('designations.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to update designation.']);
        }
    }

    public function destroy(Designation $designation): RedirectResponse
    {
        try {
            $this->service->delete($designation);

            return to_route('designations.index');
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
