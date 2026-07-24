<?php

/**
 * Mixin for CourseController: Status action logic
 * Handles "Save as Draft" and "Save & Publish" actions with readiness validation
 */

namespace App\Http\Controllers\Mixins;

use App\Support\Supabase\Exceptions\SupabaseAuthException;

trait CourseStatusLogic
{
    /**
     * Determine course readiness for publishing.
     * Returns array of checks with {label, met} boolean status.
     *
     * @param  array<string,mixed>  $course
     * @param  array<string,mixed>|null  $version
     * @param  bool  $hasApprovalWorkflow
     * @return array<int,array{label:string,met:bool}>
     */
    private function readinessChecks(array $course, ?array $version, bool $hasApprovalWorkflow): array
    {
        $checks = [];

        // Check 1: Content exists (at least one module)
        $hasContent = $version !== null && $this->hasContentModules((string) $version['id']);
        $checks[] = [
            'label' => 'At least one module and lesson in Content builder',
            'met' => $hasContent,
        ];

        // Check 2: Required metadata fields
        $hasMetadata = ! empty(trim((string) ($course['title'] ?? '')))
            && ! empty(trim((string) ($course['description'] ?? '')))
            && ! empty(trim((string) ($course['aims'] ?? '')));
        $checks[] = [
            'label' => 'Title, description, and aims all filled in',
            'met' => $hasMetadata,
        ];

        // Check 3: Approval workflow (if enabled)
        if ($hasApprovalWorkflow) {
            $checks[] = [
                'label' => 'Course approved by reviewer/approver',
                'met' => false, // Will be set dynamically based on workflow state
            ];
        }

        return $checks;
    }

