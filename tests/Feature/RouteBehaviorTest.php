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
        $admin = $department->users()->create([
            'name' => 'Asta Admin',
            'location' => 'Stockholm',
            'theme_preference' => 'dark',
            'is_admin' => true,
        ]);

        $this
            ->withSession(['current_user_id' => $admin->id])
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSeeText('Asta Admin')
            ->assertSee('data-theme="dark"', false)
            ->assertSeeText('Dark mode on');
    }

    public function test_admin_logs_route_displays_matching_request_logs(): void
    {
        $department = Department::create(['name' => 'Support']);
        $admin = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm', 'is_admin' => true]);
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

    public function test_admin_can_grant_admin_access_to_another_user_via_the_route(): void
    {
        $department = Department::create(['name' => 'Finance']);
        $admin = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm', 'is_admin' => true]);
        $target = $department->users()->create(['name' => 'Nils Employee', 'location' => 'Malmö']);

        $this
            ->withSession(['current_user_id' => $admin->id])
            ->patch(route('admin.users.admin', $target))
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('status', 'Nils Employee can now manage the admin workspace.');

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_admin' => true,
        ]);
    }

    public function test_admin_can_assign_a_manager_to_a_manual_user_via_the_route(): void
    {
        $department = Department::create(['name' => 'Finance']);
        $admin = $department->users()->create([
            'name' => 'Asta Admin',
            'location' => 'Stockholm',
            'email' => 'asta@example.test',
            'password' => 'very-secure-password',
            'is_admin' => true,
        ]);
        $manager = $department->users()->create([
            'name' => 'Maja Manager',
            'location' => 'Gothenburg',
            'azure_oid' => 'azure-manager-oid',
        ]);
        $target = $department->users()->create([
            'name' => 'Nils Employee',
            'location' => 'Malmö',
            'email' => 'nils@example.test',
            'password' => 'another-secure-password',
        ]);

        $this
            ->withSession(['current_user_id' => $admin->id])
            ->patch(route('admin.users.manager', $target), ['manager_id' => $manager->id])
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('status', 'Nils Employee now reports to Maja Manager.');

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'manager_id' => $manager->id,
        ]);
    }

    public function test_admin_cannot_assign_a_manager_to_an_azure_managed_user_from_this_route(): void
    {
        $department = Department::create(['name' => 'Finance']);
        $admin = $department->users()->create([
            'name' => 'Asta Admin',
            'location' => 'Stockholm',
            'email' => 'asta@example.test',
            'password' => 'very-secure-password',
            'is_admin' => true,
        ]);
        $manager = $department->users()->create(['name' => 'Maja Manager', 'location' => 'Gothenburg']);
        $target = $department->users()->create([
            'name' => 'Azure Employee',
            'location' => 'Malmö',
            'azure_oid' => 'azure-employee-oid',
        ]);

        $this
            ->withSession(['current_user_id' => $admin->id])
            ->patch(route('admin.users.manager', $target), ['manager_id' => $manager->id])
            ->assertRedirect(route('admin.users'))
            ->assertSessionHasErrors('user_manager');

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'manager_id' => null,
        ]);
    }

    public function test_admin_can_mark_a_user_inactive_via_the_route(): void
    {
        $department = Department::create(['name' => 'Finance']);
        $admin = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm', 'is_admin' => true]);
        $target = $department->users()->create(['name' => 'Nils Employee', 'location' => 'Malmö']);

        $this
            ->withSession(['current_user_id' => $admin->id])
            ->patch(route('admin.users.activity', $target))
            ->assertRedirect(route('admin.users'))
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

    public function test_admin_who_marks_their_own_account_inactive_is_signed_out_instead_of_becoming_another_user(): void
    {
        $department = Department::create(['name' => 'Finance']);
        $admin = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm', 'is_admin' => true]);
        $otherUser = $department->users()->create(['name' => 'Nils Employee', 'location' => 'Malmö']);

        $this->withSession(['current_user_id' => $admin->id])
            ->patch(route('admin.users.activity', $admin))
            ->assertRedirect(route('home'))
            ->assertSessionHas('status', 'Your account was marked inactive and your session was signed out.')
            ->assertSessionMissing('current_user_id');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_logs_route_displays_user_inactivation_entries(): void
    {
        $department = Department::create(['name' => 'Support']);
        $admin = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm', 'is_admin' => true]);
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

    public function test_last_admin_cannot_be_revoked(): void
    {
        $department = Department::create(['name' => 'Finance']);
        $admin = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm', 'is_admin' => true]);

        $this
            ->from(route('admin.users'))
            ->withSession(['current_user_id' => $admin->id])
            ->patch(route('admin.users.admin', $admin))
            ->assertRedirect(route('admin.users'))
            ->assertSessionHasErrors('user_admin');
    }

    public function test_last_active_user_cannot_be_marked_inactive(): void
    {
        $department = Department::create(['name' => 'Finance']);
        $admin = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm', 'is_admin' => true]);

        $this
            ->from(route('admin.users'))
            ->withSession(['current_user_id' => $admin->id])
            ->patch(route('admin.users.activity', $admin))
            ->assertRedirect(route('admin.users'))
            ->assertSessionHasErrors('user_activity');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_store_a_new_absence_option_via_the_route(): void
    {
        $department = Department::create(['name' => 'Operations']);
        $admin = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm', 'is_admin' => true]);

        $this
            ->withSession(['current_user_id' => $admin->id])
            ->post(route('admin.absence-options.store'), [
                'code' => ' wfh ',
                'label' => 'Work from home',
                'color' => '#22c55e',
            ])
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHas('status', 'Absence option Work from home was added.');

        $this->assertDatabaseHas('absence_options', [
            'code' => 'WFH',
            'label' => 'Work from home',
            'color' => '#22c55e',
            'sort_order' => 1,
        ]);
    }
}