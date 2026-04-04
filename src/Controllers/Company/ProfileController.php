<?php

namespace Controllers\Company;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Core\Uploads\ProfileImageManager;
use Models\User;

/**
 * Handles profile-related updates for Company users, 
 * specifically focusing on profile photo management.
 */
class ProfileController extends BaseController
{
    private ProfileImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ProfileImageManager();
    }

    /**
     * Handle POST requests for profile updates.
     */
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
            return Response::redirect('/company/profile');
        }

        if (!$currentUser) {
            $session->flash('errors', ['Profile not found.']);
            return Response::redirect('/company/profile');
        }

        // Branching based on the button clicked in the form
        if ($request->has('uploadPhoto')) {
            $this->processPhotoUpload($request, $userModel, $currentUser, $userId);
        } elseif ($request->has('removePhoto')) {
            $this->processPhotoRemoval($userModel, $currentUser, $userId);
        } else {
            $session->flash('status', 'No changes were detected.');
        }

        return Response::redirect('/company/profile');
    }

    /**
     * Process profile photo upload using ProfileImageManager.
     */
    private function processPhotoUpload(Request $request, User $userModel, array $currentUser, int $userId): void
    {
        $session = session();

        if (!$request->hasFile('photo')) {
            $session->flash('errors', ['Please choose an image to upload.']);
            $session->flash('active_modal', '#photoUploadModal');
            return;
        }

        $file = $request->file('photo') ?? [];
        $result = $this->imageManager->store($file);

        if (!$result['ok']) {
            $session->flash('errors', [$result['error'] ?? 'Unable to upload the selected image.']);
            $session->flash('active_modal', '#photoUploadModal');
            return;
        }

        $relativePath = $result['path'] ?? null;
        if ($relativePath === null) {
            $session->flash('errors', ['Unable to determine stored image path.']);
            $session->flash('active_modal', '#photoUploadModal');
            return;
        }

        try {
            // Update DB with new path
            $userModel->updateProfileImagePath($userId, $relativePath);
            
            // Delete old physical file if it exists
            $oldPath = $currentUser['profile_image_path'] ?? ($currentUser['profileImagePath'] ?? null);
            $this->imageManager->delete($oldPath);
            
            $session->flash('status', 'Profile photo updated successfully.');
        } catch (\Throwable $e) {
            // Rollback: delete the newly uploaded file if DB update fails
            $this->imageManager->delete($relativePath);
            $session->flash('errors', ['Failed to update profile photo.']);
            $session->flash('active_modal', '#photoUploadModal');
        }
    }

    /**
     * Remove the current profile photo and clear the DB entry.
     */
    private function processPhotoRemoval(User $userModel, array $currentUser, int $userId): void
    {
        $session = session();

        try {
            $userModel->updateProfileImagePath($userId, null);
            
            $oldPath = $currentUser['profile_image_path'] ?? ($currentUser['profileImagePath'] ?? null);
            $this->imageManager->delete($oldPath);
            
            $session->flash('status', 'Profile photo removed.');
        } catch (\Throwable $e) {
            $session->flash('errors', ['Unable to remove profile photo.']);
            $session->flash('active_modal', '#photoUploadModal');
        }
    }
}
