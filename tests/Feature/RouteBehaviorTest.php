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