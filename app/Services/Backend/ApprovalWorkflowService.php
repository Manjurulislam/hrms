<?php

namespace App\Services\Backend;

use App\Models\ApprovalWorkflow;
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

        $this->applyFilters($query, $request);

        $perPage = $request->integer('per_page', 50);

        if ($perPage === -1) {
            $items = $query->get();
            return ['data' => $items, 'total' => $items->count()];
        }

        return $query->paginate($perPage)->toArray();
    }

    public function store(array $data): ApprovalWorkflow
    {
        return DB::transaction(function () use ($data) {
            $workflow = ApprovalWorkflow::create([
                'name'       => data_get($data, 'name'),
                'company_id' => data_get($data, 'company_id'),
                'is_active'  => data_get($data, 'is_active', true),
            ]);

            $this->syncSteps($workflow, data_get($data, 'steps', []));

            return $workflow->load('steps');
        });
    }

    public function update(ApprovalWorkflow $workflow, array $data): ApprovalWorkflow
    {
        return DB::transaction(function () use ($workflow, $data) {
            $workflow->update([
                'name'      => data_get($data, 'name'),
                'is_active' => data_get($data, 'is_active', true),
            ]);

            $workflow->steps()->delete();
            $this->syncSteps($workflow, data_get($data, 'steps', []));

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

    // ═══════════════════════════════════════════════════════════════
    // Private Methods
    // ═══════════════════════════════════════════════════════════════

    private function syncSteps(ApprovalWorkflow $workflow, array $steps): void
    {
        foreach ($steps as $index => $step) {
            $workflow->steps()->create([
                'step_order'      => $index + 1,
                'approver_type'   => data_get($step, 'approver_type'),
                'approver_value'  => data_get($step, 'approver_value'),
                'is_mandatory'    => data_get($step, 'is_mandatory', true),
                'condition_type'  => data_get($step, 'condition_type', 'always'),
                'condition_value' => data_get($step, 'condition_value'),
            ]);
        }
    }

    private function applyFilters($query, Request $request): void
    {
        $query->when($request->filled('company_id'), fn($q) => $q->where('company_id', $request->input('company_id')));
        $query->when($request->filled('search'), fn($q) => $q->where('name', 'like', '%' . $request->input('search') . '%'));
    }
}
