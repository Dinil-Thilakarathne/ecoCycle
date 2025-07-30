# EcoCycle Framework Documentation

## Overview

This is a comprehensive PHP framework built from scratch for the Digital Waste Management System. It provides Laravel-like functionality with a focus on simplicity, performance, and maintainability.

## Framework Features

### ✅ Core Components Implemented

#### 1. **Environment Configuration (.env handling)**

- **File**: `src/Core/Environment.php`
- **Features**:
  - Loads `.env` files with support for quotes, booleans, and null values
  - Environment variable parsing and type conversion
  - Integration with PHP's global environment variables

#### 2. **Advanced Routing System**

- **File**: `src/Core/Router.php`
- **Features**:
  - HTTP method routing (GET, POST, PUT, DELETE, PATCH)
  - Route parameters with dynamic matching `{id}`, `{name}`
  - Route groups with shared middleware and prefixes
  - Middleware support per route
  - Wildcard routes

#### 3. **Dependency Injection Container**

- **File**: `src/Core/Container.php`
- **Features**:
  - Auto-wiring with reflection
  - Singleton and instance binding
  - Callable resolution
  - Constructor dependency injection
  - Service resolution

#### 4. **HTTP Request/Response Layer**

- **Files**: `src/Core/Http/Request.php`, `src/Core/Http/Response.php`
- **Features**:
  - PSR-7 inspired request handling
  - JSON request/response support
  - File upload handling
  - Header management
  - Content type detection

#### 5. **Session Management**

- **File**: `src/Core/Session/SessionManager.php`
- **Features**:
  - Multiple drivers support (file, database, redis)
  - Flash messaging
  - CSRF token generation
  - User authentication state
  - Secure session configuration

#### 6. **Event System**

- **File**: `src/Core/Events/EventDispatcher.php`
- **Features**:
  - Event registration and dispatching
  - Wildcard event listeners
  - Priority-based event handling
  - Event payload support

#### 7. **Application Core**

- **File**: `src/Core/Application.php`
- **Features**:
  - Application bootstrapping
  - Service provider registration
  - Request lifecycle management
  - Error handling
  - Singleton pattern

#### 8. **Configuration System**

- **File**: `src/Core/Config.php`
- **Features**:
  - Dot notation access (`config('database.host')`)
  - Environment variable integration
  - Configuration file loading
  - Runtime configuration updates

#### 9. **Validation System**

- **File**: `src/Core/Validator.php`
- **Features**:
  - Rule-based validation
  - Custom error messages
  - Built-in validation rules (required, email, min, max, etc.)
  - Array and nested validation

#### 10. **Base Controller**

- **File**: `src/Core/BaseController.php`
- **Features**:
  - Middleware execution
  - Response helpers (json, redirect, view)
  - Authentication helpers
  - Validation integration

#### 11. **Helper Functions**

- **File**: `src/helpers.php`
- **Features**:
  - Laravel-like global helpers
  - Environment and config access
  - URL generation
  - CSRF protection
  - Request/response shortcuts

## Configuration Files

### Database Configuration

```php
// config/database.php
return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'database' => env('DB_DATABASE', 'ecocycle'),
            // ... more config
        ]
    ]
];
```

### Application Configuration

```php
// config/app.php
return [
    'name' => env('APP_NAME', 'EcoCycle'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'providers' => [
        // Service providers
    ],
    'aliases' => [
        // Class aliases
    ]
];
```

### Session Configuration

```php
// config/session.php
return [
    'driver' => env('SESSION_DRIVER', 'file'),
    'lifetime' => env('SESSION_LIFETIME', 120),
    'encrypt' => env('SESSION_ENCRYPT', false),
    // ... more session config
];
```

## Usage Examples

### 1. Basic Routing

```php
// In routes file
$router = app('router');

$router->get('/users', 'UserController@index');
$router->post('/users', 'UserController@store');
$router->get('/users/{id}', 'UserController@show');

// Route groups
$router->group(['prefix' => 'api', 'middleware' => ['auth']], function($router) {
    $router->get('/profile', 'UserController@profile');
});
```

### 2. Controller Example

```php
<?php

namespace Controllers;

use Core\BaseController;

class UserController extends BaseController
{
    public function index()
    {
        $users = User::all();
        return $this->json($users);
    }

    public function store()
    {
        $data = $this->validate([
            'name' => 'required|string',
            'email' => 'required|email'
        ]);

        $user = User::create($data);
        return $this->success('User created', $user);
    }
}
```

### 3. Middleware Example

```php
<?php

namespace Middleware;

class AuthMiddleware
{
    public function handle($request, $next)
    {
        if (!session()->isAuthenticated()) {
            return redirect('/login');
        }

        return $next($request);
    }
}
```

### 4. Using Helper Functions

```php
// Environment variables
$dbHost = env('DB_HOST', 'localhost');

// Configuration
$appName = config('app.name');

// Request data
$name = request()->input('name');

// Response
return response()->json(['status' => 'success']);

// Session
session()->put('user_id', 123);
$userId = session('user_id');

// Validation
$validator = validator($data, [
    'email' => 'required|email'
]);
```

## Framework Strengths

### 🚀 **Performance**

- Minimal overhead with only necessary components loaded
- Efficient autoloading with PSR-4
- Optimized for PHP 7.4+ features

### 🔒 **Security**

- CSRF protection built-in
- Secure session management
- XSS protection in responses
- SQL injection prevention through PDO

### 🧩 **Modularity**

- Dependency injection for loose coupling
- Event-driven architecture
- Middleware pattern for cross-cutting concerns
- Service provider pattern for bootstrapping

### 📚 **Developer Experience**

- Laravel-like syntax for familiarity
- Comprehensive helper functions
- Clear error messages in debug mode
- Well-documented codebase

### 🔧 **Extensibility**

- Easy to add new validation rules
- Custom middleware support
- Event system for plugins
- Service provider registration

## Next Steps for Enhancement

### 1. **ORM/Database Layer**

- Eloquent-like model system
- Query builder
- Database migrations
- Model relationships

### 2. **View Engine**

- Template system (Twig-like)
- View compilation and caching
- Layout inheritance
- Component system

### 3. **CLI Commands**

- Artisan-like command system
- Code generation commands
- Database seeding
- Cache management

### 4. **Caching System**

- Multiple cache drivers
- Cache tags and invalidation
- Query result caching
- View caching

### 5. **Testing Framework**

- Unit testing support
- HTTP testing
- Database testing
- Mocking utilities

## Installation & Setup

1. **Clone the repository**

```bash
git clone <repository-url>
cd ecoCycle
```

2. **Install dependencies**

```bash
composer install
```

3. **Environment setup**

```bash
cp .env.example .env
# Edit .env with your configuration
```

4. **Start development server**

```bash
composer serve
# or
php -S localhost:8000 -t public
```

## Folder Structure

```
ecoCycle/
├── config/          # Configuration files
├── public/          # Web server document root
├── src/
│   ├── Core/        # Framework core components
│   ├── Controllers/ # Application controllers
│   ├── Models/      # Data models
│   ├── Middleware/  # HTTP middleware
│   ├── Services/    # Business logic services
│   └── helpers.php  # Global helper functions
├── storage/         # File storage
├── migrations/      # Database migrations
└── composer.json    # Dependency management
```

This framework provides a solid foundation for building the Digital Waste Management System with all the essential features of a modern PHP framework while maintaining simplicity and performance.
