<?php

namespace Tests\Feature;

use App\Livewire\VacationPlanner;
use App\Models\Absence;
use App\Models\AbsenceOption;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_route_shows_the_landing_page_when_users_exist_but_no_one_is_signed_in(): void
    {
        $department = Department::create(['name' => 'Engineering']);
        $department->users()->create([
            'name' => 'Asta First',
            'email' => 'asta@example.test',
            'password' => 'manual-password',
            'location' => 'Stockholm',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSessionMissing('current_user_id');
        $response->assertSeeText('Manual sign-in');
    }

    public function test_absence_request_is_pending_when_the_user_has_a_manager(): void
    {
        AbsenceOption::create(['code' => 'S', 'label' => 'Vacation', 'color' => '#4ade80', 'sort_order' => 1]);

        $department = Department::create(['name' => 'Operations']);
        $manager = $department->users()->create(['name' => 'Maja Manager', 'location' => 'Stockholm']);
        $employee = $department->users()->create(['name' => 'Emil Employee', 'location' => 'Stockholm', 'manager_id' => $manager->id]);

        session(['current_user_id' => $employee->id]);

        $component = Livewire::test(VacationPlanner::class)
            ->call('applyAbsence', $employee->id, ['2026-07-01', '2026-07-02'], 'S', 'Summer trip');

        $absence = Absence::query()->firstOrFail();

        $this->assertSame(Absence::STATUS_PENDING, $absence->status);
        $this->assertSame($employee->id, $absence->user_id);
        $this->assertNotNull($absence->request_uuid);

        $pendingRequests = $component->instance()->render()->getData()['pendingRequests'];
        $this->assertCount(1, $pendingRequests);
        $this->assertSame('Maja Manager', $pendingRequests->first()['attester_name']);

        $component->assertSeeText('Waiting for Maja Manager');

        session(['current_user_id' => $manager->id]);

        $managerComponent = Livewire::test(VacationPlanner::class);

        $managerApprovals = $managerComponent->instance()->render()->getData()['managerApprovals'];
        $this->assertCount(1, $managerApprovals);

        $managerComponent->call('approveRequest', $absence->request_uuid);

        $absence->refresh();

        $this->assertSame(Absence::STATUS_APPROVED, $absence->status);
        $this->assertSame($manager->id, $absence->approved_by);
        $this->assertNotNull($absence->approved_at);
        $this->assertNull($absence->decision_reason);
    }

    public function test_rejecting_a_request_requires_and_stores_a_manager_reason(): void
    {
        AbsenceOption::create(['code' => 'S', 'label' => 'Vacation', 'color' => '#4ade80', 'sort_order' => 1]);

        $department = Department::create(['name' => 'Operations']);
        $manager = $department->users()->create(['name' => 'Maja Manager', 'location' => 'Stockholm']);
        $employee = $department->users()->create(['name' => 'Emil Employee', 'location' => 'Stockholm', 'manager_id' => $manager->id]);

        session(['current_user_id' => $employee->id]);

        Livewire::test(VacationPlanner::class)
            ->call('applyAbsence', $employee->id, ['2026-07-01', '2026-07-02'], 'S', 'Summer trip');

        $absence = Absence::query()->firstOrFail();

        session(['current_user_id' => $manager->id]);

        Livewire::test(VacationPlanner::class)
            ->call('rejectRequest', $absence->request_uuid)
            ->assertHasErrors([
                'managerDecisionReasons.' . $absence->request_uuid,
            ]);

        $absence->refresh();

        $this->assertSame(Absence::STATUS_PENDING, $absence->status);
        $this->assertNull($absence->decision_reason);

        Livewire::test(VacationPlanner::class)
            ->set('managerDecisionReasons.' . $absence->request_uuid, 'Project deadline conflicts with this period.')
            ->call('rejectRequest', $absence->request_uuid)
            ->assertHasNoErrors();

        $absence->refresh();

        $this->assertSame(Absence::STATUS_REJECTED, $absence->status);
        $this->assertSame($manager->id, $absence->approved_by);
        $this->assertSame('Project deadline conflicts with this period.', $absence->decision_reason);
    }

    public function test_absence_request_is_auto_approved_when_the_user_has_no_manager(): void
    {
        AbsenceOption::create(['code' => 'S', 'label' => 'Vacation', 'color' => '#4ade80', 'sort_order' => 1]);

        $department = Department::create(['name' => 'Sales']);
        $user = $department->users()->create(['name' => 'Solo User', 'location' => 'Malmö']);

        session(['current_user_id' => $user->id]);

        Livewire::test(VacationPlanner::class)
            ->call('applyAbsence', $user->id, ['2026-08-04'], 'S', 'One day off');

        $absence = Absence::query()->firstOrFail();

        $this->assertSame(Absence::STATUS_APPROVED, $absence->status);
        $this->assertSame($user->id, $absence->approved_by);
    }

    public function test_existing_absences_cannot_be_overwritten_by_submitting_a_new_request(): void
    {
        AbsenceOption::create(['code' => 'S', 'label' => 'Vacation', 'color' => '#4ade80', 'sort_order' => 1]);
        AbsenceOption::create(['code' => 'B', 'label' => 'Parental leave', 'color' => '#facc15', 'sort_order' => 2]);

        $department = Department::create(['name' => 'Sales']);
        $user = $department->users()->create(['name' => 'Solo User', 'location' => 'Malmö']);

        Absence::create([
            'user_id' => $user->id,
            'type' => 'S',
            'reason' => 'Approved vacation',
            'status' => Absence::STATUS_APPROVED,
            'request_uuid' => 'approved-request',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'date' => '2026-08-04',
        ]);

        session(['current_user_id' => $user->id]);

        Livewire::test(VacationPlanner::class)
            ->call('applyAbsence', $user->id, ['2026-08-04'], 'B', 'Replacement request');

        $this->assertDatabaseCount('absences', 1);
        $this->assertDatabaseHas('absences', [
            'user_id' => $user->id,
            'date' => '2026-08-04',
            'type' => 'S',
            'status' => Absence::STATUS_APPROVED,
            'request_uuid' => 'approved-request',
        ]);
    }

    public function test_approved_absence_cannot_be_deleted_through_the_grid_action(): void
    {
        AbsenceOption::create(['code' => 'S', 'label' => 'Vacation', 'color' => '#4ade80', 'sort_order' => 1]);

        $department = Department::create(['name' => 'Sales']);
        $user = $department->users()->create(['name' => 'Solo User', 'location' => 'Malmö']);

        Absence::create([
            'user_id' => $user->id,
            'type' => 'S',
            'reason' => 'Approved vacation',
            'status' => Absence::STATUS_APPROVED,
            'request_uuid' => 'approved-request',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'date' => '2026-08-04',
        ]);

        session(['current_user_id' => $user->id]);

        Livewire::test(VacationPlanner::class)
            ->call('removeAbsence', $user->id, ['2026-08-04']);

        $this->assertDatabaseHas('absences', [
            'user_id' => $user->id,
            'date' => '2026-08-04',
            'status' => Absence::STATUS_APPROVED,
        ]);
    }

    public function test_pending_absence_request_can_be_updated_by_the_request_owner(): void
    {
        AbsenceOption::create(['code' => 'S', 'label' => 'Vacation', 'color' => '#4ade80', 'sort_order' => 1]);
        AbsenceOption::create(['code' => 'B', 'label' => 'Parental leave', 'color' => '#facc15', 'sort_order' => 2]);

        $department = Department::create(['name' => 'Support']);
        $manager = $department->users()->create(['name' => 'Maja Manager', 'location' => 'Stockholm']);
        $employee = $department->users()->create(['name' => 'Ella Employee', 'location' => 'Stockholm', 'manager_id' => $manager->id]);

        session(['current_user_id' => $employee->id]);

        Livewire::test(VacationPlanner::class)
            ->call('applyAbsence', $employee->id, ['2026-09-01', '2026-09-02'], 'S', 'Initial request');

        $requestUuid = Absence::query()->value('request_uuid');

        $component = Livewire::test(VacationPlanner::class);
        $this->assertStringContainsString(
            sprintf('wire:target="startEditingRequest(&#039;%s&#039;)"', $requestUuid),
            $component->html()
        );

        $component
            ->call('startEditingRequest', $requestUuid)
            ->assertSee('Edit pending request')
            ->set('editingRequestStartDate', '2026-09-03')
            ->set('editingRequestEndDate', '2026-09-05')
            ->set('editingRequestType', 'B')
            ->set('editingRequestReason', 'Updated request')
            ->call('updatePendingRequest')
            ->assertSet('editingRequestUuid', null);

        $updatedAbsences = Absence::query()->orderBy('date')->get();

        $this->assertCount(3, $updatedAbsences);
        $this->assertSame(['2026-09-03', '2026-09-04', '2026-09-05'], $updatedAbsences->pluck('date')->all());
        $this->assertSame(['B'], $updatedAbsences->pluck('type')->unique()->values()->all());
        $this->assertSame(['Updated request'], $updatedAbsences->pluck('reason')->unique()->values()->all());
        $this->assertSame([$requestUuid], $updatedAbsences->pluck('request_uuid')->unique()->values()->all());
        $this->assertSame([Absence::STATUS_PENDING], $updatedAbsences->pluck('status')->unique()->values()->all());
    }

    public function test_pending_absence_request_can_be_deleted_by_the_request_owner(): void
    {
        AbsenceOption::create(['code' => 'S', 'label' => 'Vacation', 'color' => '#4ade80', 'sort_order' => 1]);

        $department = Department::create(['name' => 'Finance']);
        $manager = $department->users()->create(['name' => 'Maja Manager', 'location' => 'Stockholm']);
        $employee = $department->users()->create(['name' => 'Dana Employee', 'location' => 'Stockholm', 'manager_id' => $manager->id]);

        session(['current_user_id' => $employee->id]);

        Livewire::test(VacationPlanner::class)
            ->call('applyAbsence', $employee->id, ['2026-10-11', '2026-10-12'], 'S', 'Needs review');

        $requestUuid = Absence::query()->value('request_uuid');

        Livewire::test(VacationPlanner::class)
            ->call('deletePendingRequest', $requestUuid);

        $this->assertDatabaseCount('absences', 0);
    }

    public function test_pending_absence_request_can_be_deleted_from_the_edit_modal(): void
    {
        AbsenceOption::create(['code' => 'S', 'label' => 'Vacation', 'color' => '#4ade80', 'sort_order' => 1]);

        $department = Department::create(['name' => 'Legal']);
        $manager = $department->users()->create(['name' => 'Maja Manager', 'location' => 'Stockholm']);
        $employee = $department->users()->create(['name' => 'Dora Employee', 'location' => 'Stockholm', 'manager_id' => $manager->id]);

        session(['current_user_id' => $employee->id]);

        Livewire::test(VacationPlanner::class)
            ->call('applyAbsence', $employee->id, ['2026-11-03', '2026-11-04'], 'S', 'Edit then delete');

        $requestUuid = Absence::query()->value('request_uuid');

        Livewire::test(VacationPlanner::class)
            ->call('startEditingRequest', $requestUuid)
            ->assertSet('editingRequestUuid', $requestUuid)
            ->call('deleteEditingRequest')
            ->assertSet('editingRequestUuid', null);

        $this->assertDatabaseCount('absences', 0);
    }
}
