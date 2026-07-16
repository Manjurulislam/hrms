# Approval Workflow Drag-and-Drop Builder Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the Approval Workflow create/edit forms with an n8n-style vertical draggable node lane (linear chain, no branching), and fix two related backend `update()` bugs.

**Architecture:** Extract one shared `WorkflowBuilder.vue` (plus `WorkflowNode.vue` and `WorkflowNodeDrawer.vue`) so `create.vue`/`edit.vue` become thin wrappers around a single reactive `useForm` state. The data model, payload shape, validation, and runtime approval engine are unchanged; drag order maps to `step_order` exactly as before.

**Tech Stack:** Laravel 11 + Inertia + Vue 3.4 + Vuetify 3.7 + Element Plus, new dependency `vue-draggable-plus` (Vue 3-native SortableJS wrapper).

## Global Constraints

- Form payload shape is fixed: `{ name, company_id, steps: [{ approver_type, approver_value, is_mandatory, condition_type, condition_value }] }`. Array order = execution order = `step_order`.
- `approver_type` ∈ `direct_manager | designation_level | specific_employee | department_head`.
- `condition_type` ∈ `always | days_greater_than | days_less_than`.
- Steps relation FK is `workflow_id` (see `ApprovalWorkflow::steps()`), NOT `approval_workflow_id`.
- New Vue components live under `resources/js/Components/modules/approval-workflow/`.
- Do NOT touch the runtime engine (`LeaveApprovalService`), the step schema, or `ApprovalWorkflowRequest`.
- Commit trailer on every commit: `Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>`.

---

## File Structure

- Modify `app/Services/Backend/ApprovalWorkflowService.php` — fix `update()` (persist `company_id`, stop resetting `is_active`).
- Create `tests/Feature/ApprovalWorkflowUpdateTest.php` — cover the two fixes.
- Modify `package.json` / `package-lock.json` — add `vue-draggable-plus`.
- Create `resources/js/Components/modules/approval-workflow/WorkflowNode.vue` — one node card (presentational).
- Create `resources/js/Components/modules/approval-workflow/WorkflowNodeDrawer.vue` — right-drawer editor for the selected node.
- Create `resources/js/Components/modules/approval-workflow/WorkflowBuilder.vue` — shared builder (top bar, lane, insertion, drawer, overview, quick guide, submit).
- Rewrite `resources/js/Pages/Backend/ApprovalWorkflow/create.vue` — thin wrapper (`post`).
- Rewrite `resources/js/Pages/Backend/ApprovalWorkflow/edit.vue` — thin wrapper (`put`).

---

## Task 1: Backend `update()` fixes (persist company, preserve active state)

**Files:**
- Modify: `app/Services/Backend/ApprovalWorkflowService.php` (method `update`, ~lines 47-60)
- Test: `tests/Feature/ApprovalWorkflowUpdateTest.php`

**Interfaces:**
- Consumes: `ApprovalWorkflowService::update(ApprovalWorkflow $workflow, array $data): ApprovalWorkflow`, `ApprovalWorkflow::create()`, `$workflow->steps()->create()`, `Company::factory()`.
- Produces: corrected `update()` behavior — writes `company_id`, omits `is_active`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ApprovalWorkflowUpdateTest.php`:

```php
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
```

- [ ] **Step 2: Run the test and verify it fails**

Run: `php artisan test --filter=ApprovalWorkflowUpdateTest`
Expected: both tests FAIL — `test_update_persists_company_change` sees company A (company not written), `test_update_does_not_reactivate_inactive_workflow` sees `is_active = true` (reset to true).

- [ ] **Step 3: Fix `update()`**

In `app/Services/Backend/ApprovalWorkflowService.php`, replace the body of `update()`:

```php
    public function update(ApprovalWorkflow $workflow, array $data): ApprovalWorkflow
    {
        return DB::transaction(function () use ($workflow, $data) {
            $workflow->update([
                'name'       => data_get($data, 'name'),
                'company_id' => data_get($data, 'company_id'),
            ]);

            $workflow->steps()->delete();
            $this->syncSteps($workflow, data_get($data, 'steps', []));

            return $workflow->load('steps');
        });
    }
