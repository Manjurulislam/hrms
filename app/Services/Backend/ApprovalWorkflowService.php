<?php

namespace App\Services\Backend;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use App\Traits\PaginateQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalWorkflowService
{
    use PaginateQuery;

    public function list(Request $request): array
    {
        $query = ApprovalWorkflow::query()
            ->with(['company:id,name', 'steps' => fn($q) => $q->orderBy('step_order')])
            ->orderBy('created_at', 'desc');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $perPage = $request->integer('per_page', 50);
        $paginated = $perPage === -1 ? $query->get() : $query->paginate($perPage);

        if ($perPage === -1) {
            return ['data' => $paginated, 'total' => $paginated->count()];
        }

        return $paginated->toArray();
    }

    public function store(array $data): ApprovalWorkflow
    {
        return DB::transaction(function () use ($data) {
            $workflow = ApprovalWorkflow::create([
                'name'       => $data['name'],
                'company_id' => $data['company_id'],
                'is_active'  => $data['is_active'] ?? true,
            ]);

            if (!empty($data['steps'])) {
                foreach ($data['steps'] as $index => $step) {
                    $workflow->steps()->create([
                        'step_order'      => $index + 1,
                        'approver_type'   => $step['approver_type'],
                        'approver_value'  => $step['approver_value'] ?? null,
                        'is_mandatory'    => $step['is_mandatory'] ?? true,
                        'condition_type'  => $step['condition_type'] ?? 'always',
                        'condition_value' => $step['condition_value'] ?? null,
                    ]);
                }
            }

            return $workflow->load('steps');
        });
    }

    public function update(ApprovalWorkflow $workflow, array $data): ApprovalWorkflow
    {
        return DB::transaction(function () use ($workflow, $data) {
            $workflow->update([
                'name'      => $data['name'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Replace all steps
            $workflow->steps()->delete();

            if (!empty($data['steps'])) {
                foreach ($data['steps'] as $index => $step) {
                    $workflow->steps()->create([
                        'step_order'      => $index + 1,
                        'approver_type'   => $step['approver_type'],
                        'approver_value'  => $step['approver_value'] ?? null,
                        'is_mandatory'    => $step['is_mandatory'] ?? true,
                        'condition_type'  => $step['condition_type'] ?? 'always',
                        'condition_value' => $step['condition_value'] ?? null,
                    ]);
                }
            }

            return $workflow->load('steps');
        });
    }

    public function delete(ApprovalWorkflow $workflow): void
    {
        $workflow->delete();
    }

    public function toggle(ApprovalWorkflow $workflow): bool
    {
        $workflow->update(['is_active' => !$workflow->is_active]);

        return $workflow->is_active;
    }
}
