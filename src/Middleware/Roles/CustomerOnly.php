<?php
namespace Middleware\Roles;
use Middleware\RoleMiddleware;
class CustomerOnly extends RoleMiddleware
{
    public function __construct()
    {
        parent::__construct(['customer']);
    }
}
