<?php

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;

/**
 * Navigation Controller
 * 
 * Handles navigation and overview pages
 */
class NavigationController extends BaseController
{
    /**
     * Show dashboard navigation page
     */
    public function index(): Response
    {
        return $this->view('navigation');
    }
}
