<?php
namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\profileModel;

class profileController extends BaseController
{
    private profileModel $model;

    public function __construct()
    {
        $this->model = new profileModel();
    }

    public function updateProfile(Request $request): Response
    {
        $user = auth();

        if (!$user || empty($user['id']) || empty($user['role'])) {
            return Response::errorJson('Unauthenticated user.', 401);
        }

        $role = strtolower(trim($user['role'])); 

        if (!in_array($role, ['customer', 'company', 'collector'])) {
        return Response::json(['message' => 'Invalid user type'], 403);
        }
        // Build payload based on role
        $payload = $this->buildPayload($role, $request);


        try {
            $this->model->updateProfile((int)$user['id'], $payload);
            
            $updatedUser = $this->model->getUserById($user['id']);
            session()->set('auth', $updatedUser);
            
            session()->flash('success', 'Profile updated successfully!');

            return Response::redirect("/{$role}/profile");

        } catch (\Throwable $e) {
            error_log("Profile update failed for {$role} ID {$user['id']}: " . $e->getMessage());
            return Response::json(['message' => 'Update failed. Please try again.'], 500);
        }
    }

    private function buildPayload(string $role, Request $request): array
    {
        // Common fields for ALL roles
        $payload = [
            'name'    => trim($request->input('name', '')),
            'email'   => trim(strtolower($request->input('email', ''))),
            'phone'   => trim($request->input('phone', '')),
            'address' => trim($request->input('address', '')),
        ];

        // Role-specific metadata
        switch ($role) {
            case 'company':
                $payload['metadata'] = [
                    'companyName' => trim($request->input('companyName', '')),
                    'type'         => trim($request->input('type', '')),
                    'reg_number'   => trim($request->input('reg_number', '')),
                    'description'  => trim($request->input('description', '')),
                    'website'     => trim($request->input('website', '')),
                    'waste_types' => $this->parseCommaList($request->input('waste_types')),
                ];
                break;

            case 'collector':
                $payload['metadata'] = [
                    'vehiclePreference'     => trim($request->input('vehiclePreference', '')),
                    'serviceAreas'    => $this->parseServiceAreas($request->input('serviceArea')),
                    'licenseNumber'   => trim($request->input('licenseNumber', '')),
                ];
                break;

            case 'customer':
                $payload['metadata'] = [
                    'postalCode'   => trim($request->input('postalCode', '')),    
                ];
                break;
        }

        return $payload;
    }

    private function parseCommaList($input): array
    {
        if (!$input) return [];
        return array_filter(array_map('trim', explode(',', $input)));
    }

    private function parseServiceAreas($input): array
    {
        if (empty($input)) return [];
        return array_filter(array_map('trim', explode(',', (string)$input)));
    }

    public function deleteProfile(Request $request): Response
    {
        $user = auth();

        if (!$user || empty($user['id'])) {
            return Response::errorJson('Unauthenticated user.', 401);
        }

        try {
            $this->model->deleteProfile((int)$user['id']);

            session()->flash('success', 'Profile deleted successfully.');

            return Response::redirect('/');

        } catch (\Throwable $e) {
            return Response::errorJson('Profile deletion failed: ' . $e->getMessage(), 500);
        }

    }

    public function updateBankDetails(Request $request): Response
    {
        $user = auth();

        if (!$user || empty($user['id']) || empty($user['role'])) {
            return Response::errorJson('Unauthenticated user.', 401);
        }

        $role = strtolower(trim($user['role']));

        if (!in_array($role, ['customer', 'company', 'collector'])) {
        return Response::errorJson('Unauthorized action', 403);
        }

        $payload = [
            'bank_name'=> trim($request->input('bank_name')),
            'bank_account_number'=> trim($request->input('bank_account_number')),
            'bank_account_name'=> trim($request->input('bank_account_name')),
            'bank_branch'=> trim($request->input('bank_branch')),
        ];

        try {
            $this->model->updateBankDetails((int) $user['id'], $payload);

            session()->flash('success','Bank details updated successfully');

            return Response::redirect("/{$role}/profile");

        } catch (\Throwable $e) {
            return Response::errorJson('Update failed: ' . $e->getMessage(), 500);
        }    
    }

    public function changePassword(Request $request): Response
    {
        $user = auth();

        if (!$user || empty($user['id']) || empty($user['role'])) {
            return Response::errorJson('Unauthenticated user.', 401);
        }

        $role = strtolower(trim($user['role']));

        // Get input fields
        $currentPassword = trim(
            $request->input('currentPassword') ??
            $request->input('current_password') ??
            ''
        );
        $password = trim($request->input('password') ?? '');
        $passwordConfirm = trim(
            $request->input('confirm_password') ??
            $request->input('password_confirm') ??
            $request->input('password_confirmation') ??
            ''
        );

        // Validation
        if ($currentPassword !== '') {
            return Response::errorJson('Old password is not required for password changes.', 422);
        }

        if ($password === '' || $passwordConfirm === '') {
            return Response::errorJson('Both password fields are required', 422);
        }

        if (strlen($password) < 6) {
            return Response::errorJson('Password must be at least 6 characters', 422);
        }

        if ($password !== $passwordConfirm) {
            return Response::errorJson('Passwords do not match', 422);
        }

        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $this->model->updatePassword((int)$user['id'], [
                'password_hash' => $hashed
            ]);

            session()->flash('success', 'Password changed successfully');
            return Response::redirect("/{$role}/profile");

        } catch (\Throwable $e) {
            return Response::errorJson('Password change failed: ' . $e->getMessage(), 500);
        }
    }


}

