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
        $companyId = $request->user()->id;
        $payload = $request->all();

        // Handle profile picture upload
        if (!empty($_FILES['profile_picture']['tmp_name'])) {
            $file = $_FILES['profile_picture'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'uploads/profiles/' . uniqid() . '.' . $ext;

            // if (!move_uploaded_file($file['tmp_name'], $filename)) {
            //     return Response::errorJson('Failed to upload profile picture', 500);
            // }

            $payload['profile_picture'] = $filename;
        }

        try {
            $this->model->updateProfile($companyId, $payload);
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

