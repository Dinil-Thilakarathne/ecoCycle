<?php

namespace Controllers;

use Controllers\BaseController;

class TestController extends BaseController
{
    public function index()
    {
        return $this->view('test/toast', [
            'title' => 'Toast API Test',
        ]);
    }
}
