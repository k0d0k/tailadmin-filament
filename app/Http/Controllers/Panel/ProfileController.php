<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Lets the signed in user view and update their own account profile from the
 * topbar user menu.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->loadMissing(['roles', 'principal', 'tenant']);

        return view('panel.profile', [
            'user' => $user,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->getKey()),
            ],
        ]);

        $user->update($validated);

        return redirect()
            ->route('panel.profile')
            ->with('status', __('ui.profile.updated'));
    }
}
