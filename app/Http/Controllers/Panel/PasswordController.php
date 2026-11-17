<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Lets the signed in user change their own password from the topbar user menu.
 */
class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('panel.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // The User model casts `password` to `hashed`, so the plain value is
        // hashed automatically on save.
        $request->user()->update([
            'password' => $validated['password'],
        ]);

        return redirect()
            ->route('panel.password.edit')
            ->with('status', 'Password berhasil diperbarui.');
    }
}
