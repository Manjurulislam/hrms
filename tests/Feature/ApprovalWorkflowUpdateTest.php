<?php

namespace Tests\Feature;

use App\Models\ApprovalWorkflow;
use App\Models\Company;
use App\Services\Backend\ApprovalWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalWorkflowUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkflow(int $companyId, bool $active): ApprovalWorkflow
    {
        $workflow = ApprovalWorkflow::create([
            'name'       => 'Standard',
            'company_id' => $companyId,
            'is_active'  => $active,
        ]);

        $workflow->steps()->create([
            'step_order'     => 1,
            'approver_type'  => 'direct_manager',
            'is_mandatory'   => true,
            'condition_type' => 'always',
        ]);

        return $workflow;
    }

    private function payload(int $companyId): array
    {
        return [
            'name'       => 'Renamed',
            'company_id' => $companyId,
            'steps'      => [
                [
                    'approver_type'   => 'direct_manager',
                    'approver_value'  => null,
                    'is_mandatory'    => true,
                    'condition_type'  => 'always',
                    'condition_value' => null,
                ],
            ],
        ];
    }

    public function test_update_persists_company_change(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $workflow = $this->makeWorkflow($companyA->id, true);

        app(ApprovalWorkflowService::class)->update($workflow, $this->payload($companyB->id));

        $this->assertSame($companyB->id, $workflow->fresh()->company_id);
    }

    public function test_update_does_not_reactivate_inactive_workflow(): void
    {
        $company  = Company::factory()->create();
        $workflow = $this->makeWorkflow($company->id, false);

        app(ApprovalWorkflowService::class)->update($workflow, $this->payload($company->id));

        $this->assertFalse($workflow->fresh()->is_active);
    }
}