```

(Note: `is_active` is intentionally omitted so the existing value is preserved; `company_id` is now written.)

- [ ] **Step 4: Run the test and verify it passes**

Run: `php artisan test --filter=ApprovalWorkflowUpdateTest`
Expected: PASS (2 tests, 2 assertions each area).

- [ ] **Step 5: Commit**

```bash
git add app/Services/Backend/ApprovalWorkflowService.php tests/Feature/ApprovalWorkflowUpdateTest.php
git commit -m "fix(approval-workflow): persist company and preserve active state on update

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 2: Add the `vue-draggable-plus` dependency

**Files:**
- Modify: `package.json`, `package-lock.json`

**Interfaces:**
- Produces: `import { VueDraggable } from 'vue-draggable-plus'` available to later tasks.

- [ ] **Step 1: Install the package**

Run: `npm install vue-draggable-plus`
Expected: `package.json` gains `vue-draggable-plus` under dependencies; lockfile updates; exit code 0.

- [ ] **Step 2: Verify it resolves in a build**

Run: `npx vite build 2>&1 | tail -5`
Expected: build completes without errors ("built in ..."). (The library isn't imported yet; this just confirms install didn't break the build.)

- [ ] **Step 3: Commit**

```bash
git add package.json package-lock.json
git commit -m "build: add vue-draggable-plus for workflow builder

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 3: Builder components + refactor `create.vue`

Deliverable: the three builder components, wired into `create.vue`, verified by a full build and by driving the create page in the app.

**Files:**
- Create: `resources/js/Components/modules/approval-workflow/WorkflowNode.vue`
- Create: `resources/js/Components/modules/approval-workflow/WorkflowNodeDrawer.vue`
- Create: `resources/js/Components/modules/approval-workflow/WorkflowBuilder.vue`
- Rewrite: `resources/js/Pages/Backend/ApprovalWorkflow/create.vue`

**Interfaces:**
- `WorkflowNode` props: `step:Object, index:Number, total:Number, active:Boolean, hasError:Boolean, approverTypes:Array, designations:Array, employees:Array`; emits `select`, `delete`.
- `WorkflowNodeDrawer` props: `step:Object|null, index:Number, approverTypes:Array, conditionTypes:Array, designations:Array, employees:Array, errors:Object`; emits `close`, `delete`.
- `WorkflowBuilder` props: `name:String, companyId:Number|null, steps:Array, companies:Array, approverTypes:Array, conditionTypes:Array, designations:Array, employees:Array, errors:Object, processing:Boolean, title:String, submitLabel:String`; emits `update:name`, `update:companyId`, `submit`. Mutates `steps` in place (shared reference with the parent `useForm`).

- [ ] **Step 1: Create `WorkflowNode.vue`**

```vue
<script setup>
import {computed} from 'vue';

const props = defineProps({
    step: {type: Object, required: true},
    index: {type: Number, required: true},
    total: {type: Number, required: true},
    active: {type: Boolean, default: false},
    hasError: {type: Boolean, default: false},
    approverTypes: {type: Array, default: () => []},
    designations: {type: Array, default: () => []},
    employees: {type: Array, default: () => []},
});

defineEmits(['select', 'delete']);

const approverTypeIcons = {
    direct_manager: 'mdi-account-tie',
    designation_level: 'mdi-badge-account-horizontal',
    specific_employee: 'mdi-account-check',
    department_head: 'mdi-account-supervisor',
};

const icon = computed(() => approverTypeIcons[props.step.approver_type] || 'mdi-account');

const typeLabel = computed(() => {
    const found = props.approverTypes.find(t => t.value === props.step.approver_type);
    return found ? found.label : props.step.approver_type;
});

const resolvedValue = computed(() => {
    if (props.step.approver_type === 'designation_level') {
        const d = props.designations.find(d => d.id === props.step.approver_value);
        return d ? `${d.title} (Level ${d.level})` : 'Not set';
    }
    if (props.step.approver_type === 'specific_employee') {
        const e = props.employees.find(e => e.id === props.step.approver_value);
        return e ? `${e.first_name} ${e.last_name}` : 'Not set';
    }
    return null;
});

