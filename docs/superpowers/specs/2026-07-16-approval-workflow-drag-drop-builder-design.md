# Approval Workflow — Drag-and-Drop Builder (Design)

**Date:** 2026-07-16
**Status:** Approved
**Scope:** Frontend redesign of the Approval Workflow create/edit pages into an n8n-style
draggable node lane, plus two related backend correctness fixes. The underlying data model
and approval engine are unchanged.

## Goal

Replace the current stacked step-cards form on `create.vue` / `edit.vue` with a simplified
n8n-like visual builder: a **vertical, draggable node lane** where each node is one approval
step, edited via a side drawer. The workflow remains a **strictly linear ordered chain**
(no branching) — this is a UI redesign, not an engine change.

## Non-Goals

- No branching/graph model, no parallel paths, no conditional edges (that would be a separate,
  much larger project requiring a runtime-engine rewrite).
- No change to the runtime approval engine (`LeaveApprovalService`), the `ApprovalWorkflowStep`
  schema, the store/update endpoints, or the `ApprovalWorkflowRequest` validation contract.
- No pan/zoom canvas library (Vue Flow was considered and rejected as over-powered for a
  linear chain).

## Decisions (from brainstorming)

| Question | Decision |
|---|---|
| Linear chain vs branching graph | **Linear chain** (visual only) |
| Canvas fidelity / library | **Draggable node-lane** via `vue-draggable-plus` |
| Orientation | **Vertical** (top → bottom) |
| Node editing | **Side drawer** (n8n-style), temporary overlay |
| Page layout | **Keep both** Overview + Quick Guide panels in the right column |
| Adding steps | **Insert anywhere** (⊕ on connectors) **+ Add at end** |
| Backend bugs | **Include both fixes** |

## Architecture

### Component decomposition

Today `create.vue` (~420 lines) and `edit.vue` (~426 lines) are near-identical duplicates.
Extract one shared builder; the pages become thin wrappers.

- **`resources/js/Components/modules/approval-workflow/WorkflowBuilder.vue`** (new, shared)
  - Owns the whole builder UI (lane, insertion, drawer, Overview, Quick Guide, submit bar).
  - Props: `name`, `companyId`, `steps` (bound to the parent `useForm` state), `companies`,
    `approverTypes`, `conditionTypes`, `designations`, `employees`, `errors`, `processing`,
    and a `title` / submit-label for reuse between create and edit.
  - Emits: `submit`, plus `update:name` / `update:companyId` (or a single `update:model`).
    `steps` is a reactive array mutated in place (add/remove/reorder/edit).
- **`WorkflowNode.vue`** — a single node card. Props: `step`, `index`, `total`, `hasError`,
  `active`. Emits: `select`, `delete`. Displays number badge, approver-type icon + label,
  resolved approver value, condition chip, Required/Optional chip, drag handle.
- **`WorkflowNodeDrawer.vue`** — contents of the right-side editor drawer for the selected
  node. Props: `step`, `approverTypes`, `conditionTypes`, `designations`, `employees`,
  `errors`, `index`. Mutates the step reactively; has a Delete action.
- **Overview** and **Quick Guide** remain small inline sections in the right column (may be
  kept inline in `WorkflowBuilder.vue` or split into tiny presentational components).

### Thin pages

- **`create.vue`** — `useForm({ name:'', company_id:null, steps:[<default step>] })`,
  renders `<WorkflowBuilder>`, submit → `form.post(route('approval-workflows.store'))`.
- **`edit.vue`** — `useForm` seeded from `props.workflow` (name, company_id, mapped steps),
  same component, submit → `form.put(route('approval-workflows.update', workflow.id))`.

The default step is unchanged:
`{ approver_type: 'direct_manager', approver_value: null, is_mandatory: true, condition_type: 'always', condition_value: null }`.

## UI / Interaction

### Layout

```
┌─ Name [__________]   Company [__▾] ──────────────────────────┐
│                                                              │
│  ┌───────── lane (left / center) ─────────┐  ┌ right column ┐│
│  │   ① Direct Manager        [⠿ drag]     │  │ Overview      ││
│  │        ⊕  (insert on hover)            │  │  Total    3   ││
│  │   ② Dept Head · >5 days · Optional     │  │  Required 2   ││
│  │        ⊕                               │  │  Optional 1   ││
│  │      + Add step                        │  │ Quick Guide   ││
│  └────────────────────────────────────────┘  │  • Direct Mgr ││
│                                               │  • Level ...  ││
│  [ Create Workflow ]                          └───────────────┘│
└──────────────────────────────────────────────────────────────┘
```

