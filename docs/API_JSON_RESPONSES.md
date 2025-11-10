# API JSON Responses - Solution Guide

## 🎯 Problem Solved

**Issue**: When testing the login endpoint in Postman, the response was returning HTML pages instead of JSON data.

**Root Cause**: The web authentication routes (`/login`, `/logout`, `/register`) were designed for browser-based requests and return HTML redirects. They check if the request expects JSON, but this requires specific headers that weren't being set consistently.

**Solution**: Created dedicated API authentication endpoints that **always** return JSON responses.

---

## ✅ What Changed

### 1. **New API Authentication Controller**

Created: `src/Controllers/Api/AuthController.php`

This controller provides JSON-only authentication endpoints:

```php
namespace Controllers\Api;

class AuthController extends BaseController
{
    // Always returns JSON (no HTML redirects)
    public function login(Request $request): Response { ... }

    public function logout(): Response { ... }

    public function register(Request $request): Response { ... }

    public function me(): Response { ... }
}
```

### 2. **New API Routes**

Added to `config/routes.php`:

```php
// API Authentication routes (Returns JSON only)
$router->post('/api/auth/login', 'Controllers\Api\AuthController@login');
$router->post('/api/auth/logout', 'Controllers\Api\AuthController@logout', [
    'Middleware\AuthMiddleware'
]);
$router->post('/api/auth/register', 'Controllers\Api\AuthController@register');
$router->get('/api/auth/me', 'Controllers\Api\AuthController@me', [
    'Middleware\AuthMiddleware'
]);
```

### 3. **Updated Postman Collection**

All authentication requests in `postman_collection.json` now use the API endpoints:

- ❌ Old: `POST {{base_url}}/login`
- ✅ New: `POST {{base_url}}/api/auth/login`

---

## 🚀 Using the API Endpoints

### **Login (Admin Example)**

```http
POST http://localhost/api/auth/login
Content-Type: application/json

{
  "email": "admin@ecocycle.com",
  "password": "admin123"
}
```

**Response (200 OK):**

```json
{
  "success": true,
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@ecocycle.com",
    "role": "admin"
  },
  "redirect": "/admin"
}
```

**Response (401 Unauthorized):**

```json
{
  "success": false,
  "message": "Invalid email or password"
}
```

---

### **Logout**

```http
POST http://localhost/api/auth/logout
Cookie: PHPSESSID=<your-session-id>
```

**Response (200 OK):**

```json
{
  "success": true,
  "message": "Logged out successfully",
  "redirect": "/login"
}
```

---

### **Register**

```http
POST http://localhost/api/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "securepass123",
  "password_confirmation": "securepass123",
  "role": "customer"
}
```

**Response (201 Created):**

```json
{
  "success": true,
  "message": "Registration successful",
  "user": {
    "id": 42,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "customer"
  }
}
```

**Response (422 Validation Error):**

```json
{
  "success": false,
  "message": "Passwords do not match"
}
```

---

### **Get Current User**

```http
GET http://localhost/api/auth/me
Cookie: PHPSESSID=<your-session-id>
```

**Response (200 OK):**

```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@ecocycle.com",
    "role": "admin"
  }
}
```

**Response (401 Unauthorized):**

```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

---

## 📋 Testing in Postman

### **Quick Test Workflow**

1. **Import Updated Collection**

   - Re-import `postman_collection.json` to get the latest changes

2. **Set Environment**

   ```
   base_url = http://localhost
   ```

3. **Test Login**

   - Go to: `Authentication` → `Login - Admin`
   - Click **Send**
   - ✅ You should now see JSON response (not HTML)

4. **Verify Session**

   - The session cookie (`PHPSESSID`) is automatically saved
   - Go to: `Authentication` → `Get Current User` (if you added it)
   - Click **Send** to verify you're logged in

5. **Test Protected Endpoints**
   - Go to: `Admin - Vehicles` → `List All Vehicles`
   - Click **Send**
   - Should work with your admin session

---

## 🔄 Route Comparison

### **Web Routes (Browser-Based)**

| Method | Endpoint    | Returns               | Use Case                  |
| ------ | ----------- | --------------------- | ------------------------- |
| `GET`  | `/login`    | HTML form             | Display login page        |
| `POST` | `/login`    | HTML redirect or JSON | Form submission           |
| `POST` | `/logout`   | HTML redirect         | Browser logout            |
| `GET`  | `/register` | HTML form             | Display registration page |
| `POST` | `/register` | HTML redirect or JSON | Form submission           |

**Behavior**: Returns JSON only if request has `Accept: application/json` header or `X-Requested-With: XMLHttpRequest`

### **API Routes (Always JSON)**

| Method | Endpoint             | Returns | Use Case         |
| ------ | -------------------- | ------- | ---------------- |
| `POST` | `/api/auth/login`    | JSON    | API login        |
| `POST` | `/api/auth/logout`   | JSON    | API logout       |
| `POST` | `/api/auth/register` | JSON    | API registration |
| `GET`  | `/api/auth/me`       | JSON    | Get current user |

**Behavior**: **Always returns JSON**, no HTML ever

---

## 🎨 Response Format Standards

### **Success Response Structure**

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... },
  // Optional fields:
  "user": { ... },
  "redirect": "/path"
}
```

