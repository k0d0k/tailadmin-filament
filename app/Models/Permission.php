<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasSlug;
}
