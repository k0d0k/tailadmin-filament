<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Support\Acl;
use App\Support\Panel\IconLibrary;
use App\Support\Panel\ModuleGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Native TailAdmin front-end for the CRUD generator. Replaces the Filament
 * page so modules can be scaffolded without leaving /panel.
 */
class GeneratorController extends Controller
{
    public function show(): View
    {
        $this->authorizeGenerator();

        return view('panel.generator', [
            'fieldTypes' => ModuleGenerator::FIELD_TYPES,
            'icons' => array_combine(IconLibrary::keys(), IconLibrary::keys()),
            'iconMap' => IconLibrary::map(),
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $this->authorizeGenerator();

        $data = $request->validate([
            'model' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z][A-Za-z0-9]*$/'],
            'label' => ['nullable', 'string', 'max:255'],
            'navigation_group' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.name' => ['required', 'string', 'max:100'],
            'fields.*.type' => ['required', 'string'],
            'fields.*.label' => ['nullable', 'string', 'max:255'],
            'fields.*.options' => ['nullable', 'string', 'max:1000'],
            'fields.*.default' => ['nullable', 'string', 'max:255'],
            'fields.*.nullable' => ['boolean'],
            'fields.*.in_form' => ['boolean'],
            'fields.*.in_table' => ['boolean'],
        ]);

        try {
            $result = app(ModuleGenerator::class)->generate($data);
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('panel.index', $result['key'])
            ->with('status', "Modul {$result['title']} berhasil dibuat dengan ".count($result['permissions']).' permission.');
    }

    protected function authorizeGenerator(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && ($user->hasRole(Acl::SUPER_ADMIN_ROLE) || $user->checkPermissionTo('generate_crud')),
            403,
        );
    }
}
