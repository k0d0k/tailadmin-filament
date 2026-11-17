<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasSlug;
}