const conditionText = computed(() => {
    if (props.step.condition_type === 'days_greater_than') return `> ${props.step.condition_value ?? '?'} days`;
    if (props.step.condition_type === 'days_less_than') return `< ${props.step.condition_value ?? '?'} days`;
    return 'Always';
});
</script>

<template>
    <v-card
        :color="hasError ? 'error' : undefined"
        :variant="active ? 'tonal' : 'outlined'"
        class="workflow-node"
        @click="$emit('select')"
    >
        <div class="d-flex align-center pa-2 ga-2">
            <v-icon class="drag-handle" color="medium-emphasis" size="18" style="cursor: grab" @click.stop>
                mdi-drag-vertical
            </v-icon>
            <v-avatar size="26" color="primary" variant="tonal">
                <span class="text-caption font-weight-bold">{{ index + 1 }}</span>
            </v-avatar>
            <v-icon size="16" color="medium-emphasis">{{ icon }}</v-icon>
            <div class="flex-grow-1" style="min-width: 0">
                <div class="text-body-2 font-weight-medium">{{ typeLabel }}</div>
                <div v-if="resolvedValue" class="text-caption text-medium-emphasis text-truncate">{{ resolvedValue }}</div>
            </div>
            <v-chip size="x-small" variant="outlined" label>{{ conditionText }}</v-chip>
            <v-chip :color="step.is_mandatory ? 'success' : 'warning'" size="x-small" variant="outlined" label>
                {{ step.is_mandatory ? 'Required' : 'Optional' }}
            </v-chip>
            <v-btn
                :disabled="total <= 1"
                color="error"
                icon="mdi-trash-can-outline"
                size="x-small"
                variant="text"
                @click.stop="$emit('delete')"
            />
        </div>
    </v-card>
</template>
```

- [ ] **Step 2: Create `WorkflowNodeDrawer.vue`**

```vue
<script setup>
import {watch} from 'vue';

const props = defineProps({
    step: {type: Object, default: null},
    index: {type: Number, default: -1},
    approverTypes: {type: Array, default: () => []},
    conditionTypes: {type: Array, default: () => []},
    designations: {type: Array, default: () => []},
    employees: {type: Array, default: () => []},
    errors: {type: Object, default: () => ({})},
});

defineEmits(['close', 'delete']);

const errorFor = (field) => props.errors[`steps.${props.index}.${field}`];

const needsApproverValue = (type) => ['designation_level', 'specific_employee'].includes(type);
const needsConditionValue = (type) => ['days_greater_than', 'days_less_than'].includes(type);

// Reset approver_value whenever the approver type changes (clears stale value and
// switches cleanly between designation <-> employee).
watch(() => props.step?.approver_type, (type, old) => {
    if (!props.step || type === old) return;
    props.step.approver_value = null;
});

watch(() => props.step?.condition_type, (type, old) => {
    if (!props.step || type === old) return;
    if (!needsConditionValue(type)) props.step.condition_value = null;
});
</script>

