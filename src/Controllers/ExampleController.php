<?php

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;

class ExampleController extends BaseController
{
    /**
     * Display a simple page
     */
    public function index(Request $request): Response
    {
        return $this->json([
            'message' => 'Hello from ExampleController!',
            'data' => [
                'user' => 'Developer',
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Handle form data
     */
    public function store(Request $request): Response
    {
        $data = $request->all();

        return $this->json([
            'message' => 'Data received!',
            'received_data' => $data
        ]);
    }

    /**
     * Example with parameters
     */
    public function show(Request $request): Response
    {
        $id = $request->get('id');

        return $this->json([
            'message' => "Showing item {$id}",
            'item_id' => $id
        ]);
    }
}
