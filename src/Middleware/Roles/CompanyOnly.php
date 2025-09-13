<?php
namespace Middleware\Roles;
use Middleware\RoleMiddleware;
class CompanyOnly extends RoleMiddleware
{
    public function __construct()
    {
        parent::__construct(['company']);
    }
}