<template>
    <div v-if="step" class="pa-4">
        <div class="d-flex align-center justify-space-between mb-3">
            <span class="text-subtitle-2 font-weight-bold">Step {{ index + 1 }}</span>
            <v-btn icon="mdi-close" size="small" variant="text" @click="$emit('close')"/>
        </div>

        <v-select
            v-model="step.approver_type"
            :error-messages="errorFor('approver_type')"
            :items="approverTypes"
            class="mb-3"
            density="compact"
            hide-details="auto"
            item-title="label"
            item-value="value"
            label="Approver Type"
            variant="outlined"
        />

        <v-select
            v-if="step.approver_type === 'designation_level'"
            v-model="step.approver_value"
            :error-messages="errorFor('approver_value')"
            :item-title="d => `${d.title} (Level ${d.level})`"
            :items="designations"
            class="mb-3"
            clearable
            density="compact"
            hide-details="auto"
            item-value="id"
            label="Designation"
            variant="outlined"
        />

        <v-autocomplete
            v-else-if="step.approver_type === 'specific_employee'"
            v-model="step.approver_value"
            :error-messages="errorFor('approver_value')"
            :item-title="e => `${e.first_name} ${e.last_name}`"
            :items="employees"
            class="mb-3"
            clearable
            density="compact"
            hide-details="auto"
            item-value="id"
            label="Select Employee"
            variant="outlined"
        />

        <v-select
            v-model="step.condition_type"
            :error-messages="errorFor('condition_type')"
            :items="conditionTypes"
            class="mb-3"
            density="compact"
            hide-details="auto"
            item-title="label"
            item-value="value"
            label="Condition"
            variant="outlined"
        />

        <v-text-field
            v-if="needsConditionValue(step.condition_type)"
            v-model.number="step.condition_value"
            :error-messages="errorFor('condition_value')"
            class="mb-3"
            density="compact"
            hide-details="auto"
            label="Days"
            min="1"
            type="number"
            variant="outlined"
        />

        <div class="d-flex align-center ga-2 mb-4">
            <el-switch
                v-model="step.is_mandatory"
                size="small"
                style="--el-switch-on-color: #13ce66; --el-switch-off-color: #ff4949"
            />
            <span :class="step.is_mandatory ? 'text-success' : 'text-warning'" class="text-body-2">
                {{ step.is_mandatory ? 'Required' : 'Optional' }}
            </span>
        </div>

        <v-btn block color="error" prepend-icon="mdi-trash-can-outline" variant="outlined" @click="$emit('delete')">
            Delete Step
        </v-btn>
    </div>
</template>
```

- [ ] **Step 3: Create `WorkflowBuilder.vue`**

```vue
<script setup>
import {computed, ref} from 'vue';
import {VueDraggable} from 'vue-draggable-plus';
import CardTitle from '@/Components/common/card/CardTitle.vue';
import TextInput from '@/Components/common/form/TextInput.vue';
import WorkflowNode from './WorkflowNode.vue';
import WorkflowNodeDrawer from './WorkflowNodeDrawer.vue';

const props = defineProps({
    name: {type: String, default: ''},
    companyId: {type: [Number, null], default: null},
    steps: {type: Array, required: true},
    companies: {type: Array, default: () => []},
    approverTypes: {type: Array, default: () => []},
    conditionTypes: {type: Array, default: () => []},
    designations: {type: Array, default: () => []},
    employees: {type: Array, default: () => []},
    errors: {type: Object, default: () => ({})},
    processing: {type: Boolean, default: false},
    title: {type: String, default: 'Approval Workflow'},
    submitLabel: {type: String, default: 'Save'},
});

const emit = defineEmits(['update:name', 'update:companyId', 'submit']);

const nameProxy = computed({get: () => props.name, set: v => emit('update:name', v)});
const companyProxy = computed({get: () => props.companyId, set: v => emit('update:companyId', v)});

const stepsModel = computed({
    get: () => props.steps,
    set: (val) => props.steps.splice(0, props.steps.length, ...val),
});

const selectedIndex = ref(null);
const selectedStep = computed(() => selectedIndex.value === null ? null : (props.steps[selectedIndex.value] ?? null));

const makeStep = () => ({
    approver_type: 'direct_manager',
    approver_value: null,
    is_mandatory: true,
    condition_type: 'always',
    condition_value: null,
});

const selectNode = (index) => { selectedIndex.value = index; };
const closeDrawer = () => { selectedIndex.value = null; };

const addStep = () => {
    props.steps.push(makeStep());
    selectedIndex.value = props.steps.length - 1;
};

const insertStep = (index) => {
    props.steps.splice(index, 0, makeStep());
    selectedIndex.value = index;
};

const removeStep = (index) => {
    if (props.steps.length <= 1) return;
    props.steps.splice(index, 1);
    selectedIndex.value = null;
};

const stepHasError = (index) => Object.keys(props.errors).some(k => k.startsWith(`steps.${index}.`));

const requiredSteps = computed(() => props.steps.filter(s => s.is_mandatory).length);
const optionalSteps = computed(() => props.steps.filter(s => !s.is_mandatory).length);
</script>

