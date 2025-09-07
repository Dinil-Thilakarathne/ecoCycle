<?php
namespace Middleware\Roles;
use Middleware\RoleMiddleware;
class AdminOnly extends RoleMiddleware
{
    public function __construct()
    {
        parent::__construct(['admin']);
    }
}
