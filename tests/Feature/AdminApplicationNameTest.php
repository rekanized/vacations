<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApplicationNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_the_application_name(): void
    {
        $department = Department::create(['name' => 'Engineering']);
        $user = $department->users()->create(['name' => 'Asta Admin', 'location' => 'Stockholm', 'is_admin' => true]);

        $response = $this
            ->withSession(['current_user_id' => $user->id])
            ->post(route('admin.application-name.update'), [
                'app_name' => 'Vacation Hub',
            ]);

        $response
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHas('status', 'Application name updated.');

        $this->assertSame('Vacation Hub', Setting::valueFor('app_name'));

        $this
            ->withSession(['current_user_id' => $user->id])
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Vacation Hub');
    }
}