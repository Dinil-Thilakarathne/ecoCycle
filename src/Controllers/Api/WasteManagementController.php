<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\WasteCategory;
use Services\WasteCategory\WasteCategoryService;

class WasteCategoryController extends BaseController
{
    private WasteCategory $categories;
    private WasteCategoryService $service;

    public function __construct()
    {
        $this->categories = new WasteCategory();
        $this->service = new WasteCategoryService($this->categories);
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
            $record = $this->service->createCategory($payload['data']);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to create category', 500, ['detail' => $e->getMessage()]);
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

        try {
            $record = $this->service->updateCategory((int)$id, $payload['data']);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to update category', 500, ['detail' => $e->getMessage()]);
        }

        return Response::json([
            'message' => 'Category updated',
            'data' => $record
        ]);
    }

    // DELETE /api/waste-categories/{id}
    public function destroy(Request $request): Response
    {
        $id = $this->resolveRouteId($request);
        if (!$id) {
            return Response::errorJson('Category ID is required', 400);
        }

        try {
            $this->service->deleteCategory((int)$id);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to delete category', 500, ['detail' => $e->getMessage()]);
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

        if (!$isUpdate || isset($data['description'])) {
            if (empty($data['description'])) {
                $errors['description'] = 'Description is required.';
            }
        }

        if (!$isUpdate || isset($data['basePrice'])) {
            if (!isset($data['basePrice']) || (float)$data['basePrice'] <= 0) {
                $errors['basePrice'] = 'Base price must be greater than zero.';
            }
        }

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        return ['data' => [
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'basePrice' => isset($data['basePrice']) ? (float)$data['basePrice'] : null,
        ]];
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
        if (!$id) $id = $request->get('id');
        return $id ? (string)$id : null;
    }
}
