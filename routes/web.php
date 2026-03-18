<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureCurrentUser;
use Illuminate\Support\Facades\Route;

use App\Livewire\VacationPlanner;

Route::middleware(EnsureCurrentUser::class)->group(function () {
	Route::get('/', VacationPlanner::class)->name('planner');
	Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
	Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

	Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
	Route::get('/admin/logs', [AdminController::class, 'logs'])->name('admin.logs');
	Route::post('/admin/application-name', [AdminController::class, 'updateApplicationName'])->name('admin.application-name.update');
	Route::post('/admin/impersonate', [AdminController::class, 'impersonate'])->name('admin.impersonate');
	Route::patch('/admin/users/{user}/activity', [AdminController::class, 'updateUserActivity'])->name('admin.users.activity');
	Route::post('/admin/absence-options', [AdminController::class, 'storeAbsenceOption'])->name('admin.absence-options.store');
	Route::patch('/admin/absence-options/{absenceOption}', [AdminController::class, 'updateAbsenceOption'])->name('admin.absence-options.update');
	Route::delete('/admin/absence-options/{absenceOption}', [AdminController::class, 'destroyAbsenceOption'])->name('admin.absence-options.destroy');
});
