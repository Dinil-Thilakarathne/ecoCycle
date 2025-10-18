<?php

namespace Controllers\Customer;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Core\Uploads\ProfileImageManager;
use Models\User;

class ProfileController extends BaseController
{
    private ProfileImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ProfileImageManager();
    }

    public function update(Request $request): Response
    {
        $authUser = auth();
        if (!$authUser) {
            return Response::redirect('/login');
        }

        $userId = (int) $authUser['id'];
        $session = session();
        $userModel = new User();

        try {
            $currentUser = $userModel->findById($userId);
        } catch (\Throwable $e) {
            $session->flash('errors', ['Unable to load your profile. Please try again later.']);
            return Response::redirect('/customer/profile');
        }

        if (!$currentUser) {
            $session->flash('errors', ['Profile not found.']);
            return Response::redirect('/customer/profile');
        }

        if ($request->has('uploadPhoto')) {
            $this->processPhotoUpload($request, $userModel, $currentUser, $userId);
        } elseif ($request->has('removePhoto')) {
            $this->processPhotoRemoval($userModel, $currentUser, $userId);
        } elseif ($request->has('saveProfile')) {
            $this->processProfileSave($request, $userModel, $currentUser, $userId);
        } elseif ($request->has('updatePassword')) {
            $this->processPasswordChange($request, $userModel, $currentUser, $userId);
        } else {
            $session->flash('status', 'No changes were detected.');
        }

        return Response::redirect('/customer/profile');
    }

    private function processPhotoUpload(Request $request, User $userModel, array $currentUser, int $userId): void
    {
        $session = session();

        if (!$request->hasFile('photo')) {
            $session->flash('errors', ['Please choose an image to upload.']);
            return;
        }

        $file = $request->file('photo') ?? [];
        $result = $this->imageManager->store($file);

        if (!$result['ok']) {
            $session->flash('errors', [$result['error'] ?? 'Unable to upload the selected image.']);
            return;
        }

        $relativePath = $result['path'] ?? null;
        if ($relativePath === null) {
            $session->flash('errors', ['Unable to determine stored image path.']);
            return;
        }

        try {
            $userModel->updateProfileImagePath($userId, $relativePath);
            $this->imageManager->delete($currentUser['profile_image_path'] ?? null);
            $session->flash('status', 'Profile photo updated successfully.');
        } catch (\Throwable $e) {
            $this->imageManager->delete($relativePath);
            $session->flash('errors', ['Failed to update profile photo.']);
        }
    }

    private function processPhotoRemoval(User $userModel, array $currentUser, int $userId): void
    {
        $session = session();

        try {
            $userModel->updateProfileImagePath($userId, null);
            $this->imageManager->delete($currentUser['profile_image_path'] ?? null);
            $session->flash('status', 'Profile photo removed.');
        } catch (\Throwable $e) {
            $session->flash('errors', ['Unable to remove profile photo.']);
        }
    }

    private function processProfileSave(Request $request, User $userModel, array $currentUser, int $userId): void
    {
        $session = session();

        $firstName = trim((string) $request->input('firstName'));
        $lastName = trim((string) $request->input('lastName'));
        $email = trim((string) $request->input('email'));
        $phone = trim((string) $request->input('phone'));
        $address = trim((string) $request->input('address'));
        $postalCode = trim((string) $request->input('postalCode'));
        $bankAccount = trim((string) $request->input('bankAccount'));

        $errors = [];

        if ($firstName === '' || $lastName === '' || $email === '' || $phone === '' || $address === '' || $postalCode === '' || $bankAccount === '') {
            $errors[] = 'All fields are required.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }

        if ($phone !== '' && !preg_match('/^0\d{9}$/', $phone)) {
            $errors[] = 'Phone number must start with 0 and be exactly 10 digits.';
        }

        if ($postalCode !== '' && !preg_match('/^\d{1,5}$/', $postalCode)) {
            $errors[] = 'Postal code must be numeric and up to 5 digits.';
        }

        try {
            if ($email !== '' && $userModel->emailExists($email, $userId)) {
                $errors[] = 'Another account already uses that email address.';
            }
        } catch (\Throwable $e) {
            $errors[] = 'Unable to verify email uniqueness. Please try again.';
        }

        $oldInput = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'postalCode' => $postalCode,
            'bankAccount' => $bankAccount,
        ];

        if (!empty($errors)) {
            $session->flash('errors', $errors);
            $session->flash('old', $oldInput);
            return;
        }

        $metadata = is_array($currentUser['metadata'] ?? null) ? $currentUser['metadata'] : [];
        $metadata['firstName'] = $firstName;
        $metadata['lastName'] = $lastName;
        $metadata['postalCode'] = $postalCode;
        $metadata['bankAccount'] = $bankAccount;

        $updateData = [
            'name' => trim($firstName . ' ' . $lastName),
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'metadata' => $metadata,
        ];

        try {
            $userModel->updateUser($userId, $updateData);
            $session->flash('status', 'Profile details updated.');
        } catch (\Throwable $e) {
            $session->flash('errors', ['Failed to update your profile.']);
            $session->flash('old', $oldInput);
        }
    }

    private function processPasswordChange(Request $request, User $userModel, array $currentUser, int $userId): void
    {
        $session = session();

        $currentPassword = (string) $request->input('currentPassword');
        $newPassword = (string) $request->input('newPassword');
        $confirmPassword = (string) $request->input('confirmPassword');

        $errors = [];

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $errors[] = 'All password fields are required.';
        }

        if ($newPassword !== '' && strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters long.';
        }

        if ($newPassword !== '' && $newPassword !== $confirmPassword) {
            $errors[] = 'New password confirmation does not match.';
        }

        $storedHash = $currentUser['password_hash'] ?? '';
        $currentValid = false;

        if ($storedHash !== '') {
            if (preg_match('/^\$2y\$/', $storedHash)) {
                $currentValid = password_verify($currentPassword, $storedHash);
            } else {
                $currentValid = hash_equals($storedHash, $currentPassword);
            }
        }

        if (!$currentValid) {
            $errors[] = 'Current password is incorrect.';
        }

        if (!empty($errors)) {
            $session->flash('errors', $errors);
            return;
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

        try {
            $userModel->updateUser($userId, ['password_hash' => $newHash]);
            $session->flash('status', 'Password updated successfully.');
        } catch (\Throwable $e) {
            $session->flash('errors', ['Unable to update password. Please try again.']);
        }
    }
}
