<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AzureAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request, AzureAuthenticationService $azureAuthentication): View|RedirectResponse
    {
        $currentUserId = $request->session()->get('current_user_id');

        if (is_numeric($currentUserId) && User::query()->active()->whereKey((int) $currentUserId)->exists()) {
            return redirect()->route('planner');
        }

        $request->session()->forget('current_user_id');

        if (User::query()->count() === 0) {
            return redirect()->route('setup.show');
        }

        return view('welcome', [
            'azureConfigured' => $azureAuthentication->hasConfiguration(),
            'manualLoginAvailable' => User::query()->whereNotNull('password')->exists(),
        ]);
    }
}