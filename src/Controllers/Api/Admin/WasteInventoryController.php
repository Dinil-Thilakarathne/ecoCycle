<?php

namespace Controllers\Api\Admin;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\WasteInventory;
use Models\PickupRequest;
use Models\BiddingRound;

/**
 * Admin controller for waste inventory management
 * Handles viewing collected waste and creating bidding rounds
 */
class WasteInventoryController extends BaseController
{
    private WasteInventory $wasteInventory;
    private PickupRequest $pickupRequest;
    private BiddingRound $biddingRound;
    private \Models\Notification $notification;

    public function __construct()
    {
        $this->wasteInventory = new WasteInventory();
        $this->pickupRequest = new PickupRequest();
        $this->biddingRound = new BiddingRound();
        $this->notification = new \Models\Notification();
    }

    /**
     * GET /api/admin/waste-inventory
     * Get current waste inventory status for all categories
     */
    public function index(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return Response::errorJson('Unauthenticated', 401);
        }

        if (!$this->isAdmin($user)) {
            return Response::errorJson('Forbidden', 403);
        }

        try {
            $inventory = $this->wasteInventory->getInventoryStatus();
            $unallocated = $this->pickupRequest->getUnallocatedWaste();
            $stats = $this->wasteInventory->getCollectionStats();
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to load waste inventory', 500, ['detail' => $e->getMessage()]);
        }

        return Response::json([
            'inventory' => $inventory,
            'unallocated' => $unallocated,
            'stats' => $stats,
        ]);
    }

    /**
     * GET /api/admin/waste-inventory/{categoryId}
     * Get detailed inventory for a specific waste category
     */
    public function show(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return Response::errorJson('Unauthenticated', 401);
        }

        if (!$this->isAdmin($user)) {
            return Response::errorJson('Forbidden', 403);
        }

        $categoryId = $this->resolveRouteId($request);
        if ($categoryId === null) {
            return Response::errorJson('Category ID is required', 400);
        }

        try {
            $inventory = $this->wasteInventory->getAvailableByCategory((int) $categoryId);
            if (!$inventory) {
                return Response::errorJson('Category not found or no waste collected', 404);
            }

            $sourcePickups = $this->wasteInventory->getSourcePickups((int) $categoryId, 50);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to load category details', 500, ['detail' => $e->getMessage()]);
        }

        return Response::json([
            'inventory' => $inventory,
            'sourcePickups' => $sourcePickups,
        ]);
    }

    /**
     * POST /api/admin/waste-inventory/create-bidding-round
     * Create a bidding round from available waste
     * 
     * Expected payload:
     * {
     *   "wasteCategoryId": 1,
     *   "quantity": 100.5,
     *   "startingBid": 50.0,
     *   "endTime": "2026-02-10 18:00:00",
     *   "notes": "Optional notes"
     * }
     */
    public function createBiddingRound(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return Response::errorJson('Unauthenticated', 401);
        }

        if (!$this->isAdmin($user)) {
            return Response::errorJson('Forbidden', 403);
        }

        $this->mergeJsonBody($request);
        $payload = $request->all();

        // Validate input
        $errors = [];

        $categoryId = isset($payload['wasteCategoryId']) ? (int) $payload['wasteCategoryId'] : 0;
        if ($categoryId <= 0) {
            $errors['wasteCategoryId'] = 'Valid waste category ID is required';
        }

        $quantity = isset($payload['quantity']) ? (float) $payload['quantity'] : 0;
        if ($quantity <= 0) {
            $errors['quantity'] = 'Quantity must be greater than zero';
        }

        $startingBid = isset($payload['startingBid']) ? (float) $payload['startingBid'] : 0;
        if ($startingBid < 0) {
            $errors['startingBid'] = 'Starting bid cannot be negative';
        }

        $endTime = $payload['endTime'] ?? null;
        if ($endTime !== null && $endTime !== '') {
            $timestamp = strtotime((string) $endTime);
            if ($timestamp === false) {
                $errors['endTime'] = 'Invalid end time format';
            } elseif ($timestamp <= time()) {
                $errors['endTime'] = 'End time must be in the future';
            }
        }

        if (!empty($errors)) {
            return Response::errorJson('Validation failed', 422, $errors);
        }

        try {
            // Check if sufficient waste is available
            if (!$this->wasteInventory->canAllocate($categoryId, $quantity)) {
                return Response::errorJson('Insufficient waste available', 422, [
                    'quantity' => 'Not enough waste available for this category'
                ]);
            }

            // Get source pickup IDs for this allocation
            $sourcePickupIds = $this->pickupRequest->getUnallocatedPickupIds($categoryId, $quantity);

            // Generate lot ID
            $lotId = $this->biddingRound->generateLotId();

            // Prepare bidding round payload
            $roundPayload = [
                'lot_id' => $lotId,
                'waste_category_id' => $categoryId,
                'quantity' => $quantity,
                'unit' => $payload['unit'] ?? 'kg',
                'starting_bid' => $startingBid,
                'current_highest_bid' => 0.0,
                'status' => 'active',
                'end_time' => $endTime ?? null,
                'notes' => $payload['notes'] ?? null,
            ];

            // Create bidding round with source tracking
            $round = $this->biddingRound->createFromCollectedWaste($roundPayload, $sourcePickupIds);

            // Notify companies about new bidding round
            $this->notification->create([
                'type' => 'bidding_round_created',
                'title' => 'New Bidding Round Available',
                'message' => "New bidding round created: {$lotId}. Quantity: {$quantity} kg",
                'recipient_group' => 'company',
                'status' => 'pending'
            ]);

        } catch (\Throwable $e) {
            return Response::errorJson('Failed to create bidding round', 500, ['detail' => $e->getMessage()]);
        }

        return Response::json([
            'message' => 'Bidding round created successfully',
            'round' => $round,
        ], 201);
    }

    /**
     * GET /api/admin/waste-inventory/stats
     * Get waste collection statistics
     */
    public function stats(Request $request): Response
    {
        $user = auth();
        if (!$user) {
            return Response::errorJson('Unauthenticated', 401);
        }

        if (!$this->isAdmin($user)) {
            return Response::errorJson('Forbidden', 403);
        }

        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        try {
            $stats = $this->wasteInventory->getCollectionStats($startDate, $endDate);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to load statistics', 500, ['detail' => $e->getMessage()]);
        }

        return Response::json([
            'stats' => $stats,
        ]);
    }

    private function isAdmin(array $user): bool
    {
        $role = strtolower((string) ($user['role'] ?? $user['role_name'] ?? ''));
        return in_array($role, ['admin', 'manager'], true);
    }

    private function mergeJsonBody(Request $request): void
    {
        $json = $request->json();
        if (!is_array($json)) {
            return;
        }

        if (method_exists($request, 'mergeBody')) {
            $request->mergeBody($json);
        }
    }

    private function resolveRouteId(Request $request): ?string
    {
        $id = $request->route('id');
        if ($id === null || $id === '') {
            $id = $request->get('id');
        }

        if ($id === null || $id === '') {
            return null;
        }

        return (string) $id;
    }
}
