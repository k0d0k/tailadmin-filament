<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Principal;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = collect([
            ['key' => 'users', 'label' => 'User', 'value' => User::count(), 'icon' => 'users', 'route' => 'users', 'tone' => 'brand', 'model' => User::class],
            ['key' => 'principals', 'label' => 'Principal', 'value' => Principal::count(), 'icon' => 'building-office-2', 'route' => 'principals', 'tone' => 'success', 'model' => Principal::class],
            ['key' => 'tenants', 'label' => 'Tenant', 'value' => Tenant::count(), 'icon' => 'building-storefront', 'route' => 'tenants', 'tone' => 'warning', 'model' => Tenant::class],
            ['key' => 'roles', 'label' => 'Role', 'value' => Role::count(), 'icon' => 'shield-check', 'route' => 'roles', 'tone' => 'brand', 'model' => Role::class],
            ['key' => 'permissions', 'label' => 'Permission', 'value' => Permission::count(), 'icon' => 'key', 'route' => 'permissions', 'tone' => 'success', 'model' => Permission::class],
            ['key' => 'menus', 'label' => 'Menu', 'value' => Menu::count(), 'icon' => 'bars-3', 'route' => 'menus', 'tone' => 'warning', 'model' => Menu::class],
        ])->filter(fn (array $stat): bool => Gate::allows('viewAny', $stat['model']))->values()->all();

        return view('panel.dashboard', [
            'stats' => $stats,
            'canViewUsers' => Gate::allows('viewAny', User::class),
            'canViewPrincipals' => Gate::allows('viewAny', Principal::class),
            'recentUsers' => $this->recentUsers(),
            'principals' => $this->principals(),
        ]);
    }

    /**
     * @return Collection<int, User>
     */
    protected function recentUsers(): Collection
    {
        if (! Gate::allows('viewAny', User::class)) {
            return collect();
        }

        return User::query()->with(['roles', 'principal'])->latest()->limit(5)->get();
    }

    /**
     * @return Collection<int, Principal>
     */
    protected function principals(): Collection
    {
        if (! Gate::allows('viewAny', Principal::class)) {
            return collect();
        }

        return Principal::query()->withCount(['tenants', 'users'])->orderBy('name')->get();
    }
}
