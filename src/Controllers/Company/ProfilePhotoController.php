<?php

namespace Controllers\Company;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Core\Uploads\ProfileImageManager;
use Models\User;

class ProfilePhotoController extends BaseController
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

        $userId = (int) ($authUser['id'] ?? 0);
        if ($userId <= 0) {
            return Response::redirect('/login');
        }

        $session = session();
        $userModel = new User();

        try {
            $currentUser = $userModel->findById($userId);
        } catch (\Throwable $e) {
            $session->flash('errors', ['Unable to load profile. Please try again.']);
            return Response::redirect('/company/profile');
        }

        if (!$currentUser) {
            $session->flash('errors', ['Profile not found.']);
            return Response::redirect('/company/profile');
        }

        // ── UPLOAD ────────────────────────────────────────────
        if ($request->has('uploadPhoto')) {

            if (!$request->hasFile('photo')) {
                $session->flash('errors', ['Please choose an image to upload.']);
                return Response::redirect('/company/profile');
            }

            $file = $request->file('photo') ?? [];
            $result = $this->imageManager->store($file);

            if (!$result['ok']) {
                $session->flash('errors', [$result['error'] ?? 'Unable to upload the selected image.']);
                return Response::redirect('/company/profile');
            }

            $relativePath = $result['path'] ?? null;
            if ($relativePath === null) {
                $session->flash('errors', ['Unable to determine stored image path.']);
                return Response::redirect('/company/profile');
            }

            try {
                $userModel->updateProfileImagePath($userId, $relativePath);
                $this->imageManager->delete($currentUser['profile_image_path'] ?? null);
                $session->flash('status', 'Profile photo updated successfully.');
            } catch (\Throwable $e) {
                $this->imageManager->delete($relativePath);
                $session->flash('errors', ['Failed to save profile photo.']);
            }

            return Response::redirect('/company/profile');
        }

        // ── REMOVE ────────────────────────────────────────────
        if ($request->has('removePhoto')) {
            try {
                $userModel->updateProfileImagePath($userId, null);
                $this->imageManager->delete($currentUser['profile_image_path'] ?? null);
                $session->flash('status', 'Profile photo removed.');
            } catch (\Throwable $e) {
                $session->flash('errors', ['Unable to remove profile photo.']);
            }

            return Response::redirect('/company/profile');
        }

        // ── FALLBACK ──────────────────────────────────────────
        $session->flash('errors', ['Invalid request.']);
        return Response::redirect('/company/profile');
    }
}