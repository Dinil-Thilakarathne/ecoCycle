<?php
return [
    'defaults' => [
        'name_label' => 'Full name',
        'name_placeholder' => 'Your full name',
    ],
    'roles' => [
        'customer' => [
            'label' => 'Customer',
            'option' => 'Customer — Track recycling requests',
            'summary' => 'Ideal for residents scheduling and monitoring recycling pickups.',
            'fields' => [
                [
                    'name' => 'phone',
                    'label' => 'Phone number',
                    'type' => 'tel',
                    'placeholder' => '07XXXXXXXX',
                    'help' => 'Use a 10-digit mobile number starting with 0.',
                    // made nullable so registration can proceed without phone for now
                    'rules' => ['nullable', 'regex:/^0\d{9}$/'],
                    'store' => 'user',
                    'column' => 'phone',
                    'attributes' => [
                        'pattern' => '^0\\d{9}$',
                        'maxlength' => '10',
                        'inputmode' => 'numeric',
                    ],
                ],
                [
                    'name' => 'address',
                    'label' => 'Address',
                    'type' => 'textarea',
                    'placeholder' => 'Street address, city',
                    // made nullable so registration can proceed without address for now
                    'rules' => ['nullable', 'max:255'],
                    'store' => 'user',
                    'column' => 'address',
                    'attributes' => [
                        'rows' => '3',
                    ],
                ],
                [
                    'name' => 'postalCode',
                    'label' => 'Postal code',
                    'type' => 'text',
                    'placeholder' => 'Postal code',
                    'help' => 'Numeric, up to 5 digits.',
                    'rules' => ['required', 'regex:/^\d{1,5}$/'],
                    'store' => 'metadata',
                    'attributes' => [
                        'pattern' => '^\d{1,5}$',
                        'maxlength' => '5',
                        'inputmode' => 'numeric',
                    ],
                ],

            ],
        ],
        'company' => [
            'label' => 'Company',
            'option' => 'Company — Operations & analytics',
            'summary' => 'Built for company managers supervising recycling performance and KPIs.',
            'overrides' => [
                'name_label' => 'Account owner name',
                'name_placeholder' => 'Main contact full name',
            ],
            'fields' => [
                [
                    'name' => 'companyName',
                    'label' => 'Company name',
                    'type' => 'text',
                    'placeholder' => 'EcoCycle Industries',
                    'rules' => ['required', 'max:150'],
                    'store' => 'metadata',
                ],

                [
                    'name' => 'companyPhone',
                    'label' => 'Company phone (Fixed Line)',
                    'type' => 'tel',
                    'placeholder' => '011XXXXXXX',
                    // Regex enforces 0 followed by NOT 7, then 8 digits. 
                    // Matches 011..., 038... but NOT 07...
                    'rules' => ['required', 'regex:/^0(?!7)\d{9}$/'],
                    'store' => 'user',
                    'column' => 'phone',
                    'attributes' => [
                        'pattern' => '^0(?!7)\\d{9}$',
                        'maxlength' => '10',
                        'inputmode' => 'numeric',
                    ],
                ],
                [
                    'name' => 'registrationNumber',
                    'label' => 'Business registration number',
                    'type' => 'text',
                    'placeholder' => 'BR123456',
                    'rules' => ['required', 'max:64'],
                    'store' => 'metadata',
                ],
                [
                    'name' => 'address',
                    'label' => 'Head office address',
                    'type' => 'textarea',
                    'placeholder' => 'Registered business address',
                    // made nullable so company registration can proceed without address for now
                    'rules' => ['nullable', 'max:255'],
                    'store' => 'user',
                    'column' => 'address',
                    'attributes' => [
                        'rows' => '3',
                    ],
                ],
            ],
        ],

    ],
];
