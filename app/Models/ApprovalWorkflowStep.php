<?php

namespace App\Models;

use App\Enums\ApproverType;
use App\Enums\StepConditionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalWorkflowStep extends Model
{
    protected $fillable = [
        'workflow_id',
        'step_order',
        'approver_type',
        'approver_value',
        'is_mandatory',
        'condition_type',
        'condition_value',
    ];

    protected $casts = [
        'step_order'     => 'integer',
        'approver_type'  => ApproverType::class,
        'is_mandatory'   => 'boolean',
        'condition_type' => StepConditionType::class,
        'condition_value' => 'integer',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }
}
