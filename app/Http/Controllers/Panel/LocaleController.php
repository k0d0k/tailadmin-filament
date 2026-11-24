<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Persists the language selected in the panel switcher.
 */
class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $supported = array_keys(config('panel.locales', []));
        $locale = (string) $request->input('locale');

        if (in_array($locale, $supported, true)) {
            $request->session()->put('locale', $locale);
        }

        return back();
    }
}
