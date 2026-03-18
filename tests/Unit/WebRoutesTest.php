<?php

namespace Tests\Unit;

use App\Http\Controllers\AdminController;
use App\Http\Middleware\EnsureCurrentUser;
use App\Livewire\VacationPlanner;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WebRoutesTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: array<int, string>, 2: string, 3: string}>
     */
    public static function routeDefinitionsProvider(): array
    {
        return [
            'planner' => ['planner', ['GET', 'HEAD'], '/', VacationPlanner::class],
            'admin index' => ['admin.index', ['GET', 'HEAD'], 'admin', AdminController::class.'@index'],
            'admin logs' => ['admin.logs', ['GET', 'HEAD'], 'admin/logs', AdminController::class.'@logs'],
            'update application name' => ['admin.application-name.update', ['POST'], 'admin/application-name', AdminController::class.'@updateApplicationName'],
            'impersonate user' => ['admin.impersonate', ['POST'], 'admin/impersonate', AdminController::class.'@impersonate'],
            'update user activity' => ['admin.users.activity', ['PATCH'], 'admin/users/{user}/activity', AdminController::class.'@updateUserActivity'],
            'store absence option' => ['admin.absence-options.store', ['POST'], 'admin/absence-options', AdminController::class.'@storeAbsenceOption'],
            'update absence option' => ['admin.absence-options.update', ['PATCH'], 'admin/absence-options/{absenceOption}', AdminController::class.'@updateAbsenceOption'],
            'delete absence option' => ['admin.absence-options.destroy', ['DELETE'], 'admin/absence-options/{absenceOption}', AdminController::class.'@destroyAbsenceOption'],
        ];
    }

    #[DataProvider('routeDefinitionsProvider')]
    public function test_web_routes_are_registered_with_expected_configuration(
        string $name,
        array $methods,
        string $uri,
        string $action,
    ): void {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route);
        $this->assertSame($uri, $route->uri());
        $this->assertEqualsCanonicalizing($methods, $route->methods());
        $this->assertSame($action, $route->getActionName());
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertContains(EnsureCurrentUser::class, $route->gatherMiddleware());
    }
}