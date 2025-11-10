# 🔧 Troubleshooting: 404 Page Not Found on /api/auth/login

## 🎯 Your Issue

**Error**: "Page not found" when accessing `http://localhost:8080/api/auth/login`

## 🔍 Common Causes & Solutions

### ❌ **Cause 1: Using GET Instead of POST**

The `/api/auth/login` endpoint only accepts **POST** requests, not GET.

**Wrong** (Browser/GET):

```
❌ http://localhost:8080/api/auth/login
→ Opens in browser = GET request = 404 Not Found
```

**Correct** (POST request):

```bash
✅ curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@ecocycle.com","password":"admin123"}'
```

**✅ Solution**: Use POST method in:

- Postman
- cURL
- JavaScript fetch
- **NOT** by typing URL in browser (that's GET)

---

### ❌ **Cause 2: Docker Container Not Running**

Your app runs on Docker port 8080. Container might not be running.

**Check if running**:

```bash
docker ps | grep ecocycle
```

**Expected output**:

```
ecocycle-app    Up 5 minutes    0.0.0.0:8080->80/tcp
ecocycle-db     Up 5 minutes    0.0.0.0:5432->5432/tcp
```

**✅ Solution**: Start Docker containers:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/ecoCycle
docker-compose -f docker-compose.dev.yml up -d
```

---

### ❌ **Cause 3: Routes Not Loaded / Cache Issue**

Routes might not be loaded or cached.

**✅ Solution**: Restart Docker containers:

```bash
docker-compose -f docker-compose.dev.yml restart app
```

Or rebuild:

```bash
docker-compose -f docker-compose.dev.yml down
docker-compose -f docker-compose.dev.yml up -d --build
```

---

### ❌ **Cause 4: Wrong Base URL**

You're using XAMPP path but running Docker on port 8080.

**Wrong URLs**:

```
❌ http://localhost/api/auth/login (XAMPP default port 80)
❌ http://localhost:80/api/auth/login
```

**Correct URL**:

```
✅ http://localhost:8080/api/auth/login (Docker port 8080)
```

**✅ Solution**: Update Postman environment:

```
base_url = http://localhost:8080
```

---

### ❌ **Cause 5: PHP Syntax Error in Controller**

If there's a syntax error, routes won't load.

**Check logs**:

```bash
# Docker logs
docker logs ecocycle-app --tail 50

# Or inside container
docker exec -it ecocycle-app tail -f /var/log/apache2/error.log
```

**✅ Solution**: Fix any PHP errors shown in logs.

---

## 🧪 Quick Diagnostic Tests

### Test 1: Check if Docker is running

```bash
docker ps
```

**Expected**: See `ecocycle-app` and `ecocycle-db` containers running

---

### Test 2: Check if web server responds

```bash
curl http://localhost:8080
```

**Expected**: HTML response (homepage or navigation page)

---

### Test 3: Check if routes are registered

```bash
curl http://localhost:8080/routes/list
```

**Expected**: JSON list of all routes including `/api/auth/login`

---

### Test 4: Test the actual login endpoint (POST)

```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@ecocycle.com","password":"admin123"}'
```

**Expected**: JSON response with user data

---

### Test 5: Check Apache/PHP-FPM is working inside Docker

```bash
docker exec -it ecocycle-app php -v
```

**Expected**: PHP version info

---

## 🎯 Most Likely Issue: Using GET Instead of POST

**The #1 reason** for this error is trying to access the endpoint in a browser (GET request).

### ✅ Correct Way to Test

**In Postman**:

1. Set method to **POST** (not GET)
2. Set URL to `http://localhost:8080/api/auth/login`
3. Set Body → raw → JSON:
   ```json
   {
     "email": "admin@ecocycle.com",
     "password": "admin123"
   }
   ```
4. Click **Send**

**Using cURL**:

```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@ecocycle.com",
    "password": "admin123"
  }'
```

**Using JavaScript (fetch)**:

```javascript
fetch("http://localhost:8080/api/auth/login", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
  },
  body: JSON.stringify({
    email: "admin@ecocycle.com",
    password: "admin123",
  }),
})
  .then((res) => res.json())
  .then((data) => console.log(data));
```

---

## 📋 Complete Troubleshooting Checklist

```
[ ] Docker containers are running (docker ps)
[ ] App container is healthy (no restart loops)
[ ] Web server responds on port 8080 (curl http://localhost:8080)
[ ] Using POST method (not GET)
[ ] Using correct URL: http://localhost:8080/api/auth/login
[ ] Content-Type header is set to application/json
[ ] Request body contains email and password
[ ] No PHP errors in logs (docker logs ecocycle-app)
[ ] Routes are registered (check /routes/list)
```

---

## 🔄 Step-by-Step Fix

### 1️⃣ Start Docker (if not running)

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/ecoCycle
docker-compose -f docker-compose.dev.yml up -d
```

### 2️⃣ Wait for containers to be healthy

```bash
docker ps
# Wait until status shows "healthy" or "Up X minutes"
```

### 3️⃣ Test basic connectivity

```bash
curl http://localhost:8080
# Should return HTML
```

### 4️⃣ Verify routes are loaded

```bash
curl http://localhost:8080/routes/list
# Should return JSON with list of routes
```

### 5️⃣ Test login with POST

```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@ecocycle.com","password":"admin123"}'
```

**Expected Response**:

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

---

## 🐛 Still Not Working?

### Check Container Logs

```bash
# View all logs
docker logs ecocycle-app

# Follow logs in real-time
docker logs -f ecocycle-app

# Last 50 lines
docker logs ecocycle-app --tail 50
```

### Restart Container

```bash
docker-compose -f docker-compose.dev.yml restart app
```

### Rebuild Container

```bash
docker-compose -f docker-compose.dev.yml down
docker-compose -f docker-compose.dev.yml up -d --build
```

### Check Inside Container

```bash
# Enter container
docker exec -it ecocycle-app bash

# Check if controller file exists
ls -la /var/www/html/src/Controllers/Api/AuthController.php

# Check routes file
cat /var/www/html/config/routes.php | grep "api/auth"

# Test PHP syntax
php -l /var/www/html/src/Controllers/Api/AuthController.php
```

---

## ✅ Working Example

Once fixed, your POST request should work like this:

**Request**:

```bash
curl -v -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@ecocycle.com","password":"admin123"}'
```

**Response**:

```http
HTTP/1.1 200 OK
Content-Type: application/json

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

---

## 📝 Summary

**Most Common Issue**: Using GET instead of POST

**Quick Fix**:

1. Make sure Docker is running: `docker ps`
2. Use POST method in Postman
3. Use correct URL: `http://localhost:8080/api/auth/login`
4. Set Content-Type: `application/json`
5. Send JSON body with email and password

**Test Command**:

```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@ecocycle.com","password":"admin123"}'
```

---

## 🎯 Next Steps

After fixing the 404:

1. ✅ Test login works
2. ✅ Update Postman environment: `base_url = http://localhost:8080`
3. ✅ Test all authentication endpoints
4. ✅ Test protected API endpoints

**Need more help?** Check the logs with `docker logs ecocycle-app`
