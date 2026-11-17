<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Acl;
use Illuminate\Database\Eloquent\Model;

/**
 * Maps Filament's resource authorization checks to the spatie permissions
 * created for a resource (`view_any_user`, `create_user`, ...).
 */
abstract class BaseResourcePolicy
{
    /**
     * The resource key used to build permission names, e.g. `user`.
     */
    protected string $resource;

    protected function allows(User $user, string $action): bool
    {
        if ($user->hasRole(Acl::SUPER_ADMIN_ROLE)) {
            return true;
        }

        return $user->checkPermissionTo(Acl::name($action, $this->resource));
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'view_any');
    }

    public function view(User $user, Model $model): bool
    {
        return $this->allows($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'create');
    }

    public function update(User $user, Model $model): bool
    {
        return $this->allows($user, 'update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->allows($user, 'delete');
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, 'delete');
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->allows($user, 'update');
    }

    public function restoreAny(User $user): bool
    {
        return $this->allows($user, 'update');
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $this->allows($user, 'delete');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->allows($user, 'delete');
    }
}
