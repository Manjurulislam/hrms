<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovalWorkflowRequest;
use App\Models\ApprovalWorkflow;
use App\Models\Employee;
use App\Enums\ApproverType;
use App\Enums\DesignationLevel;
use App\Enums\StepConditionType;
use App\Services\Backend\ApprovalWorkflowService;
use App\Services\Backend\SharedService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalWorkflowController extends Controller
{
    public function __construct(
        protected readonly ApprovalWorkflowService $service,
        protected readonly SharedService $shared
    ) {}

    public function index(): Response
    {
        $companies = $this->shared->companies();

        return Inertia::render('Backend/ApprovalWorkflow/index', [
            'companies'        => $companies,
            'defaultCompanyId' => $companies->first()?->id,
        ]);
    }

    public function get(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request));
    }

    public function create(): Response
    {
        return Inertia::render('Backend/ApprovalWorkflow/create', $this->formData());
    }

    public function store(ApprovalWorkflowRequest $request): RedirectResponse
    {
        try {
            $this->service->store($request->validated());

            return to_route('approval-workflows.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create workflow.']);
        }
    }

    public function edit(ApprovalWorkflow $approvalWorkflow): Response
    {
        $approvalWorkflow->load('steps');

        return Inertia::render('Backend/ApprovalWorkflow/edit', array_merge(
            $this->formData(),
            ['workflow' => $approvalWorkflow]
        ));
    }

    public function update(ApprovalWorkflowRequest $request, ApprovalWorkflow $approvalWorkflow): RedirectResponse
    {
        try {
            $this->service->update($approvalWorkflow, $request->validated());

            return to_route('approval-workflows.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to update workflow.']);
        }
    }

    public function destroy(ApprovalWorkflow $approvalWorkflow): RedirectResponse
    {
        try {
            $this->service->delete($approvalWorkflow);

            return to_route('approval-workflows.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to delete workflow.']);
        }
    }

    public function toggleStatus(ApprovalWorkflow $approvalWorkflow): JsonResponse
    {
        try {
            $status = $this->service->toggle($approvalWorkflow);

            return response()->json(['success' => true, 'status' => $status]);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return response()->json(['success' => false], 500);
        }
    }

    private function formData(): array
    {
        return [
            'companies'       => $this->shared->companies(),
            'approverTypes'   => collect(ApproverType::cases())->map(fn($t) => [
                'value' => $t->value,
                'label' => ucwords(str_replace('_', ' ', $t->value)),
            ]),
            'conditionTypes'  => collect(StepConditionType::cases())->map(fn($t) => [
                'value' => $t->value,
                'label' => ucwords(str_replace('_', ' ', $t->value)),
            ]),
            'designationLevels' => collect(DesignationLevel::cases())->map(fn($l) => [
                'value' => $l->value,
                'label' => $l->name,
            ]),
            'employees' => Employee::where('status', true)->get(['id', 'first_name', 'last_name']),
        ];
    }
}
