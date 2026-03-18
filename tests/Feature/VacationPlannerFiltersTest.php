<?php

namespace Tests\Feature;

use App\Livewire\VacationPlanner;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VacationPlannerFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_filter_supports_multiple_selected_departments(): void
    {
        $operations = Department::create(['name' => 'Operations']);
        $engineering = Department::create(['name' => 'Engineering']);
        $sales = Department::create(['name' => 'Sales']);

        $operations->users()->create(['name' => 'Olivia Ops', 'location' => 'Stockholm']);
        $engineering->users()->create(['name' => 'Elias Eng', 'location' => 'Göteborg']);
        $sales->users()->create(['name' => 'Sara Sales', 'location' => 'Malmö']);

        $component = Livewire::test(VacationPlanner::class)
            ->set('viewDate', '2026-03-01')
            ->set('selectedDepartments', ['Operations', 'Sales']);

        $departments = $component->instance()->render()->getData()['departments'];

        $this->assertSame(['Operations', 'Sales'], $departments->pluck('name')->all());
    }

    public function test_site_filter_supports_multiple_selected_sites_and_limits_users(): void
    {
        $operations = Department::create(['name' => 'Operations']);
        $engineering = Department::create(['name' => 'Engineering']);

        $operations->users()->create(['name' => 'Olivia Ops', 'location' => 'Stockholm']);
        $operations->users()->create(['name' => 'Mark Ops', 'location' => 'Umeå']);
        $engineering->users()->create(['name' => 'Elias Eng', 'location' => 'Göteborg']);
        $engineering->users()->create(['name' => 'Nora Eng', 'location' => 'Lund']);

        $component = Livewire::test(VacationPlanner::class)
            ->set('viewDate', '2026-03-01')
            ->set('selectedSites', ['Stockholm', 'Lund']);

        $departments = $component->instance()->render()->getData()['departments'];

        $this->assertSame(['Engineering', 'Operations'], $departments->pluck('name')->all());
        $this->assertSame(['Nora Eng'], $departments->firstWhere('name', 'Engineering')->users->pluck('name')->all());
        $this->assertSame(['Olivia Ops'], $departments->firstWhere('name', 'Operations')->users->pluck('name')->all());
    }

    public function test_manager_filter_supports_multiple_selected_managers_and_limits_users(): void
    {
        $operations = Department::create(['name' => 'Operations']);
        $engineering = Department::create(['name' => 'Engineering']);
        $management = Department::create(['name' => 'Management']);

        $managerOne = User::create(['department_id' => $management->id, 'name' => 'Mira Manager', 'location' => 'Stockholm']);
        $managerTwo = User::create(['department_id' => $management->id, 'name' => 'Noah Lead', 'location' => 'Göteborg']);
        $managerThree = User::create(['department_id' => $management->id, 'name' => 'Pia Director', 'location' => 'Malmö']);

        $operations->users()->create(['name' => 'Olivia Ops', 'location' => 'Stockholm', 'manager_id' => $managerOne->id]);
        $operations->users()->create(['name' => 'Mark Ops', 'location' => 'Umeå', 'manager_id' => $managerThree->id]);
        $engineering->users()->create(['name' => 'Elias Eng', 'location' => 'Göteborg', 'manager_id' => $managerTwo->id]);
        $engineering->users()->create(['name' => 'Nora Eng', 'location' => 'Lund', 'manager_id' => $managerThree->id]);

        $component = Livewire::test(VacationPlanner::class)
            ->set('viewDate', '2026-03-01')
            ->set('selectedManagers', [(string) $managerOne->id, (string) $managerTwo->id]);

        $departments = $component->instance()->render()->getData()['departments'];

        $this->assertSame(['Engineering', 'Operations'], $departments->pluck('name')->all());
        $this->assertSame(['Elias Eng'], $departments->firstWhere('name', 'Engineering')->users->pluck('name')->all());
        $this->assertSame(['Olivia Ops'], $departments->firstWhere('name', 'Operations')->users->pluck('name')->all());
    }

    public function test_inactive_users_are_excluded_from_planner_departments_and_filters(): void
    {
        $operations = Department::create(['name' => 'Operations']);
        $management = Department::create(['name' => 'Management']);

        $activeManager = User::create(['department_id' => $management->id, 'name' => 'Mira Manager', 'location' => 'Stockholm']);
        $inactiveManager = User::create(['department_id' => $management->id, 'name' => 'Nora Manager', 'location' => 'Malmö', 'is_active' => false]);

        $operations->users()->create(['name' => 'Olivia Ops', 'location' => 'Stockholm', 'manager_id' => $activeManager->id]);
        $operations->users()->create(['name' => 'Ivan Inactive', 'location' => 'Umeå', 'manager_id' => $inactiveManager->id, 'is_active' => false]);

        $component = Livewire::test(VacationPlanner::class)
            ->set('viewDate', '2026-03-01');

        $viewData = $component->instance()->render()->getData();

        $this->assertSame(['Olivia Ops'], $viewData['departments']->firstWhere('name', 'Operations')->users->pluck('name')->all());
        $this->assertSame(['Stockholm'], $viewData['sites']->all());
        $this->assertSame(['Mira Manager'], $viewData['managers']->pluck('name')->all());
    }
}
