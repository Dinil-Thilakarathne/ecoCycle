# Implementing Backend API Endpoints (Step-by-step)

This guide explains how to implement a backend API endpoint in this project, from creating the API controller to registering the route and calling the API from a view. It uses existing project conventions (namespaces, `BaseController`, `Response::json`, CSRF handling and middleware).

Contents

- Overview
- 1. Create an API controller
- 2. Add controller methods (Request/Response pattern)
- 3. Register API routes (link to router/main controller)
- 4. Protect endpoints with middleware (auth / CSRF / roles)
- 5. Call the API from a view (client-side `fetch` examples)
- Example: `ExampleController` full implementation
- Quick checklist

---

Overview
This project keeps API controllers under `src/Controllers/Api` with namespace `Controllers\Api`. Controllers extend `Controllers\BaseController` and use `Core\Http\Request` and `Core\Http\Response` helpers to read input and return JSON.

1. Create an API controller

- File location: `src/Controllers/Api/ExampleController.php` (or subfolder like `Api/Company/`)
- Namespace: `namespace Controllers\Api;`
- Extend: `use Controllers\BaseController;`
- Use request/response helpers: `use Core\Http\Request;` and `use Core\Http\Response;`

Minimal skeleton:

```php
<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;

class ExampleController extends BaseController
{
    public function index(Request $request): Response
    {
        return Response::json(['message' => 'Hello from ExampleController']);
    }
}
```

Notes:

- Prefer named methods for RESTful actions (`index`, `show`, `store`, `update`, `destroy`).
- Use typed Request and Response return types where available; this keeps code consistent with other controllers (see `VehicleController`).

2. Add controller methods (Request/Response pattern)

- Read input using `$request->all()`, `$request->input('name')`, `$request->json()` (for JSON body), or `$request->route('id')` to read route parameters.
- Use helper methods from `BaseController` when available (validation, shared helpers).
- Always return JSON with `Response::json(...)` or errors with `Response::errorJson('message', $status, $data)`.

Example `store` and `show` methods:

```php
public function show(Request $request): Response
{
    $id = $request->route('id');
    if (!$id) {
        return Response::errorJson('Missing id', 400);
    }

    $model = (new \Models\ExampleModel())->find($id);
    if (!$model) {
        return Response::errorJson('Not found', 404);
    }

    return Response::json(['data' => $model]);
}

public function store(Request $request): Response
{
    // If client sends JSON body, merge it into request body with helper
    if (method_exists($request, 'mergeBody') && is_array($request->json())) {
        $request->mergeBody($request->json());
    }

    $payload = $request->all();
    // validate payload (use your model or custom validator)

    try {
        $record = (new \Models\ExampleModel())->create($payload);
    } catch (\Throwable $e) {
        return Response::errorJson('Create failed', 500, ['detail' => $e->getMessage()]);
    }

    return Response::json(['message' => 'Created', 'record' => $record], 201);
}
```

3. Register API routes (link to router/main controller)

- Routes live in `config/routes.php`.
- Add a line that maps an HTTP method + path to the controller method, e.g.:

```php
$router->get('/api/examples', 'Controllers\\Api\\ExampleController@index');
$router->get('/api/examples/{id}', 'Controllers\\Api\\ExampleController@show');
$router->post('/api/examples', 'Controllers\\Api\\ExampleController@store', [
    'Middleware\\AuthMiddleware',
    'Middleware\\CsrfMiddleware',
]);
```

- You can also rely on `PageRouter::registerApiRoutes($router);` if your project uses automatic API route registration for particular files — but explicit entries in `config/routes.php` are the simplest and most visible way for new team members.

4. Protect endpoints with middleware (auth / CSRF / roles)

- Add middleware array as third param to `$router->verb(...)` to require authentication or role checks.
- Example from the project:

