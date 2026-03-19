<?php

namespace Tests\Feature;

use App\Models\Absence;
use App\Models\AbsenceOption;
use App\Models\AbsenceRequestLog;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PageRenderingEagerLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_pages_render_without_lazy_loading_violations(): void
    {
        AbsenceOption::create([
            'code' => 'S',
            'label' => 'Vacation',
            'color' => '#4ade80',
            'sort_order' => 1,
        ]);

        $department = Department::create(['name' => 'Engineering']);
        $manager = $department->users()->create([
            'name' => 'Maja Manager',
            'location' => 'Stockholm',
            'is_admin' => true,
        ]);
        $employee = $department->users()->create([
            'name' => 'Ella Employee',
            'location' => 'Stockholm',
            'manager_id' => $manager->id,
        ]);

        $absence = Absence::create([
            'user_id' => $employee->id,
            'type' => 'S',
            'reason' => 'Planned leave',
            'status' => Absence::STATUS_PENDING,
            'request_uuid' => '11111111-1111-1111-1111-111111111111',
            'date' => '2026-07-01',
        ]);

        AbsenceRequestLog::create([
            'request_uuid' => $absence->request_uuid,
            'user_id' => $employee->id,
            'actor_id' => $manager->id,
            'action' => AbsenceRequestLog::ACTION_SUBMITTED,
            'absence_type' => 'S',
            'status' => Absence::STATUS_PENDING,
            'date_start' => '2026-07-01',
            'date_end' => '2026-07-01',
            'date_count' => 1,
            'reason' => 'Planned leave',
            'metadata' => ['dates' => ['2026-07-01']],
        ]);

        $this
            ->withSession(['current_user_id' => $employee->id])
            ->get(route('planner'))
            ->assertOk()
            ->assertSeeText('Ella Employee')
            ->assertSeeText('Attester')
            ->assertSeeText('Maja Manager will attest this absence.')
            ->assertSeeText('Add absence')
            ->assertSee('Apply absence', false);

        $this
            ->withSession(['current_user_id' => $manager->id])
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSeeText('Maja Manager');

        $this
            ->withSession(['current_user_id' => $manager->id])
            ->get(route('admin.authentication'))
            ->assertOk()
            ->assertSeeText('Azure authentication');

        $this
            ->withSession(['current_user_id' => $manager->id])
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSeeText('Users and permissions');

        $this
            ->withSession(['current_user_id' => $manager->id])
            ->get(route('admin.logs'))
            ->assertOk()
            ->assertSeeText('Request log');

        $this
            ->withSession(['current_user_id' => $employee->id])
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSeeText('Ella Employee');
    }

    public function test_planner_render_queries_absence_options_once(): void
    {
        AbsenceOption::create([
            'code' => 'S',
            'label' => 'Vacation',
            'color' => '#4ade80',
            'sort_order' => 1,
        ]);

        $department = Department::create(['name' => 'Engineering']);
        $user = $department->users()->create([
            'name' => 'Ella Employee',
            'location' => 'Stockholm',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this
            ->withSession(['current_user_id' => $user->id])
            ->get(route('planner'))
            ->assertOk();

        $absenceOptionQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => preg_match('/\babsence_options\b/i', $query['query']) === 1)
            ->count();

        $this->assertSame(1, $absenceOptionQueries);
    }
}