<template>
    <v-card>
        <CardTitle
            :extra-route="{title: 'Back', route: 'approval-workflows.index', icon: 'mdi-arrow-left-bold'}"
            :title="title"
            icon="mdi-sitemap-outline"
        />
        <form @submit.prevent="$emit('submit')">
            <v-card-text>
                <v-card class="mb-4" variant="outlined">
                    <v-card-text>
                        <v-row dense>
                            <v-col cols="12" md="6">
                                <TextInput
                                    v-model="nameProxy"
                                    :error-messages="errors.name"
                                    label="Name"
                                    placeholder="e.g., Standard Leave Approval"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-select
                                    v-model="companyProxy"
                                    :error-messages="errors.company_id"
                                    :items="companies"
                                    density="compact"
                                    hide-details="auto"
                                    item-title="name"
                                    item-value="id"
                                    label="Company"
                                    variant="outlined"
                                />
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                <v-row>
                    <v-col cols="12" md="8">
                        <div v-if="errors.steps" class="text-error text-body-2 mb-3">{{ errors.steps }}</div>

                        <VueDraggable
                            v-model="stepsModel"
                            :animation="150"
                            class="d-flex flex-column align-center"
                            handle=".drag-handle"
                            @end="closeDrawer"
                        >
                            <div v-for="(step, index) in steps" :key="index" class="workflow-lane-item">
                                <WorkflowNode
                                    :active="selectedIndex === index"
                                    :approver-types="approverTypes"
                                    :designations="designations"
                                    :employees="employees"
                                    :has-error="stepHasError(index)"
                                    :index="index"
                                    :step="step"
                                    :total="steps.length"
                                    @delete="removeStep(index)"
                                    @select="selectNode(index)"
                                />
                                <div class="workflow-connector">
                                    <div class="workflow-line"></div>
                                    <v-btn
                                        v-if="index < steps.length - 1"
                                        class="workflow-insert"
                                        color="primary"
                                        icon="mdi-plus"
                                        size="x-small"
                                        variant="tonal"
                                        @click="insertStep(index + 1)"
                                    />
                                </div>
                            </div>
                        </VueDraggable>

                        <div class="d-flex justify-center">
                            <v-btn
                                class="text-none"
                                color="primary"
                                prepend-icon="mdi-plus"
                                size="small"
                                variant="tonal"
                                @click="addStep"
                            >
                                Add step
                            </v-btn>
                        </div>
                    </v-col>

                    <v-col cols="12" md="4">
                        <v-card class="mb-4" variant="outlined">
                            <v-toolbar class="border-b" color="transparent" density="compact">
                                <v-icon class="ml-4" size="small">mdi-chart-box-outline</v-icon>
                                <v-toolbar-title class="text-body-2 font-weight-bold">Overview</v-toolbar-title>
                            </v-toolbar>
                            <v-list density="compact">
                                <v-list-item>
                                    <v-list-item-title class="text-body-2">Total Steps</v-list-item-title>
                                    <template #append>
                                        <v-chip color="primary" label size="small" variant="tonal">{{ steps.length }}</v-chip>
                                    </template>
                                </v-list-item>
                                <v-list-item>
                                    <v-list-item-title class="text-body-2">Required</v-list-item-title>
                                    <template #append>
                                        <v-chip color="success" label size="small" variant="tonal">{{ requiredSteps }}</v-chip>
                                    </template>
                                </v-list-item>
                                <v-list-item>
                                    <v-list-item-title class="text-body-2">Optional</v-list-item-title>
                                    <template #append>
                                        <v-chip color="warning" label size="small" variant="tonal">{{ optionalSteps }}</v-chip>
                                    </template>
                                </v-list-item>
                            </v-list>
                        </v-card>

                        <v-alert class="text-caption" density="compact" type="info" variant="tonal">
                            <div class="font-weight-bold mb-1">Quick Guide</div>
                            <div><v-icon size="14">mdi-account-tie</v-icon> <strong>Direct Manager</strong> - Employee's immediate boss</div>
                            <div><v-icon size="14">mdi-badge-account-horizontal</v-icon> <strong>Designation Level</strong> - A manager at a specific rank</div>
                            <div><v-icon size="14">mdi-account-check</v-icon> <strong>Specific Employee</strong> - A named person always approves</div>
                            <div><v-icon size="14">mdi-account-supervisor</v-icon> <strong>Department Head</strong> - The top person in the department</div>
                        </v-alert>
                    </v-col>
                </v-row>
            </v-card-text>

            <v-divider/>
            <v-card-actions class="justify-center pa-4">
                <v-btn
                    :loading="processing"
                    class="text-none"
                    color="primary"
                    prepend-icon="mdi-check"
                    type="submit"
                    variant="flat"
                >
                    {{ submitLabel }}
                </v-btn>
            </v-card-actions>
        </form>
    </v-card>

    <v-navigation-drawer
        :model-value="selectedIndex !== null"
        location="right"
        temporary
        width="360"
        @update:model-value="val => { if (!val) closeDrawer(); }"
    >
        <WorkflowNodeDrawer
            :approver-types="approverTypes"
            :condition-types="conditionTypes"
            :designations="designations"
            :employees="employees"
            :errors="errors"
            :index="selectedIndex ?? -1"
            :step="selectedStep"
            @close="closeDrawer"
            @delete="removeStep(selectedIndex)"
        />
    </v-navigation-drawer>
