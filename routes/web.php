<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AzureAuthenticationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ManualAuthenticationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

use App\Livewire\VacationPlanner;

Route::get('/', HomeController::class)->name('home');
Route::get('/setup', [SetupController::class, 'show'])->name('setup.show');
Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');
Route::post('/setup/manual-admin', [SetupController::class, 'storeManualAdmin'])->name('setup.manual-admin.store');

Route::get('/login', [AzureAuthenticationController::class, 'redirectToProvider'])->name('login');
Route::get('/login/manual', [ManualAuthenticationController::class, 'showLoginForm'])->name('login.manual.form');
Route::post('/login/manual', [ManualAuthenticationController::class, 'login'])->name('login.manual');
Route::get('/auth/azure/callback', [AzureAuthenticationController::class, 'handleCallback'])->name('auth.azure.callback');
Route::post('/logout', [AzureAuthenticationController::class, 'logout'])->name('logout');

Route::middleware('azure-auth')->group(function () {
	Route::get('/planner', VacationPlanner::class)->name('planner');
	Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
	Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
	Route::patch('/profile/theme', [ProfileController::class, 'updateTheme'])->name('profile.theme.update');

	Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
		Route::get('/', [AdminController::class, 'index'])->name('index');
		Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
		Route::get('/authentication', [AdminController::class, 'authentication'])->name('authentication');
		Route::get('/users', [AdminController::class, 'users'])->name('users');
		Route::get('/logs', [AdminController::class, 'logs'])->name('logs');
		Route::post('/application-name', [AdminController::class, 'updateApplicationName'])->name('application-name.update');
		Route::post('/azure-auth', [AdminController::class, 'updateAzureConfiguration'])->name('azure-auth.update');
		Route::post('/manual-users', [AdminController::class, 'storeManualUser'])->name('manual-users.store');
		Route::patch('/users/{user}/activity', [AdminController::class, 'updateUserActivity'])->name('users.activity');
		Route::patch('/users/{user}/admin', [AdminController::class, 'updateUserAdmin'])->name('users.admin');
		Route::patch('/users/{user}/manager', [AdminController::class, 'updateUserManager'])->name('users.manager');
		Route::post('/absence-options', [AdminController::class, 'storeAbsenceOption'])->name('absence-options.store');
		Route::patch('/absence-options/{absenceOption}', [AdminController::class, 'updateAbsenceOption'])->name('absence-options.update');
		Route::delete('/absence-options/{absenceOption}', [AdminController::class, 'destroyAbsenceOption'])->name('absence-options.destroy');
	});
});
