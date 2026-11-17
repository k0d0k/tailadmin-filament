<?php

namespace App\Policies;

class TenantPolicy extends BaseResourcePolicy
{
    protected string $resource = 'tenant';
}
