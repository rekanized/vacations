<?php

namespace Tests\Feature;

use App\Models\Absence;
use App\Models\AbsenceOption;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAbsenceOptionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_a_used_absence_option_and_existing_requests_follow_a_code_change(): void
    {
        $department = Department::create(['name' => 'Engineering']);
        $admin = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm', 'is_admin' => true]);
        $employee = $department->users()->create(['name' => 'Nils Employee', 'location' => 'Gothenburg']);

        $option = AbsenceOption::create([
            'code' => 'WFH',
            'label' => 'Work from home',
            'color' => '#4ade80',
            'sort_order' => 1,
        ]);

        Absence::create([
            'user_id' => $employee->id,
            'type' => 'WFH',
            'date' => '2026-03-17',
            'status' => Absence::STATUS_APPROVED,
        ]);

        $response = $this
            ->withSession(['current_user_id' => $admin->id])
            ->patch(route('admin.absence-options.update', $option), [
                'code' => 'REMOTE',
                'label' => 'Work from anywhere',
                'color' => '#22c55e',
            ]);

        $response
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHas(
                'status',
                'Absence option Work from anywhere was updated. Warning acknowledged: 1 day(s) from 1 people already used this option. Existing days were moved to the new code.'
            );

        $this->assertDatabaseHas('absence_options', [
            'id' => $option->id,
            'code' => 'REMOTE',
            'label' => 'Work from anywhere',
            'color' => '#22c55e',
        ]);

        $this->assertDatabaseHas('absences', [
            'user_id' => $employee->id,
            'type' => 'REMOTE',
        ]);
    }

    public function test_admin_can_delete_a_used_absence_option_after_warning(): void
    {
        $department = Department::create(['name' => 'Engineering']);
        $admin = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm', 'is_admin' => true]);
        $employee = $department->users()->create(['name' => 'Nils Employee', 'location' => 'Gothenburg']);

        $option = AbsenceOption::create([
            'code' => 'SICK',
            'label' => 'Sick day',
            'color' => '#f87171',
            'sort_order' => 1,
        ]);

        Absence::create([
            'user_id' => $employee->id,
            'type' => 'SICK',
            'date' => '2026-03-18',
            'status' => Absence::STATUS_APPROVED,
        ]);

        $response = $this
            ->withSession(['current_user_id' => $admin->id])
            ->delete(route('admin.absence-options.destroy', $option));

        $response
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHas(
                'status',
                'Absence option Sick day was deleted. Warning acknowledged: 1 day(s) from 1 people still reference the deleted code.'
            );

        $this->assertDatabaseMissing('absence_options', [
            'id' => $option->id,
        ]);

        $this->assertDatabaseHas('absences', [
            'user_id' => $employee->id,
            'type' => 'SICK',
        ]);
    }
}
