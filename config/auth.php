<?php
/**
 * Authentication Configuration
 *
 * Provides demo in-memory users for development and role->dashboard mapping.
 * Replace with database backed provider in production.
 */
return [
    'demo_users' => [
        [
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@ecocycle.com',
            'password_hash' => 'admin123', // plain for demo
            'role_name' => 'admin'
        ],
        [
            'id' => 2,
            'username' => 'customer1',
            'email' => 'customer@ecocycle.com',
            'password_hash' => 'customer123',
            'role_name' => 'customer'
        ],
        [
            'id' => 3,
            'username' => 'collector1',
            'email' => 'collector@ecocycle.com',
            'password_hash' => 'collector123',
            'role_name' => 'collector'
        ],
        [
            'id' => 4,
            'username' => 'company1',
            'email' => 'company@ecocycle.com',
            'password_hash' => 'company123',
            'role_name' => 'company'
        ],
    ],
    'dashboards' => [
        'admin' => '/admin',
        'customer' => '/customer',
        'collector' => '/collector',
        'company' => '/company'
    ]
];