```php
$router->post('/api/customer/pickup-requests', 'Controllers\\Api\\Customer\\PickupRequestController@store', [
    'Middleware\\AuthMiddleware',
    'Middleware\\CsrfMiddleware',
    'Middleware\\Roles\\CustomerOnly',
]);
```

- Note: CSRF is enforced by `Middleware\\CsrfMiddleware` for non-safe HTTP methods. For API authentication routes (like `/api/auth/login`) the project intentionally skips CSRF to allow token exchange.

5. Call the API from a view (client-side `fetch` examples)

- Views in this project are PHP files under `src/Views/` and commonly embed JavaScript that calls `/api/...` endpoints using `fetch`.
- If a POST/PUT/DELETE route uses `CsrfMiddleware`, include the CSRF token in the header `X-CSRF-TOKEN` (or `X-CSRF-Token`) or a `_token` form field. Use `csrf_token()` helper to embed token into the rendered page.

GET example (no CSRF required)

```js
// Inside a view or public JS file
async function loadExamples() {
  const res = await fetch("/api/examples");
  if (!res.ok) throw new Error("Failed");
  const json = await res.json();
  console.log(json);
}
```

POST example (with CSRF and JSON body)

In the view PHP file (render the CSRF token):

```php
<?php $csrf = csrf_token(); ?>
<script>
  const csrfToken = <?= json_encode($csrf, JSON_UNESCAPED_UNICODE) ?>;

  async function createExample(payload) {
    const res = await fetch('/api/examples', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify(payload),
    });

    return res.json();
  }
</script>
```

Error handling and UI update pattern

- Check HTTP status codes and `json.success`/`json.error` fields as used by your controller.
- Show friendly messages for 4xx/5xx responses.

Example: Full `ExampleController` implementation

```php
<?php
namespace Controllers\\Api;

use Controllers\\BaseController;
use Core\\Http\\Request;
use Core\\Http\\Response;
use Models\\ExampleModel;

class ExampleController extends BaseController
{
    private ExampleModel $model;

    public function __construct()
    {
        $this->model = new ExampleModel();
    }

    public function index(Request $request): Response
    {
        $items = $this->model->listAll();
        return Response::json(['examples' => $items]);
    }

    public function show(Request $request): Response
    {
        $id = $request->route('id');
        if (!$id) return Response::errorJson('Missing id', 400);

        $item = $this->model->find($id);
        if (!$item) return Response::errorJson('Not found', 404);

        return Response::json(['example' => $item]);
    }

    public function store(Request $request): Response
    {
        if (method_exists($request, 'mergeBody') && is_array($request->json())) {
            $request->mergeBody($request->json());
        }

        $payload = $request->all();

        // minimal validation
        if (empty($payload['name'])) {
            return Response::errorJson('Name is required', 422, ['errors' => ['name' => 'required']]);
        }

        try {
            $record = $this->model->create($payload);
        } catch (\\Throwable $e) {
            return Response::errorJson('Create failed', 500, ['detail' => $e->getMessage()]);
        }

        return Response::json(['message' => 'Created', 'record' => $record], 201);
    }
}
```

Quick checklist (for reviewers / new devs)

- [ ] Create controller file under `src/Controllers/Api`
- [ ] Use `namespace Controllers\\Api;` and `extends BaseController`
- [ ] Implement methods and return `Response::json` or `Response::errorJson`
- [ ] Add route(s) to `config/routes.php` and include middleware as needed
- [ ] Use `csrf_token()` in views for POST/PUT/DELETE and add header `X-CSRF-TOKEN`
- [ ] Test with browser or Postman (the project includes Postman collection)

Further reading

- See `src/Controllers/Api/VehicleController.php` for a production-style example (validation, mergeJsonBody, route id resolution).
- See `config/routes.php` to learn how middleware and role checks are attached to routes.

---

If you'd like, I can:

- create a scaffold `ExampleController.php` file under `src/Controllers/Api/` for you to review, or
- add a sample view `src/Views/examples.php` showing the JS fetch calls and CSRF usage.
