<?php
namespace Controllers\Api\Company;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\CompanyModel;
use Models\User;
class CompanyProfileController extends BaseController
{
    private CompanyModel $model;

    public function __construct()
    {
        $this->model = new CompanyModel();
    }

    public function updateProfile(Request $request): Response
    {
        $user = auth();
        if (!$user || empty($user['id'])) {
            return Response::errorJson('Unauthenticated company user.', 401);
        }

        $payload = [
            'name' => trim($request->input('name')),
            'email' => trim($request->input('email')),
            'phone' => trim($request->input('phone')),
            'address' => trim($request->input('address')),
            'metadata' => [
                'type' => trim($request->input('type')),
                'reg_number' => trim($request->input('reg_number')),
                'description' => trim($request->input('description')),
                'website' => trim($request->input('website')),
                'waste_types' => array_map('trim', explode(',', $request->input('waste_types')))
            ]
        ];

        try {
            $this->model->updateProfile((int) $user['id'], $payload);

            // Save success message for next request
            session()->flash('success', 'Profile updated successfully');

            // Redirect user back to profile page
            return Response::redirect('/company/profile');


        } catch (\Throwable $e) {
            return Response::errorJson('Update failed: ' . $e->getMessage(), 500);
        }
    }


    public function deleteProfile(Request $request): Response
    {
        $user = auth();
        if (!$user || empty($user['id'])) {
            return Response::errorJson('Unauthenticated company user.', 401);
        }

        try {
            $this->model->deleteProfile((int) $user['id']);

            // Save success message for next request
            session()->flash('success', 'Profile deleted successfully');

            // Redirect user back to Signup page
            return Response::redirect('/register');


        } catch (\Throwable $e) {
            return Response::errorJson('Delete failed: ' . $e->getMessage(), 500);
        }
    }

    public function createBankDetails(Request $request): Response
    {
        $user = auth();
        if (!$user || empty($user['id'])) {
            return Response::errorJson('Unauthenticated company user.', 401);
        }

        $payload = [
            'bank_name'=> trim($request->input('bank_name')),
            'bank_account_number'=> trim($request->input('bank_account_number')),
            'bank_account_name'=> trim($request->input('bank_account_name')),
            'bank_branch'=> trim($request->input('bank_branch')),
        ];

        try {
            $this->model->createBankDetails((int) $user['id'], $payload);

            session()->flash('success','Bank details updated successfully');

            return Response::redirect('/company/profile');

        } catch (\Throwable $e) {
            return Response::errorJson('Update failed: ' . $e->getMessage(), 500);
        }    
    }

    public function changePassword(Request $request): Response
    {
        $user = auth();

        if (!$user || empty($user['id'])) {
            return Response::errorJson('Unauthenticated company user.', 401);
        }

        // Get input fields
        $password = trim($request->input('password') ?? '');
        $passwordConfirm = trim(
            $request->input('confirm_password') ??
            $request->input('password_confirm') ??
            $request->input('password_confirmation') ??
            ''
        );

        // Validation
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
            return Response::redirect('/company/profile');

        } catch (\Throwable $e) {
            return Response::errorJson('Password change failed: ' . $e->getMessage(), 500);
        }
    }



}

