<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ImpersonationController extends Controller
{
    public function store(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('impersonate', $user);

        $request->session()->put('impersonator_id', $request->user()->id);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->get('impersonator_id');
        $impersonator = $impersonatorId ? User::query()->find($impersonatorId) : null;

        abort_unless($impersonator?->isSuperAdmin(), 403);

        $request->session()->forget('impersonator_id');

        Auth::guard('web')->login($impersonator);
        $request->session()->regenerate();

        return redirect()->route('users.index');
    }
}
