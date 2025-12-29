<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\WasteCategory;

class WasteManagementController extends BaseController
{
    private WasteCategory $categories;

    public function __construct()
    {
        $this->categories = new WasteCategory();
    }

    // GET /api/waste-categories
    public function index(Request $request): Response
    {
        $records = $this->categories->findAll();

        return Response::json([
            'data' => $records
        ]);
    }

    // POST /api/waste-categories
    public function store(Request $request): Response
    {
        $this->mergeJsonBody($request);

        $payload = $this->validatePayload($request);
        if (isset($payload['errors'])) {
            return Response::errorJson('Validation failed', 422, $payload['errors']);
        }

        try {
            $record = $this->categories->create($payload['data']);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to create category', 500, [
                'detail' => $e->getMessage()
            ]);
        }

        return Response::json([
            'message' => 'Category created',
            'data' => $record
        ]);
    }

    // PUT /api/waste-categories/{id}
    public function update(Request $request): Response
    {
        $id = $this->resolveRouteId($request);
        if (!$id) {
            return Response::errorJson('Category ID is required', 400);
        }

        $this->mergeJsonBody($request);
        $payload = $this->validatePayload($request, true);

        if (isset($payload['errors'])) {
            return Response::errorJson('Validation failed', 422, $payload['errors']);
        }

        $exists = $this->categories->findById((int) $id);
        if (!$exists) {
            return Response::errorJson('Category not found', 404);
        }

        try {
            $this->categories->update((int) $id, $payload['data']);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to update category', 500, [
                'detail' => $e->getMessage()
            ]);
        }

        return Response::json([
            'message' => 'Category updated'
        ]);
    }

    // DELETE /api/waste-categories/{id}
    public function destroy(Request $request): Response
    {
        $id = $this->resolveRouteId($request);
        if (!$id) {
            return Response::errorJson('Category ID is required', 400);
        }

        $exists = $this->categories->findById((int) $id);
        if (!$exists) {
            return Response::errorJson('Category not found', 404);
        }

        try {
            $this->categories->delete((int) $id);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to delete category', 500, [
                'detail' => $e->getMessage()
            ]);
        }

        return Response::json([
            'message' => 'Category deleted'
        ]);
    }

    // GET /api/waste-categories/pricing
    public function pricing(Request $request): Response
    {
        $records = $this->categories->getPricingTiers();

        return Response::json([
            'data' => $records
        ]);
    }

    private function validatePayload(Request $request, bool $isUpdate = false): array
    {
        $data = $request->all();
        $errors = [];

        if (!$isUpdate || isset($data['name'])) {
            if (empty($data['name'])) {
                $errors['name'] = 'Name is required.';
            }
        }

        // Price validation
        if (isset($data['pricePerUnit'])) {
            if (!is_numeric($data['pricePerUnit']) || (float) $data['pricePerUnit'] < 0) {
                $errors['pricePerUnit'] = 'Price must be a positive number.';
            }
        }

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        // Map frontend camelCase to DB snake_case
        $mapped = [];
        if (isset($data['name']))
            $mapped['name'] = $data['name'];
        if (isset($data['unit']))
            $mapped['unit'] = $data['unit'] ?: 'kg';
        if (isset($data['color']))
            $mapped['color'] = $data['color'];
        if (isset($data['pricePerUnit']))
            $mapped['price_per_unit'] = (float) $data['pricePerUnit'];

        return ['data' => $mapped];
    }

    private function mergeJsonBody(Request $request): void
    {
        $json = $request->json();
        if (is_array($json) && method_exists($request, 'mergeBody')) {
            $request->mergeBody($json);
        }
    }

    private function resolveRouteId(Request $request): ?string
    {
        $id = $request->route('id');
        if (!$id)
            $id = $request->get('id');
        return $id ? (string) $id : null;
    }
}