When a node is clicked, a **temporary drawer** slides in from the right edge, overlaying the
right column, showing that node's fields. Closing it restores the Overview / Quick Guide.

### Node lane

- Rendered with `vue-draggable-plus` bound to `steps`. Drag handle on each node reorders the
  array; array order is authoritative and becomes `step_order` on save.
- **Node card** contents:
  - Step number badge (`index + 1`).
  - Approver-type icon (existing `approverTypeIcons` map) + label (from `approverTypes`).
  - Resolved approver value: designation → `"{title} (Level {level})"`; specific employee →
    `"{first} {last}"`; direct manager / department head → none.
  - Condition chip: `Always` / `> {n} days` / `< {n} days`.
  - Required/Optional chip.
  - Error indicator (red border + badge) when any `steps.{index}.*` error exists.
- **Connectors**: a vertical line between nodes; on hover a ⊕ button appears that inserts a
  default step at that position (`steps.splice(index+1, 0, <default step>)`).
- **End**: an "+ Add step" affordance appends a default step.
- **Empty state**: when `steps.length === 0`, show a prompt with an "Add First Step" button
  (parity with current behavior).

### Node drawer (editor)

Fields for the selected step (same semantics as today):
- **Approver Type** select (`approverTypes`).
- **Approver Value** (conditional): designation select when `designation_level`; employee
  autocomplete when `specific_employee`; hidden otherwise. Cleared when the type no longer
  needs it.
- **Condition** select (`conditionTypes`).
- **Days** number field (conditional): shown when `condition_type` is `days_greater_than` /
  `days_less_than`.
- **Required** switch (`is_mandatory`).
- **Delete step** button (disabled when only one step remains).
- Per-field validation messages bound from `errors['steps.' + index + '.' + field]`.

### Validation surfacing

- Submit sends the whole form; Inertia returns `form.errors` keyed like `steps.0.approver_value`.
- A node with any matching error shows the error indicator; opening its drawer shows the
  messages.
- The top-level `steps` error (e.g. "at least one step required") shows above the lane.

## Data Flow

Unchanged contract:

1. Parent `useForm` holds `{ name, company_id, steps: [...] }`.
2. `WorkflowBuilder` mutates that reactive state (add/insert/reorder/edit/delete).
3. Submit posts/puts the payload as-is.
4. `ApprovalWorkflowRequest` validates (unchanged).
5. `ApprovalWorkflowService::store/update` persists; `syncSteps` writes `step_order = index+1`
   from array order (unchanged, delete-and-recreate on update).

## Backend Fixes

In `app/Services/Backend/ApprovalWorkflowService.php`, method `update()`:

1. **Persist company on edit** — include `'company_id' => data_get($data, 'company_id')` in
   the workflow update array (currently only `name` + `is_active` are written).
2. **Stop reactivating on edit** — remove `is_active` from the update array so an inactive
   workflow stays inactive after a save (the form never submits `is_active`, and the current
   `data_get($data, 'is_active', true)` default silently flips it to active). Active state is
   changed only via the list page's toggle-status action.

`store()` is unchanged (create defaulting `is_active` to `true` is correct).

## Testing / Verification

- **Backend feature test** (`tests/Feature`) covering `update()`:
  - Editing a workflow persists a changed `company_id`.
  - Editing an inactive workflow leaves `is_active = false`.
  - (Repo already has model factories per recent commits.)
- **Manual app verification** (drive the real UI):
  - Create: add / insert-mid-chain / drag-reorder / edit-in-drawer / delete / save →
    confirm persisted `step_order` matches on-screen order.
  - Edit: load existing workflow, change company → confirm it persists; confirm an inactive
    workflow remains inactive after save.
  - Conditional fields (designation/employee value, Days) show/hide correctly and clear when
    not applicable.
  - Validation errors mark the right node and appear in its drawer.

## Dependency

- Add **`vue-draggable-plus`** (Vue 3-native, SortableJS-based) to `package.json`. Single new
  runtime dependency; no build-config changes expected.

## Rollout / Risk

- Purely additive on the backend except the two small `update()` fixes; no migration.
- Frontend is isolated to the Approval Workflow module + one shared component; no impact on
  other pages.
- The linear model and approval engine are untouched, so existing workflows keep working.
