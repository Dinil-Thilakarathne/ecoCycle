<?php

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;

class PageController extends BaseController
{
    /**
     * Display the home page with HTML view
     */
    // public function home(Request $request): Response
    // {
    //     return $this->view('pages/home', [
    //         'title' => 'Welcome to EcoCycle - Sustainable Waste Management',
    //         'description' => 'EcoCycle provides reliable, sustainable waste management and recycling solutions.',
    //         'bodyClass' => 'home-page',

    //          'pageStyle' ...
    //     ]);
    // }


    /**
     * Handle form submission
     */
    public function submitForm(Request $request): Response
    {
        $data = $request->all();

        // In a real application, you'd validate and save the data

        return $this->json([
            'message' => 'Form submitted successfully!',
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Show user profile (example with parameters)
     */
    public function userProfile(Request $request): Response
    {
        $username = $request->get('username');

        return $this->json([
            'message' => "User profile for {$username}",
            'user' => [
                'username' => $username,
                'joined' => '2025-01-01',
                'posts' => rand(5, 50),
                'followers' => rand(10, 1000)
            ]
        ]);
    }
}