</template>

<style scoped>
.workflow-lane-item {
    width: 100%;
    max-width: 520px;
}

.workflow-connector {
    position: relative;
    height: 26px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.workflow-line {
    width: 2px;
    height: 100%;
    background: rgba(0, 0, 0, 0.12);
}

.workflow-insert {
    position: absolute;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.workflow-connector:hover .workflow-insert {
    opacity: 1;
}
</style>
```

- [ ] **Step 4: Rewrite `create.vue` as a thin wrapper**

Replace the entire contents of `resources/js/Pages/Backend/ApprovalWorkflow/create.vue`:

```vue
<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {useToast} from "vue-toastification";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import WorkflowBuilder from "@/Components/modules/approval-workflow/WorkflowBuilder.vue";

const toast = useToast();

defineProps({
    companies: Array,
    approverTypes: Array,
    conditionTypes: Array,
    designations: Array,
    employees: Array,
});

const form = useForm({
    name: '',
    company_id: null,
    steps: [
        {approver_type: 'direct_manager', approver_value: null, is_mandatory: true, condition_type: 'always', condition_value: null},
    ],
});

const submit = () => {
    form.post(route('approval-workflows.store'), {
        onSuccess: () => toast('Workflow created successfully.'),
        onError: () => toast.error('Something went wrong. Please try again.'),
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Create Approval Workflow"/>
        <WorkflowBuilder
            v-model:company-id="form.company_id"
            v-model:name="form.name"
            :approver-types="approverTypes"
            :companies="companies"
            :condition-types="conditionTypes"
            :designations="designations"
            :employees="employees"
            :errors="form.errors"
            :processing="form.processing"
            :steps="form.steps"
            submit-label="Create Workflow"
            title="Create Approval Workflow"
            @submit="submit"
        />
    </DefaultLayout>
</template>
```

- [ ] **Step 5: Verify the build compiles**

Run: `npx vite build 2>&1 | tail -15`
Expected: build succeeds with no Vue/import errors. If it fails on `vue-draggable-plus` import or a component path, fix and re-run before continuing.

- [ ] **Step 6: Manually verify the create page in the app**

Start the dev server (`npm run dev` in the background) and log in, or use the `run` skill. Navigate to **Approval Workflows → Add New**. Confirm:
- The vertical lane renders with one default node ("Direct Manager · Always · Required").
- Clicking a node opens the right drawer; changing Approver Type to "Designation Level" reveals the Designation select; to "Specific Employee" reveals the employee autocomplete; changing Condition to "days_greater_than" reveals the Days field.
- The ⊕ on a connector (hover) inserts a step; "Add step" appends one; dragging the handle reorders; the trash icon deletes (disabled at one step); Overview counts update.
- If the right-side drawer does not overlay correctly (Vuetify layout quirk), note it — fallback is to swap `<v-navigation-drawer>` for a `<v-dialog>`/right transition; otherwise leave as-is.
- Fill Name + Company, add a couple of steps, Save → redirects to the index and the new workflow shows the correct step count.

- [ ] **Step 7: Commit**

```bash
git add resources/js/Components/modules/approval-workflow/ resources/js/Pages/Backend/ApprovalWorkflow/create.vue
git commit -m "feat(approval-workflow): drag-and-drop step builder on create page

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 4: Refactor `edit.vue` onto the shared builder

**Files:**
- Rewrite: `resources/js/Pages/Backend/ApprovalWorkflow/edit.vue`

**Interfaces:**
- Consumes: `WorkflowBuilder` (Task 3), `props.workflow` (with `id`, `name`, `company_id`, `steps[]`).

- [ ] **Step 1: Rewrite `edit.vue` as a thin wrapper**

Replace the entire contents of `resources/js/Pages/Backend/ApprovalWorkflow/edit.vue`:

```vue
<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {useToast} from "vue-toastification";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import WorkflowBuilder from "@/Components/modules/approval-workflow/WorkflowBuilder.vue";

const toast = useToast();

const props = defineProps({
    workflow: Object,
    companies: Array,
    approverTypes: Array,
    conditionTypes: Array,
    designations: Array,
    employees: Array,
});

const form = useForm({
    name: props.workflow.name,
    company_id: props.workflow.company_id,
    steps: props.workflow.steps.map(s => ({
        approver_type: s.approver_type,
        approver_value: s.approver_value,
        is_mandatory: s.is_mandatory,
        condition_type: s.condition_type,
        condition_value: s.condition_value,
    })),
});

const submit = () => {
    form.put(route('approval-workflows.update', props.workflow.id), {
        onSuccess: () => toast('Workflow updated successfully.'),
        onError: () => toast.error('Something went wrong. Please try again.'),
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Edit Approval Workflow"/>
        <WorkflowBuilder
            v-model:company-id="form.company_id"
            v-model:name="form.name"
            :approver-types="approverTypes"
            :companies="companies"
            :condition-types="conditionTypes"
            :designations="designations"
            :employees="employees"
            :errors="form.errors"
            :processing="form.processing"
            :steps="form.steps"
            submit-label="Update Workflow"
            title="Edit Approval Workflow"
            @submit="submit"
        />
    </DefaultLayout>
</template>
```

- [ ] **Step 2: Verify the build compiles**

Run: `npx vite build 2>&1 | tail -15`
Expected: build succeeds with no errors.

- [ ] **Step 3: Manually verify the edit page**

Open an existing workflow via **Approval Workflows → edit**. Confirm:
- Existing steps render as nodes in the saved order, with their approver types/values, conditions, and required/optional states populated.
- Enums render correctly: a `designation_level` node shows its designation; a `specific_employee` node shows the employee name.
- Reorder + edit + save persists: reopen the workflow and confirm the new order/values stuck (verifies `step_order` mapping).
- **Change the Company and Save**, reopen → the new company is retained (verifies the Task 1 `company_id` fix through the UI).
- Toggle the workflow **inactive** on the index, then edit it and Save → it remains inactive (verifies the Task 1 `is_active` fix through the UI).

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Backend/ApprovalWorkflow/edit.vue
git commit -m "feat(approval-workflow): drag-and-drop step builder on edit page

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Self-Review Notes

- **Spec coverage:** shared builder + thin pages (Task 3/4), vertical draggable lane with `vue-draggable-plus` (Task 2/3), node cards + condition/required chips (Task 3), side drawer editor (Task 3), insert-anywhere + add-at-end (Task 3), Overview + Quick Guide retained (Task 3), backend `company_id` + `is_active` fixes with tests (Task 1), verification via app + build (Task 3/4). All spec sections mapped.
- **Payload unchanged:** create/edit still emit the exact `{name, company_id, steps:[...]}` shape; no change to `ApprovalWorkflowRequest` or `syncSteps`.
- **Type consistency:** `WorkflowBuilder` emits `update:name`/`update:companyId` consumed by `v-model:name`/`v-model:company-id`; `steps` is a shared array mutated in place (never reassigned) so Inertia tracks it; drawer field keys (`approver_type`, `approver_value`, `condition_type`, `condition_value`, `is_mandatory`) match the payload and the `errors['steps.N.field']` keys.
- **Known risk:** `v-navigation-drawer` nesting — Step 6 of Task 3 includes an explicit fallback if the overlay misbehaves.
