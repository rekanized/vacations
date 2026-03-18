<?php

namespace Tests\Feature;

use App\Models\AbsenceRequestLog;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_index_route_is_accessible_for_the_current_session_user(): void
    {
        $department = Department::create(['name' => 'Engineering']);
        $admin = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm']);

        $this
            ->withSession(['current_user_id' => $admin->id])
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSeeText('Asta Admin');
    }

    public function test_admin_logs_route_displays_matching_request_logs(): void
    {
        $department = Department::create(['name' => 'Support']);
        $admin = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm']);
        $employee = $department->users()->create(['name' => 'Elsa Employee', 'location' => 'Gothenburg']);

        AbsenceRequestLog::create([
            'request_uuid' => 'request-1',
            'user_id' => $employee->id,
            'actor_id' => $admin->id,
            'action' => AbsenceRequestLog::ACTION_APPROVED,
            'absence_type' => 'VAC',
            'status' => 'approved',
            'date_start' => '2026-03-17',
            'date_end' => '2026-03-18',
            'date_count' => 2,
            'reason' => 'Family trip',
            'metadata' => ['source' => 'test'],
        ]);

        $this
            ->withSession(['current_user_id' => $admin->id])
            ->get(route('admin.logs', ['search' => 'Family trip', 'action' => AbsenceRequestLog::ACTION_APPROVED]))
            ->assertOk()
            ->assertSeeText('Elsa Employee')
            ->assertSeeText('Family trip');
    }

    public function test_admin_can_impersonate_another_user_via_the_route(): void
    {
        $department = Department::create(['name' => 'Finance']);
        $admin = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm']);
        $target = $department->users()->create(['name' => 'Nils Employee', 'location' => 'Malmö']);

        $this
            ->withSession(['current_user_id' => $admin->id])
            ->post(route('admin.impersonate'), ['user_id' => $target->id])
            ->assertRedirect(route('admin.index'))
            ->assertSessionHas('current_user_id', $target->id)
            ->assertSessionHas('status', 'You are now impersonating Nils Employee.');
    }

    public function test_admin_can_mark_a_user_inactive_via_the_route(): void
    {
        $department = Department::create(['name' => 'Finance']);
        $admin = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm']);
        $target = $department->users()->create(['name' => 'Nils Employee', 'location' => 'Malmö']);

        $this
            ->withSession(['current_user_id' => $admin->id])
            ->patch(route('admin.users.activity', $target))
            ->assertRedirect(route('admin.index'))
            ->assertSessionHas('status', 'Nils Employee was marked inactive.');

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('absence_request_logs', [
            'user_id' => $target->id,
            'actor_id' => $admin->id,
            'action' => AbsenceRequestLog::ACTION_USER_INACTIVATED,
            'status' => 'inactive',
            'reason' => 'User marked inactive from the admin panel.',
        ]);
    }

    public function test_admin_logs_route_displays_user_inactivation_entries(): void
    {
        $department = Department::create(['name' => 'Support']);
        $admin = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm']);
        $employee = $department->users()->create(['name' => 'Elsa Employee', 'location' => 'Gothenburg']);

        AbsenceRequestLog::create([
            'request_uuid' => null,
            'user_id' => $employee->id,
            'actor_id' => $admin->id,
            'action' => AbsenceRequestLog::ACTION_USER_INACTIVATED,
            'absence_type' => null,
            'status' => 'inactive',
            'date_start' => null,
            'date_end' => null,
            'date_count' => 0,
            'reason' => 'User marked inactive from the admin panel.',
            'metadata' => ['source' => 'admin_user_management'],
        ]);

        $this
            ->withSession(['current_user_id' => $admin->id])
            ->get(route('admin.logs', ['action' => AbsenceRequestLog::ACTION_USER_INACTIVATED]))
            ->assertOk()
            ->assertSeeText('User Inactivated')
            ->assertSeeText('Elsa Employee')
            ->assertSeeText('Admin user management');
    }

    public function test_inactive_users_cannot_be_impersonated(): void
    {
        $department = Department::create(['name' => 'Finance']);
        $admin = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm']);
        $inactiveUser = $department->users()->create(['name' => 'Nils Employee', 'location' => 'Malmö', 'is_active' => false]);

        $this
            ->from(route('admin.index'))
            ->withSession(['current_user_id' => $admin->id])
            ->post(route('admin.impersonate'), ['user_id' => $inactiveUser->id])
            ->assertRedirect(route('admin.index'))
            ->assertSessionHasErrors('user_id');
    }

    public function test_inactive_session_user_falls_back_to_the_first_active_user(): void
    {
        $department = Department::create(['name' => 'Finance']);
        $inactiveUser = $department->users()->create(['name' => 'Inactive Ingrid', 'location' => 'Stockholm', 'is_active' => false]);
        $activeUser = $department->users()->create(['name' => 'Active Asta', 'location' => 'Malmö']);

        $this
            ->withSession(['current_user_id' => $inactiveUser->id])
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSessionHas('current_user_id', $activeUser->id)
            ->assertSeeText('Active Asta');
    }

    public function test_last_active_user_cannot_be_marked_inactive(): void
    {
        $department = Department::create(['name' => 'Finance']);
        $admin = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm']);

        $this
            ->from(route('admin.index'))
            ->withSession(['current_user_id' => $admin->id])
            ->patch(route('admin.users.activity', $admin))
            ->assertRedirect(route('admin.index'))
            ->assertSessionHasErrors('user_activity');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_store_a_new_absence_option_via_the_route(): void
    {
        $department = Department::create(['name' => 'Operations']);
        $admin = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm']);

        $this
            ->withSession(['current_user_id' => $admin->id])
            ->post(route('admin.absence-options.store'), [
                'code' => ' wfh ',
                'label' => 'Work from home',
                'color' => '#22c55e',
            ])
            ->assertRedirect(route('admin.index'))
            ->assertSessionHas('status', 'Absence option Work from home was added.');

        $this->assertDatabaseHas('absence_options', [
            'code' => 'WFH',
            'label' => 'Work from home',
            'color' => '#22c55e',
            'sort_order' => 1,
        ]);
    }
}