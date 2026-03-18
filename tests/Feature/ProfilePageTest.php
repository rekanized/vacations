<?php

namespace Tests\Feature;

use App\Livewire\VacationPlanner;
use App\Models\Absence;
use App\Models\AbsenceOption;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_user_can_view_profile_page_with_manager_site_and_request_statuses(): void
    {
        $department = Department::create(['name' => 'Operations']);
        $management = Department::create(['name' => 'Management']);

        $manager = $management->users()->create([
            'name' => 'Maja Manager',
            'location' => 'Stockholm',
        ]);

        $user = $department->users()->create([
            'name' => 'Ella Employee',
            'location' => 'Copenhagen',
            'manager_id' => $manager->id,
            'holiday_country' => 'US',
        ]);

        Absence::create([
            'user_id' => $user->id,
            'type' => 'VAC',
            'reason' => 'Summer trip',
            'status' => Absence::STATUS_APPROVED,
            'request_uuid' => 'approved-request',
            'approved_by' => $manager->id,
            'approved_at' => now(),
            'date' => '2026-03-20',
        ]);

        Absence::create([
            'user_id' => $user->id,
            'type' => 'VAC',
            'reason' => 'Conference',
            'status' => Absence::STATUS_REJECTED,
            'request_uuid' => 'rejected-request',
            'approved_by' => $manager->id,
            'approved_at' => now(),
            'date' => '2026-03-24',
        ]);

        Absence::create([
            'user_id' => $user->id,
            'type' => 'VAC',
            'reason' => 'Long weekend',
            'status' => Absence::STATUS_PENDING,
            'request_uuid' => 'pending-request',
            'date' => '2026-03-28',
        ]);

        $this
            ->withSession(['current_user_id' => $user->id])
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSeeText('Ella Employee')
            ->assertSeeText('Operations')
            ->assertSeeText('Copenhagen')
            ->assertSeeText('Maja Manager')
            ->assertSeeText('United States')
            ->assertSeeText('Approved requests')
            ->assertSeeText('Rejected requests')
            ->assertSeeText('Pending requests')
            ->assertSeeText('Summer trip')
            ->assertSeeText('Conference')
            ->assertSeeText('Long weekend');
    }

    public function test_user_can_update_holiday_country_and_planner_uses_the_new_calendar(): void
    {
        AbsenceOption::create([
            'code' => 'VAC',
            'label' => 'Vacation',
            'color' => '#4ade80',
            'sort_order' => 1,
        ]);

        $department = Department::create(['name' => 'Engineering']);
        $user = $department->users()->create([
            'name' => 'Asta Analyst',
            'location' => 'Stockholm',
            'holiday_country' => 'SE',
        ]);

        $this
            ->withSession(['current_user_id' => $user->id])
            ->patch(route('profile.update'), ['holiday_country' => 'US'])
            ->assertRedirect(route('profile.show'))
            ->assertSessionHas('status', 'Holiday country updated.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'holiday_country' => 'US',
        ]);

        session(['current_user_id' => $user->id]);

        $dates = Livewire::test(VacationPlanner::class)
            ->set('viewDate', '2026-07-01')
            ->instance()
            ->render()
            ->getData()['dates'];

        $holiday = $dates->firstWhere('date', '2026-07-03');

        $this->assertNotNull($holiday);
        $this->assertSame('Independence Day', $holiday['holiday_name']);
        $this->assertTrue($holiday['is_holiday']);
    }
}