# Digital Waste Management System


## Overview

This project is a comprehensive web-based digital platform that streamlines the collection, management, and resale of recyclable waste from residential households. The platform acts as a bridge between citizens and third-party recycling companies, allowing for scheduled waste pickups, transparent bidding on collected materials, and equitable compensation to households contributing recyclable waste.

## 🚀 Built with EcoCycle Framework

This project is built using a custom PHP framework developed specifically for this application. The framework provides Laravel-like functionality with enhanced features for waste management systems.

### Framework Features

- ✅ **Environment Configuration** - Complete .env file support
- ✅ **Advanced Routing** - RESTful routes with parameters and middleware
- ✅ **Dependency Injection** - Auto-wiring container with reflection
- ✅ **HTTP Layer** - Request/Response handling with JSON support
- ✅ **Session Management** - Secure session handling with CSRF protection
- ✅ **Event System** - Event dispatching with listeners
- ✅ **Validation System** - Rule-based validation with custom messages
- ✅ **Base Controllers** - Rich controller base class with helpers
- ✅ **Helper Functions** - Laravel-like global helper functions
- ✅ **Configuration System** - Dot notation config access
- ✅ **Error Handling** - Comprehensive error management

## Key Features

- **📦 Pickup Scheduling**: Customers can schedule pickups for recyclable waste by selecting waste categories and preferred time slots.
- **🧑‍💼 Bidding Module**: Third-party recycling companies can bid in real-time to acquire grouped waste materials.
- **💰 Customer Payment System**: A payment system that ensures equitable compensation for households contributing recyclable waste.
- **🚛 Vehicle & Collector Assignment**: Manage collector assignments and vehicle availability with time-slot management.
- **📊 Admin Dashboard**: An interface for managing users, pickups, bids, and generating reports.
- **🔔 Notification System**: Updates, reminders, and alerts for users regarding their pickups and bids.

## Project Goal

To provide a sustainable, efficient, and scalable platform that promotes responsible recycling habits, facilitates the reuse of materials through third-party partnerships, and contributes to environmental conservation and waste traceability.

## Target Users

- **🧍 Customers**: Households providing recyclable waste.
- **🚛 Collectors**: Assigned staff managing waste pickups.
- **🏭 Recycling Companies**: Third-party businesses bidding for waste lots.
- **🛠️ Administrators**: Platform managers and coordinators.

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- Docker Desktop (recommended) or local PostgreSQL installation
- Extensions: PDO, pdo_pgsql, JSON, mbstring, OpenSSL

### Installation (Docker - Recommended)

**For consistent development environments across all platforms:**

1. **Clone the repository:**

   ```bash
   git clone https://github.com/yourusername/digital-waste-management.git
   cd digital-waste-management
   ```

2. **Set up environment configuration:**

   ```bash
   cp .env.example .env
   # Edit .env file with your application settings
   ```

3. **Start Docker services:**

   ```bash
   docker-compose up -d
   ```

4. **Access the application:**
   Navigate to `http://localhost` in your web browser.

5. **View logs (optional):**

   ```bash
   docker-compose logs -f
   ```

**Note:** The Docker setup automatically:

- Creates PostgreSQL database with schema
- Installs all PHP dependencies
- Configures web server (Caddy)
- Seeds initial data

### Installation (Local Development)

**For development without Docker:**

1. **Clone the repository:**

   ```bash
   git clone https://github.com/yourusername/digital-waste-management.git
   cd digital-waste-management
   ```

2. **Install dependencies using Composer:**

   ```bash
   composer install
   ```

3. **Set up environment configuration:**

   ```bash
   cp .env.example .env
   # Edit .env file with your database and application settings
   ```

4. **Configure your database settings in `.env`:**

   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=eco_cycle
   DB_USERNAME=postgres
   DB_PASSWORD=your_password
   ```

5. **Initialize the database:**

   ```bash
   psql -U postgres -d eco_cycle < database/postgresql/init/01_create_tables.sql
   ```

6. **Start the development server:**

   ```bash
   composer serve
   # or use PHP built-in server
   php -S localhost:8000 -t public
   ```

7. **Access the application:**
   Navigate to `http://localhost:8000` in your web browser.

### Database Migration (MySQL to PostgreSQL)

If you're migrating from MySQL to PostgreSQL, see our comprehensive migration guide:

📖 **[MySQL to PostgreSQL Migration Guide](docs/MIGRATION_GUIDE.md)**