### **Error Response Structure**

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

### **HTTP Status Codes**

| Code  | Meaning          | Example                    |
| ----- | ---------------- | -------------------------- |
| `200` | Success          | Login successful           |
| `201` | Created          | User registered            |
| `401` | Unauthorized     | Invalid credentials        |
| `403` | Forbidden        | No permission              |
| `422` | Validation Error | Invalid input              |
| `500` | Server Error     | Database connection failed |

---

## 🛠️ Implementation Details

### **Session Management**

Both web and API routes use the same session system:

```php
// Login creates a session
session()->login($userId, $userData);
session()->put('user_name', $name);
session()->put('user_email', $email);
session()->put('user_role', $role);

// Session is sent as cookie
// Postman automatically stores and sends PHPSESSID cookie
```

### **Authentication Check**

```php
// In API controllers
if (!session()->isLoggedIn()) {
    return Response::errorJson('Unauthenticated', 401);
}
```

### **Demo Users**

If the database is not available, the system falls back to demo users:

```php
// Configured in config/auth.php
'demo_users' => [
    [
        'id' => 1,
        'email' => 'admin@ecocycle.com',
        'password_hash' => 'admin123',
        'role' => 'admin',
        'name' => 'Admin User'
    ],
    // ... more demo users
]
```

---

## 🔍 Troubleshooting

### **Problem: Still getting HTML responses**

**Cause**: Using old web routes instead of API routes

**Solution**: Make sure you're using:

- ✅ `POST /api/auth/login`
- ❌ NOT `POST /login`

---

### **Problem: 401 Unauthorized on protected endpoints**

**Cause**: Session cookie not being sent

**Check**:

1. Verify login was successful
2. Check Postman cookies: `Cookies` → `localhost`
3. Look for `PHPSESSID` cookie
4. Ensure "Cookie" header is present in subsequent requests

**Solution**: Postman should automatically handle cookies. If not:

1. Go to Settings → General
2. Enable "Automatically follow redirects"
3. Enable "Send cookies with requests"

---

### **Problem: CSRF token errors**

**Note**: CSRF protection is typically disabled for API routes, but if you encounter it:

**Check middleware in routes.php**:

```php
// Should NOT have CsrfMiddleware for API auth routes
$router->post('/api/auth/login', 'Controllers\Api\AuthController@login');
// No CsrfMiddleware ✅
```

---

### **Problem: Can't test in browser**

**Note**: API routes return JSON, not HTML pages

**For browser testing**, use the web routes:

- Go to: `http://localhost/login` (shows HTML form)
- Submit form (returns HTML redirect)

**For API testing**, use:

- Postman
- cURL
- JavaScript fetch/axios
- Mobile apps

---

## 📚 Related Documentation

- **[API Documentation](./API_DOCUMENTATION.md)** - Complete API reference
- **[Postman Testing Guide](./POSTMAN_TESTING_GUIDE.md)** - Detailed testing workflows
- **[Postman Setup Checklist](./POSTMAN_SETUP_CHECKLIST.md)** - Quick start guide
- **[Authentication Documentation](./authentication.md)** - Session and auth system details

---

## 🎯 Summary

### **Before (Problem)**

```bash
POST /login
→ Returns HTML redirect (not JSON)
→ Postman shows HTML page source
→ Can't parse response in tests
```

### **After (Solution)**

```bash
POST /api/auth/login
→ Always returns JSON
→ Postman shows structured data
→ Tests work perfectly
```

### **Key Takeaway**

**Use `/api/auth/*` endpoints for all API testing in Postman!**

---

## 💡 Best Practices

1. **Use API routes for API testing**

   - `/api/auth/login` for Postman/mobile apps
   - `/login` for browser-based web interface

2. **Check response format**

   - API routes: Always JSON
   - Web routes: JSON if `Accept: application/json`, otherwise HTML

3. **Session cookies**

   - Both web and API routes use the same session system
   - Cookie is automatically handled by Postman
   - Session persists across API and web routes

4. **Error handling**
   - Always check `success` field in JSON response
   - Check HTTP status code (200 = success, 401 = auth error)
   - Read `message` field for user-friendly error descriptions

---

## ✅ Verification Checklist

- [ ] API controller exists: `src/Controllers/Api/AuthController.php`
- [ ] API routes registered in `config/routes.php`
- [ ] Postman collection updated with `/api/auth/*` endpoints
- [ ] Test login in Postman → Returns JSON ✅
- [ ] Session cookie saved automatically ✅
- [ ] Protected endpoints work with session ✅
- [ ] Logout returns JSON ✅

---

**🎉 Your API authentication is now working perfectly in Postman!**

If you encounter any issues, refer to the troubleshooting section or check the related documentation.
