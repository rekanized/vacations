<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $currentUserId = $request->session()->get('current_user_id');

        $currentUser = is_numeric($currentUserId)
            ? User::query()->active()->select(['id', 'is_admin'])->find((int) $currentUserId)
            : null;

        abort_if($currentUser === null || ! $currentUser->is_admin, 403);

        return $next($request);
    }
}