Quick migration steps:

1. Backup your current MySQL database
2. Update `.env` to use PostgreSQL settings
3. Run `docker-compose up -d --build`
4. Verify migration with test scripts

**Additional Resources:**

- [PostgreSQL Quick Reference](docs/postgres-quick-reference.md) - Common commands and syntax
- [Database Setup Guide](docs/database-setup.md) - Detailed database configuration

## Framework Architecture

### Directory Structure

```
ecoCycle/
├── config/               # Configuration files
│   ├── app.php          # Application configuration
│   ├── database.php     # Database configuration
│   ├── session.php      # Session configuration
│   └── routes.php       # Route definitions
├── public/              # Web server document root
│   ├── index.php        # Application entry point
│   ├── css/             # Stylesheets
│   └── js/              # JavaScript files
├── src/
│   ├── Core/            # Framework core components
│   │   ├── Application.php
│   │   ├── Router.php
│   │   ├── Container.php
│   │   ├── Database.php
│   │   ├── Config.php
│   │   ├── Environment.php
│   │   ├── Validator.php
│   │   ├── BaseController.php
│   │   ├── Http/        # HTTP layer
│   │   ├── Session/     # Session management
│   │   └── Events/      # Event system
│   ├── Controllers/     # Application controllers
│   │   ├── AdminController.php
│   │   ├── CustomerController.php
│   │   ├── CollectorController.php
│   │   └── RecyclingCompanyController.php
│   ├── Models/          # Data models
│   │   ├── Customer.php
│   │   ├── Pickup.php
│   │   ├── WasteLot.php
│   │   ├── Bid.php
│   │   └── Payment.php
│   ├── Middleware/      # HTTP middleware
│   │   ├── AuthMiddleware.php
│   │   └── RoleMiddleware.php
│   ├── Services/        # Business logic services
│   │   ├── PaymentService.php
│   │   ├── NotificationService.php
│   │   └── BiddingService.php
│   ├── Views/           # View templates
│   └── helpers.php      # Global helper functions
├── storage/             # File storage and logs
├── migrations/          # Database migrations
└── composer.json        # Dependency management
```

### Quick Examples

#### 1. Defining Routes

```php
// config/routes.php
$router = app('router');

// Customer routes
$router->group(['prefix' => 'customer', 'middleware' => ['auth']], function($router) {
    $router->get('/dashboard', 'CustomerController@dashboard');
    $router->post('/pickup/schedule', 'CustomerController@schedulePickup');
});

// API routes
$router->group(['prefix' => 'api'], function($router) {
    $router->get('/waste-lots', 'WasteLotController@index');
    $router->post('/bids', 'BidController@store');
});
```

#### 2. Controller Example

```php
<?php

namespace Controllers;

use Core\BaseController;
use Models\Pickup;

class CustomerController extends BaseController
{
    public function schedulePickup()
    {
        // Validate request
        $data = $this->validate([
            'waste_type' => 'required|string',
            'pickup_date' => 'required|date',
            'address' => 'required|string'
        ]);

        // Create pickup
        $pickup = Pickup::create([
            'customer_id' => $this->auth()['id'],
            'waste_type' => $data['waste_type'],
            'pickup_date' => $data['pickup_date'],
            'address' => $data['address']
        ]);

        return $this->success('Pickup scheduled successfully', $pickup);
    }
}
```

#### 3. Using Helper Functions

```php
// Get configuration
$appName = config('app.name');

// Access request data
$wasteType = request()->input('waste_type');

// Create responses
return response()->json(['success' => true]);

// Session management
session()->put('user_id', 123);
$userId = session('user_id');

// Generate URLs
$loginUrl = url('/login');
$assetUrl = asset('css/app.css');
```

## Development Scripts

The framework includes several helpful Composer scripts:

```bash
# Start development server
composer serve

# Run tests
composer test

# Run code analysis
composer phpstan

# Fix code style
composer phpcs-fix

# Run database migrations
composer migrate
```

## Framework Documentation

For detailed framework documentation, see [FRAMEWORK_DOCUMENTATION.md](FRAMEWORK_DOCUMENTATION.md).

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support and questions:

- Email: contact@ecocycle.com
- Documentation: [Framework Documentation](FRAMEWORK_DOCUMENTATION.md)
- Issues: [GitHub Issues](https://github.com/yourusername/digital-waste-management/issues)
