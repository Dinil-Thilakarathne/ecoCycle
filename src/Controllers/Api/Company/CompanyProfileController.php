<?php
namespace Controllers\Api\Company;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\CompanyModel;

class CompanyProfileController extends BaseController
{
    private CompanyModel $model;

    public function __construct()
    {
        $this->model = new CompanyModel();
    }

    public function update(Request $request): Response
    {
        $user = auth();
        if (!$user || empty($user['id'])) {
            return Response::errorJson('Unauthenticated company user.', 401);
        }

        // Demo-only
        $payload = ['name' => 'Demo Company Update'];

        try {
            $this->model->updateProfile((int) $user['id'], $payload);
            return Response::json([
                'success' => true,
                'message' => 'Profile updated',
                'updated' => $payload
            ]);
        } catch (\Throwable $e) {
            return Response::errorJson('Update failed: ' . $e->getMessage(), 500);
        }
    }
}

