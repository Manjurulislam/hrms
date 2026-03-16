<?php

namespace Database\Seeders;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use App\Models\Employee;
use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class ApprovalWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // Resolve employee IDs by first name
        $moinur  = Employee::where('first_name', 'Moinur')->first()?->id;
        $mafuz   = Employee::where('first_name', 'Mafuz')->first()?->id;
        $kabir   = Employee::where('first_name', 'Kabir')->first()?->id;
        $manjurul = Employee::where('first_name', 'Manjurul')->first()?->id;
        $hasib   = Employee::where('first_name', 'Hasib')->first()?->id;

        // ---------------------------------------------------------------
        // Workflow 1: Standard (Team Lead → PM → CTO → CEO)
        // Used for: Annual Leave, Unpaid Leave
        // ---------------------------------------------------------------
        $standard = ApprovalWorkflow::create([
            'name'       => 'Standard Approval',
            'company_id' => 1,
            'is_active'  => true,
        ]);

        $this->createSteps($standard->id, [
            ['step_order' => 1, 'approver_type' => 'direct_manager',    'approver_value' => null,   'is_mandatory' => true,  'condition_type' => 'always',            'condition_value' => null],
            ['step_order' => 2, 'approver_type' => 'designation_level', 'approver_value' => 3,      'is_mandatory' => true,  'condition_type' => 'days_greater_than', 'condition_value' => 3],
            ['step_order' => 3, 'approver_type' => 'designation_level', 'approver_value' => 2,      'is_mandatory' => true,  'condition_type' => 'days_greater_than', 'condition_value' => 5],
            ['step_order' => 4, 'approver_type' => 'designation_level', 'approver_value' => 1,      'is_mandatory' => false, 'condition_type' => 'days_greater_than', 'condition_value' => 10],
        ]);

        // ---------------------------------------------------------------
        // Workflow 2: Quick (Team Lead → PM)
        // Used for: Casual Leave, Compensatory Leave
        // ---------------------------------------------------------------
        $quick = ApprovalWorkflow::create([
            'name'       => 'Quick Approval',
            'company_id' => 1,
            'is_active'  => true,
        ]);

        $this->createSteps($quick->id, [
            ['step_order' => 1, 'approver_type' => 'direct_manager',    'approver_value' => null, 'is_mandatory' => true, 'condition_type' => 'always',            'condition_value' => null],
            ['step_order' => 2, 'approver_type' => 'designation_level', 'approver_value' => 3,    'is_mandatory' => true, 'condition_type' => 'days_greater_than', 'condition_value' => 2],
        ]);

        // ---------------------------------------------------------------
        // Workflow 3: Medical (Team Lead → PM → CTO)
        // Used for: Sick Leave, Maternity Leave, Paternity Leave
        // ---------------------------------------------------------------
        $medical = ApprovalWorkflow::create([
            'name'       => 'Medical Approval',
            'company_id' => 1,
            'is_active'  => true,
        ]);

        $this->createSteps($medical->id, [
            ['step_order' => 1, 'approver_type' => 'direct_manager',    'approver_value' => null, 'is_mandatory' => true,  'condition_type' => 'always',            'condition_value' => null],
            ['step_order' => 2, 'approver_type' => 'designation_level', 'approver_value' => 3,    'is_mandatory' => true,  'condition_type' => 'always',            'condition_value' => null],
            ['step_order' => 3, 'approver_type' => 'designation_level', 'approver_value' => 2,    'is_mandatory' => true,  'condition_type' => 'days_greater_than', 'condition_value' => 3],
        ]);

        // ---------------------------------------------------------------
        // Assign workflows to leave types
        // ---------------------------------------------------------------
        $workflowMap = [
            'Annual Leave'       => $standard->id,
            'Casual Leave'       => $quick->id,
            'Sick Leave'         => $medical->id,
            'Maternity Leave'    => $medical->id,
            'Paternity Leave'    => $medical->id,
            'Compensatory Leave' => $quick->id,
            'Unpaid Leave'       => $standard->id,
        ];

        foreach ($workflowMap as $leaveTypeName => $workflowId) {
            LeaveType::where('name', $leaveTypeName)
                ->where('company_id', 1)
                ->update(['approval_workflow_id' => $workflowId]);
        }
    }

    private function createSteps(int $workflowId, array $steps): void
    {
        foreach ($steps as $step) {
            ApprovalWorkflowStep::create(array_merge($step, [
                'workflow_id' => $workflowId,
            ]));
        }
    }
}