    /**
     * Check whether a version has any modules (content exists).
     */
    private function hasContentModules(string $versionId): bool
    {
        try {
            $rows = $this->get('/rest/v1/modules', [
                'select' => 'id',
                'course_version_id' => 'eq.'.$versionId,
                'limit' => '1',
            ]);

            return ! empty($rows);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Determine if publish action is allowed and why it might be disabled.
     *
     * @param  array<string,mixed>  $course
     * @param  array<string,mixed>|null  $version
     * @param  bool  $hasApprovalWorkflow
     * @param  array<string,mixed>|null  $workflowCurrent
     * @return array{disabled:bool,reason:string}
     */
    private function publishReadiness(
        array $course,
        ?array $version,
        bool $hasApprovalWorkflow,
        ?array $workflowCurrent
    ): array {
        // No version = cannot publish
        if ($version === null) {
            return ['disabled' => true, 'reason' => 'Create a draft in Content builder first.'];
        }

        // Check metadata
        if (empty(trim((string) ($course['title'] ?? '')))
            || empty(trim((string) ($course['description'] ?? '')))
            || empty(trim((string) ($course['aims'] ?? '')))) {
            return ['disabled' => true, 'reason' => 'Complete title, description, and aims first.'];
        }

        // Check content
        if (! $this->hasContentModules((string) $version['id'])) {
            return ['disabled' => true, 'reason' => 'Add at least one module and lesson in Content builder.'];
        }

        // Approval workflow: must be in approved or published state to publish
        if ($hasApprovalWorkflow) {
            $currentStateKey = $workflowCurrent['state_key'] ?? null;

            // Cannot publish unless state is "approved" or already "published"
            if ($currentStateKey !== 'approved' && $currentStateKey !== 'published') {
                return ['disabled' => true, 'reason' => 'Course must be approved before publishing.'];
            }
        }

        return ['disabled' => false, 'reason' => ''];
    }

    /**
     * Handle "save_draft" action: Update course and version as draft,
     * stay in draft workflow state (if workflow enabled).
     *
     * @throws SupabaseAuthException
     */
    private function actionSaveDraft(
        string $courseId,
        array $validated,
        ?string $actor,
        \App\Support\Supabase\Contracts\WritesCourse $courseWriter,
    ): void {
        // Update course metadata
        $courseWriter->update($courseId, [
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'aims' => $validated['aims'] ?? null,
            'aims_short' => $validated['aims_short'] ?? null,
            'objectives_short' => $validated['objectives_short'] ?? null,
            'slug' => $validated['slug'] ?? null,
            // Do NOT update catalog_status; it's now derived from workflow state or version status
        ]);

        // Status badge will reflect the version status or workflow state
    }

    /**
     * Handle "publish" action: Update course, version, and move through
     * workflow if enabled. If no workflow, publish directly.
     *
     * @throws SupabaseAuthException
     */
    private function actionPublish(
        string $courseId,
        array $validated,
        ?string $actor,
        \App\Support\Supabase\Contracts\WritesCourse $courseWriter,
        \App\Support\Supabase\Contracts\WritesCourseVersion $versionWriter,
        \App\Support\Supabase\Contracts\WritesCourseWorkflow $workflowWriter,
    ): void {
        // Update course metadata
        $courseWriter->update($courseId, [
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'aims' => $validated['aims'] ?? null,
            'aims_short' => $validated['aims_short'] ?? null,
            'objectives_short' => $validated['objectives_short'] ?? null,
            'slug' => $validated['slug'] ?? null,
        ]);

        $version = $this->latestVersion($courseId);
        if ($version === null) {
            throw new SupabaseAuthException('No version to publish.');
        }

        $hasApprovalWorkflow = $this->courseHasApprovalWorkflow($courseId);

        if ($hasApprovalWorkflow) {
            // With workflow: transition from "approved" → "published"
            // (assumed valid by publish readiness check above)
            $currentState = $this->currentWorkflowState((string) $version['id']);
            if ($currentState !== null) {
                $statesById = $this->loadStatesById();
                $transition = $this->findTransition((string) $currentState['state_id'], 'publish');
                if ($transition !== null) {
                    $workflowWriter->transition(
                        (string) $version['id'],
                        (string) $currentState['state_id'],
                        (string) $transition['to_state_id'],
                        'publish',
                        $actor,
                        'Published via course editor.'
                    );

                    // Transition to published state also triggers version publish
                    if (! empty(($statesById[(string) $transition['to_state_id']] ?? [])['is_published'])) {
                        $versionWriter->publish($courseId, (string) $version['id']);
                    }
                }
            }
        } else {
            // No workflow: publish directly
            $versionWriter->publish($courseId, (string) $version['id']);
        }
    }

    /**
     * Load all workflow states indexed by ID.
     *
     * @return array<string,array<string,mixed>>
     */
    private function loadStatesById(): array
    {
        $states = $this->get('/rest/v1/workflow_states', [
            'select' => 'id,key,label,is_initial,is_published,is_terminal,sort',
            'organization_id' => 'is.null',
            'order' => 'sort.asc',
        ]);

        $byId = [];
        foreach ($states as $s) {
            $byId[(string) ($s['id'] ?? '')] = $s;
        }

        return $byId;
    }

    /**
     * Check if course has approval workflow enabled.
     */
    private function courseHasApprovalWorkflow(string $courseId): bool
    {
        try {
            // Check if course_approval_workflow table has a row for this course
            $rows = $this->get('/rest/v1/course_approval_workflows', [
                'select' => 'id',
                'course_id' => 'eq.'.$courseId,
                'limit' => '1',
            ]);

            return ! empty($rows);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get current workflow state for a version.
     *
     * @return array<string,mixed>|null
     */
    private function currentWorkflowState(string $versionId): ?array
    {
        try {
            $rows = $this->get('/rest/v1/course_workflow_state', [
                'select' => 'state_id,entered_by,entered_at',
                'course_version_id' => 'eq.'.$versionId,
            ]);

            return $rows[0] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Find a workflow transition by action.
     *
     * @return array<string,mixed>|null
     */
    private function findTransition(string $fromStateId, string $action): ?array
    {
        try {
            $transitions = $this->get('/rest/v1/workflow_transitions', [
                'select' => 'id,to_state_id,action,requires_distinct_actor',
                'from_state_id' => 'eq.'.$fromStateId,
                'action' => 'eq.'.$action,
                'organization_id' => 'is.null',
            ]);

            return $transitions[0] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Derive the status label from workflow state or version status.
     *
     * @param  array<string,mixed>|null  $course
     * @param  array<string,mixed>|null  $version
     * @param  array<string,mixed>|null  $workflowCurrent
     * @param  bool  $hasApprovalWorkflow
     * @return string
     */
    private function statusLabel(?array $course, ?array $version, ?array $workflowCurrent, bool $hasApprovalWorkflow): string
    {
        if ($version === null) {
            return 'No version';
        }

        if ($hasApprovalWorkflow && $workflowCurrent !== null) {
            // Status is the workflow state label
            $statesById = $this->loadStatesById();
            $state = $statesById[(string) ($workflowCurrent['state_id'] ?? '')] ?? null;
            return $state['label'] ?? 'Unknown state';
        }

        // Without workflow, status is the version status
        $status = (string) ($version['status'] ?? 'draft');

        return match ($status) {
            'published' => 'Published',
            'archived' => 'Archived',
            default => 'Draft',
        };
    }

    /**
     * Derive the CSS class for the status badge.
     *
     * @param  array<string,mixed>|null  $course
     * @param  array<string,mixed>|null  $version
     * @param  array<string,mixed>|null  $workflowCurrent
     * @param  bool  $hasApprovalWorkflow
     * @return string
     */
    private function statusBadgeClass(?array $course, ?array $version, ?array $workflowCurrent, bool $hasApprovalWorkflow): string
    {
        if ($version === null) {
            return 'border border-line text-ink-soft bg-paper';
        }

        if ($hasApprovalWorkflow && $workflowCurrent !== null) {
            // Color by workflow state
            $stateId = (string) ($workflowCurrent['state_id'] ?? '');
            $statesById = $this->loadStatesById();
            $stateKey = ($statesById[$stateId] ?? [])['key'] ?? '';

            return match ($stateKey) {
                'published' => 'bg-rag-green/10 border border-rag-green/40 text-rag-green',
                'approved' => 'bg-teachhq/10 border border-teachhq/40 text-teachhq',
                'in_review' => 'bg-amber-100/50 border border-amber-300/50 text-amber-900',
                'rejected' => 'bg-rag-red/10 border border-rag-red/40 text-rag-red',
                default => 'border border-line text-ink-soft bg-paper',
            };
        }

        // Without workflow, color by version status
        $status = (string) ($version['status'] ?? 'draft');

        return match ($status) {
            'published' => 'bg-rag-green/10 border border-rag-green/40 text-rag-green',
            'archived' => 'bg-line/20 border border-line text-ink-soft',
            default => 'border border-line text-ink-soft bg-paper',
        };
    }
}
