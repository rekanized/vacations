<?php

namespace Tests\Unit;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AzureAuthenticationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ManualAuthenticationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SetupController;
use App\Livewire\VacationPlanner;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WebRoutesTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: array<int, string>, 2: string, 3: string, 4?: array<int, string>}>
     */
    public static function routeDefinitionsProvider(): array
    {
        return [
            'home' => ['home', ['GET', 'HEAD'], '/', HomeController::class],
            'setup show' => ['setup.show', ['GET', 'HEAD'], 'setup', SetupController::class.'@show'],
            'setup azure store' => ['setup.store', ['POST'], 'setup', SetupController::class.'@store'],
            'setup manual admin store' => ['setup.manual-admin.store', ['POST'], 'setup/manual-admin', SetupController::class.'@storeManualAdmin'],
            'azure login' => ['login', ['GET', 'HEAD'], 'login', AzureAuthenticationController::class.'@redirectToProvider'],
            'manual login form' => ['login.manual.form', ['GET', 'HEAD'], 'login/manual', ManualAuthenticationController::class.'@showLoginForm'],
            'manual login' => ['login.manual', ['POST'], 'login/manual', ManualAuthenticationController::class.'@login'],
            'azure callback' => ['auth.azure.callback', ['GET', 'HEAD'], 'auth/azure/callback', AzureAuthenticationController::class.'@handleCallback'],
            'logout' => ['logout', ['POST'], 'logout', AzureAuthenticationController::class.'@logout'],
            'planner' => ['planner', ['GET', 'HEAD'], 'planner', VacationPlanner::class, ['azure-auth']],
            'profile show' => ['profile.show', ['GET', 'HEAD'], 'profile', ProfileController::class.'@show', ['azure-auth']],
            'admin index' => ['admin.index', ['GET', 'HEAD'], 'admin', AdminController::class.'@index', ['azure-auth', 'admin']],
            'admin settings' => ['admin.settings', ['GET', 'HEAD'], 'admin/settings', AdminController::class.'@settings', ['azure-auth', 'admin']],
            'admin authentication' => ['admin.authentication', ['GET', 'HEAD'], 'admin/authentication', AdminController::class.'@authentication', ['azure-auth', 'admin']],
            'admin users' => ['admin.users', ['GET', 'HEAD'], 'admin/users', AdminController::class.'@users', ['azure-auth', 'admin']],
            'admin logs' => ['admin.logs', ['GET', 'HEAD'], 'admin/logs', AdminController::class.'@logs', ['azure-auth', 'admin']],
            'update application name' => ['admin.application-name.update', ['POST'], 'admin/application-name', AdminController::class.'@updateApplicationName', ['azure-auth', 'admin']],
            'update azure auth' => ['admin.azure-auth.update', ['POST'], 'admin/azure-auth', AdminController::class.'@updateAzureConfiguration', ['azure-auth', 'admin']],
            'store manual user' => ['admin.manual-users.store', ['POST'], 'admin/manual-users', AdminController::class.'@storeManualUser', ['azure-auth', 'admin']],
            'update user activity' => ['admin.users.activity', ['PATCH'], 'admin/users/{user}/activity', AdminController::class.'@updateUserActivity', ['azure-auth', 'admin']],
            'update user admin' => ['admin.users.admin', ['PATCH'], 'admin/users/{user}/admin', AdminController::class.'@updateUserAdmin', ['azure-auth', 'admin']],
            'update user manager' => ['admin.users.manager', ['PATCH'], 'admin/users/{user}/manager', AdminController::class.'@updateUserManager', ['azure-auth', 'admin']],
            'store absence option' => ['admin.absence-options.store', ['POST'], 'admin/absence-options', AdminController::class.'@storeAbsenceOption', ['azure-auth', 'admin']],
            'update absence option' => ['admin.absence-options.update', ['PATCH'], 'admin/absence-options/{absenceOption}', AdminController::class.'@updateAbsenceOption', ['azure-auth', 'admin']],
            'delete absence option' => ['admin.absence-options.destroy', ['DELETE'], 'admin/absence-options/{absenceOption}', AdminController::class.'@destroyAbsenceOption', ['azure-auth', 'admin']],
        ];
    }

    #[DataProvider('routeDefinitionsProvider')]
    public function test_web_routes_are_registered_with_expected_configuration(
        string $name,
        array $methods,
        string $uri,
        string $action,
        array $middleware = [],
    ): void {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route);
        $this->assertSame($uri, $route->uri());
        $this->assertEqualsCanonicalizing($methods, $route->methods());
        $this->assertSame($action, $route->getActionName());
        $this->assertContains('web', $route->gatherMiddleware());

        foreach ($middleware as $middlewareClass) {
            $this->assertContains($middlewareClass, $route->gatherMiddleware());
        }
    }
}