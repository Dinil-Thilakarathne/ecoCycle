<?php
namespace Middleware\Roles;
use Middleware\RoleMiddleware;
class CollectorOnly extends RoleMiddleware
{
    public function __construct()
    {
        parent::__construct(['collector']);
    }
}
