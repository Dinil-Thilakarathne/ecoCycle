<?php

namespace Controllers\Api;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\BiddingRound;
use Models\WasteCategory;
use Models\WasteInventory;
use Models\Payment;

class BiddingController extends BaseController
{
    private BiddingRound $rounds;
    private WasteCategory $categories;
    private WasteInventory $inventory;
    private \Models\Notification $notification;

    public function __construct()
    {
        $this->rounds = new BiddingRound();
        $this->categories = new WasteCategory();
        $this->inventory = new WasteInventory();
        $this->notification = new \Models\Notification();
    }

    /**
     * Explicitly expire a bidding round by ID (No time dependency).
     * POST /api/bidding/{id}/expire
     */
    public function expire(Request $request): Response
    {
        $id = $this->resolveRouteId($request);
        if ($id === null) {
            return Response::errorJson('Bidding round id is required', 400);
        }

        try {
            $updated = $this->rounds->expireRoundById($id);
            if (!$updated) {
                return Response::errorJson('Failed to expire round: possibly not found or not active.', 422);
            }

            return Response::json([
                'success' => true,
                'message' => 'Bidding round has been successfully closed.',
            ]);
        } catch (\Throwable $e) {
            return Response::errorJson('Internal server error during expiry', 500, ['detail' => $e->getMessage()]);
        }
    }

    public function index(Request $request): Response
    {
        try {
            $rounds = $this->rounds->listAll();
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to load bidding rounds', 500, ['detail' => $e->getMessage()]);
        }

        return Response::json([
            'success' => true,
            'rounds' => $rounds,
        ]);
    }

    public function store(Request $request): Response
    {
        $this->mergeJsonBody($request);
        $validation = $this->validateStorePayload($request);

        if (isset($validation['errors'])) {
            return Response::errorJson('Validation failed', 422, $validation['errors']);
        }

        try {
            $round = $this->rounds->createRound($validation['payload']);

            // Trigger Notification to Companies
            if ($round) {
                $this->notification->create([
                    'type' => 'bidding_round_opened',
                    'title' => 'New Bidding Round',
                    'message' => "New bidding round available: {$round['quantity']}{$round['unit']} of {$round['wasteCategory']}",
                    'recipient_group' => 'company',
                    'status' => 'pending'
                ]);
            }
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to create bidding round', 500, ['detail' => $e->getMessage()]);
        }

        return Response::json([
            'success' => true,
            'message' => 'Bidding round created successfully',
            'round' => $round,
        ], 201);
    }

    public function show(Request $request): Response
    {
        $id = $this->resolveRouteId($request);
        if ($id === null) {
            return Response::errorJson('Bidding round id is required', 400);
        }

        try {
            $round = $this->rounds->findById($id);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to load bidding round', 500, ['detail' => $e->getMessage()]);
        }

        if (!$round) {
            return Response::errorJson('Bidding round not found', 404);
        }

        $bids = $this->rounds->getBids($id);

        // Enrich with invoice data for awarded rounds
        $invoice = null;
        if (
            in_array(strtolower((string) ($round['status'] ?? '')), ['awarded', 'completed'], true) &&
            !empty($round['leadingCompanyId'])
        ) {
            try {
                $paymentModel = new Payment();
                $invoices = $paymentModel->listCompanyInvoices((int) $round['leadingCompanyId'], 5);
                // Find the invoice whose notes reference this lot
                $lotId = $round['lotId'] ?? $id;
                foreach ($invoices as $inv) {
                    $notes = strtolower((string) ($inv['notes'] ?? ''));
                    if (str_contains($notes, strtolower((string) $lotId))) {
                        $invoice = [
                            'id' => $inv['id'],
                            'status' => $inv['status'],
                            'txnId' => $inv['txnId'] ?? null,
                            'amount' => $inv['amount'] ?? null,
                        ];
                        break;
                    }
                }
            } catch (\Throwable $e) {
                // Non-critical — just skip invoice data
            }
        }

        return Response::json([
            'success' => true,
            'round' => $round,
            'bids' => $bids,
            'invoice' => $invoice,
        ]);
    }

    public function update(Request $request): Response
    {
        $id = $this->resolveRouteId($request);
        if ($id === null) {
            return Response::errorJson('Bidding round id is required', 400);
        }

        try {
            $existing = $this->rounds->findById($id);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to load bidding round', 500, ['detail' => $e->getMessage()]);
        }

        if (!$existing) {
            return Response::errorJson('Bidding round not found', 404);
        }

        if (strtolower((string) ($existing['status'] ?? '')) !== 'active') {
            return Response::errorJson('Only active bidding rounds can be updated', 422);
        }

        // Prevent updating if there are existing bids or a leading company has been set
        $roundId = (string) ($existing['id'] ?? '');
        $hasLeadingCompany = $this->rounds->hasLeadingCompanyById($roundId);
        $hasBids = $this->rounds->hasBids($roundId);

        if ($hasLeadingCompany || $hasBids) {
            return Response::errorJson('Cannot edit bidding round: bids already placed or a leading company exists', 422);
        }

        $this->mergeJsonBody($request);

        // Restrict allowed update fields server-side: only quantity, startingBid and endTime
        // Normalize incoming keys that may be camelCase from the client
        $allowed = ['quantity', 'startingBid', 'endTime', 'starting_bid', 'end_time'];
        $filtered = [];
        foreach ($request->json() ?? [] as $k => $v) {
            if (in_array($k, $allowed, true)) {
                $filtered[$k] = $v;
            }
        }

        // Merge filtered values back into request body for validation helpers
        if (!empty($filtered) && method_exists($request, 'mergeBody')) {
            $request->mergeBody($filtered);
        }

        $validation = $this->validateUpdatePayload($request, $existing);
        if (isset($validation['errors'])) {
            return Response::errorJson('Validation failed', 422, $validation['errors']);
        }

        $payload = $validation['payload'];
        if (empty($payload)) {
            return Response::json([
                'success' => true,
                'message' => 'No changes detected',
                'round' => $existing,
            ]);
        }

        try {
            $round = $this->rounds->updateRound($id, $payload);

            // Notification: Bidding Round Updated
            if ($round) {
                // Get companies that have already bid
                $participants = $this->rounds->getParticipatingCompanies($id);
                if (!empty($participants)) {
                    $categoryName = $round['wasteCategory'] ?? 'Unknown Category';
                    $lotId = $round['lotId'] ?? $id;

                    // Format recipients like 'company:123'
                    $recipients = array_map(fn($cid) => "company:$cid", $participants);

                    $this->notification->create([
                        'type' => 'info',
                        'title' => 'Bidding Round Updated',
                        'message' => "The details for bidding round {$categoryName} ({$lotId}) have been updated. Please review the changes.",
                        'recipients' => $recipients,
                        'status' => 'pending'
                    ]);
                }
            }
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to update bidding round', 500, ['detail' => $e->getMessage()]);
        }

        if (!$round) {
            return Response::errorJson('Bidding round not found', 404);
        }

        return Response::json([
            'success' => true,
            'message' => 'Bidding round updated',
            'round' => $round,
        ]);
    }

    public function destroy(Request $request): Response
    {
        $id = $this->resolveRouteId($request);
        if ($id === null) {
            return Response::errorJson('Bidding round id is required', 400);
        }

        try {
            $existing = $this->rounds->findById($id);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to load bidding round', 500, ['detail' => $e->getMessage()]);
        }

        if (!$existing) {
            return Response::errorJson('Bidding round not found', 404);
        }

        $roundId = (string) ($existing['id'] ?? '');
        $hasLeadingCompany = $this->rounds->hasLeadingCompanyById($roundId);
        $hasBids = $this->rounds->hasBids($roundId);
        $status = strtolower((string) ($existing['status'] ?? ''));

        // Allow deletion when:
        //   (a) Round is 'active' and has no bids (normal cancel flow), OR
        //   (b) Round is 'completed' or 'cancelled' and no bids were ever placed
        //       (admin cleanup of expired no-bid lots)
        $isNoBidFinished = in_array($status, ['completed', 'cancelled'], true)
            && !$hasLeadingCompany
            && !$hasBids;

        if (!$isNoBidFinished) {
            // Block non-active rounds that had bids (can't undo awarded/paid lots)
            if ($status !== 'active') {
                return Response::errorJson('Cannot delete a round that has received bids or has been awarded', 422);
            }

            // Block active rounds that already have bids
            if ($hasLeadingCompany || $hasBids) {
                return Response::errorJson('Cannot cancel bidding round: companies have already placed bids or a leading company exists', 422);
            }
        }

        $this->mergeJsonBody($request);
        $reason = $this->extractString($request, 'reason');

        try {
            $round = $this->rounds->cancelRound($id, $reason);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to cancel bidding round', 500, ['detail' => $e->getMessage()]);
        }

        if (!$round) {
            return Response::errorJson('Bidding round not found', 404);
        }



        // Trigger Notification to Companies about Cancellation
        if ($round) {
            $this->notification->create([
                'type' => 'bidding_round_cancelled',
                'title' => 'Bidding Round Cancelled',
                'message' => "Bidding round for Lot {$round['lot_id']} has been cancelled.",
                'recipient_group' => 'company',
                'status' => 'pending'
            ]);
        }

        return Response::json([
            'success' => true,
            'message' => 'Bidding round cancelled',
            'round' => $round,
        ]);
    }

    public function approve(Request $request): Response
    {
        $this->mergeJsonBody($request);
        $id = $this->extractString($request, 'biddingId');
        if ($id === null) {
            return Response::errorJson('Bidding round id is required', 422, ['biddingId' => 'This field is required.']);
        }

        $companyId = $this->extractInt($request, 'companyId');

        try {
            $round = $this->rounds->approveRound($id, $companyId);

            if ($round && !empty($round['leadingCompanyId']) && !empty($round['currentHighestBid'])) {
                $amount = (float) $round['currentHighestBid'];
                if ($amount > 0) {
                    $paymentService = new \Services\Payment\PaymentService();
                    $paymentService->createManualPayment([
                        'type' => 'payment', // Incoming money from Company
                        'recipientId' => (int) $round['leadingCompanyId'], // The Company is the "User" associated with this record
                        'amount' => $amount,
                        'status' => 'pending', // Pending invoice
                        'notes' => "Invoice for Winning Bid on Lot {$round['lotId']}",
                        'txnId' => "INV-{$round['lotId']}-" . time()
                    ]);
                }

                // Notify Winning Company
                $this->notification->create([
                    'type' => 'bid_won',
                    'title' => 'Bid Won!',
                    'message' => "Congratulations! You have won the bid for {$round['wasteCategory']} (Lot {$round['lotId']}). Please check your invoices.",
                    'recipients' => ['company:' . $companyId],
                    'status' => 'pending'
                ]);

                // Notify Losing Bidders
                $losingBidders = $this->rounds->getLosingBidders($id, $companyId);
                if (!empty($losingBidders)) {
                    $loserRecipients = array_map(fn($cid) => "company:$cid", $losingBidders);

                    $this->notification->create([
                        'type' => 'info',
                        'title' => 'Bidding Round Ended',
                        'message' => "The bidding round for {$round['wasteCategory']} (Lot {$round['lotId']}) has ended. Another bid was accepted.",
                        'recipients' => $loserRecipients,
                        'status' => 'pending'
                    ]);
                }
            }
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to approve bidding round', 500, ['detail' => $e->getMessage()]);
        }

        if (!$round) {
            return Response::errorJson('Bidding round not found', 404);
        }

        return Response::json([
            'success' => true,
            'message' => 'Bidding round approved and invoice generated',
            'round' => $round,
        ]);
    }

    public function reject(Request $request): Response
    {
        $this->mergeJsonBody($request);
        $id = $this->extractString($request, 'biddingId');
        if ($id === null) {
            return Response::errorJson('Bidding round id is required', 422, ['biddingId' => 'This field is required.']);
        }

        $reason = $this->extractString($request, 'reason');

        try {
            $round = $this->rounds->rejectRound($id, $reason);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to reject bidding round', 500, ['detail' => $e->getMessage()]);
        }

        if (!$round) {
            return Response::errorJson('Bidding round not found', 404);
        }



        // Trigger Notification to Companies about Rejection (Cancellation)
        if ($round) {
            $this->notification->create([
                'type' => 'bidding_round_cancelled',
                'title' => 'Bidding Round Cancelled',
                'message' => "Bidding round for Lot {$round['lotId']} has been cancelled.", // Use field names correctly based on model return
                'recipient_group' => 'company',
                'status' => 'pending'
            ]);
        }

        return Response::json([
            'success' => true,
            'message' => 'Bidding round rejected',
            'round' => $round,
        ]);
    }

    private function mergeJsonBody(Request $request): void
    {
        $json = $request->json();
        if (!is_array($json) || !method_exists($request, 'mergeBody')) {
            return;
        }

        $request->mergeBody($json);
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

        return trim((string) $id);
    }

    private function validateStorePayload(Request $request): array
    {
        $errors = [];

        // Lot ID will be generated by the system if not provided. If provided, still validate.
        $lotId = $this->extractString($request, 'lotId');
        if ($lotId !== null && $lotId !== '') {
            if (strlen($lotId) > 64) {
                $errors['lotId'] = 'Lot ID must be 64 characters or fewer.';
            } elseif ($this->rounds->existsByLotId($lotId)) {
                $errors['lotId'] = 'Lot ID already exists.';
            }
        } else {
            // generate one later when building payload
            $lotId = null;
        }

        $categoryId = null;
        $categoryData = null; // Store full category data for pricing

        $categoryName = $this->extractString($request, 'wasteCategory');
        $categoryIdInput = $this->extractInt($request, 'wasteCategoryId');

        if ($categoryIdInput !== null) {
            $categoryId = $categoryIdInput;
            $categoryData = $this->categories->findById($categoryId);
        } elseif ($categoryName !== null && $categoryName !== '') {
            $categoryData = $this->categories->findByName($categoryName);
            if ($categoryData) {
                $categoryId = $categoryData['id'];
            }
        }

        if ($categoryId === null || !$categoryData) {
            $errors['wasteCategory'] = 'Valid waste category is required.';
        }

        $quantity = $this->extractNumeric($request, 'quantity');
        if ($quantity === null || $quantity <= 0) {
            $errors['quantity'] = 'Quantity must be greater than zero.';
        }

        $unit = $this->extractString($request, 'unit');
        if ($unit === null || $unit === '') {
            $unit = 'kg';
        }

        if ($categoryId && $quantity > 0) {
            // Check availability using WasteInventory model (consistent with Admin Dashboard)
            if (!$this->inventory->canAllocate((int) $categoryId, $quantity)) {
                $avail = $this->inventory->getAvailableByCategory((int) $categoryId);
                $availableQty = $avail ? $avail['availableQuantity'] : 0;

                $errors['quantity'] = 'Quantity exceeds available collected waste (Available: ' . number_format($availableQty, 2) . ').';
            }
        }

        $allowedUnits = ['kg', 'tons', 'tonnes', 'lb'];
        if (!in_array(strtolower($unit), $allowedUnits, true)) {
            $errors['unit'] = 'Invalid unit provided.';
        }

        $startingBid = $this->extractNumeric($request, 'startingBid');

        // Smart Default for Starting Bid
        if ($startingBid === null && $quantity > 0 && $categoryData) {
            $pricePerUnit = (float) ($categoryData['pricePerUnit'] ?? 0);
            $markup = (float) ($categoryData['markupPercentage'] ?? 0);

            // Formula: BaseCost + Markup
            $baseCost = $quantity * $pricePerUnit;
            $startingBid = $baseCost * (1 + ($markup / 100));
        }

        if ($startingBid === null || $startingBid < 0) {
            $errors['startingBid'] = 'Starting bid must be zero or greater, or auto-calculable.';
        }

        $endTimeRaw = $this->extractString($request, 'endTime');
        if ($endTimeRaw === null || $endTimeRaw === '') {
            $errors['endTime'] = 'End time is required.';
        }

        $endTime = null;
        if (!isset($errors['endTime'])) {
            $timestamp = strtotime($endTimeRaw);
            if ($timestamp === false) {
                $errors['endTime'] = 'End time is invalid.';
            } elseif ($timestamp <= time()) {
                $errors['endTime'] = 'End time must be in the future.';
            } else {
                $endTime = date('Y-m-d H:i:s', $timestamp);
            }
        }

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        $now = time();
        $status = ($endTime !== null && strtotime($endTime) > $now) ? 'active' : 'completed';

        // If lotId is not supplied, generate a new one
        if ($lotId === null) {
            $lotId = $this->rounds->generateLotId();
        }

        return [
            'payload' => [
                'lot_id' => $lotId,
                'waste_category_id' => $categoryId,
                'quantity' => round($quantity, 2),
                'unit' => strtolower($unit) === 'tonnes' ? 'tons' : strtolower($unit),
                'starting_bid' => round((float) $startingBid, 2),
                'current_highest_bid' => 0.0,
                'status' => $status,
                'end_time' => $endTime,
            ],
        ];
    }

    private function validateUpdatePayload(Request $request, array $existing): array
    {
        $errors = [];
        $payload = [];

        if ($request->has('lotId')) {
            $lotId = $this->extractString($request, 'lotId');
            if ($lotId === null || $lotId === '') {
                $errors['lotId'] = 'Lot ID is required.';
            } else {
                $currentLot = (string) ($existing['lotId'] ?? '');
                if (strcasecmp($lotId, $currentLot) !== 0 && $this->rounds->existsByLotIdExcept($lotId, $existing['id'])) {
                    $errors['lotId'] = 'Lot ID already exists.';
                }
                $payload['lot_id'] = $lotId;
            }
        }

        $categoryId = null;
        if ($request->has('wasteCategoryId')) {
            $categoryId = $this->extractInt($request, 'wasteCategoryId');
            if ($categoryId === null || !$this->categories->exists($categoryId)) {
                $errors['wasteCategory'] = 'Valid waste category is required.';
            }
        } elseif ($request->has('wasteCategory')) {
            $categoryName = $this->extractString($request, 'wasteCategory');
            if ($categoryName === null || $categoryName === '') {
                $errors['wasteCategory'] = 'Waste category is required.';
            } else {
                $category = $this->categories->findByName($categoryName);
                if (!$category) {
                    $errors['wasteCategory'] = 'Waste category is invalid.';
                } else {
                    $categoryId = $category['id'];
                }
            }
        }

        if ($categoryId !== null) {
            $payload['waste_category_id'] = $categoryId;
        }

        if ($request->has('quantity')) {
            $quantity = $this->extractNumeric($request, 'quantity');
            if ($quantity === null || $quantity <= 0) {
                $errors['quantity'] = 'Quantity must be greater than zero.';
            } else {
                $payload['quantity'] = round($quantity, 2);
            }
        }

        if ($request->has('unit')) {
            $unit = $this->extractString($request, 'unit');
            if ($unit === null || $unit === '') {
                $errors['unit'] = 'Unit is required.';
            } else {
                $allowedUnits = ['kg', 'tons', 'tonnes', 'lb'];
                $normalized = strtolower($unit);
                if (!in_array($normalized, $allowedUnits, true)) {
                    $errors['unit'] = 'Invalid unit provided.';
                } else {
                    $payload['unit'] = $normalized === 'tonnes' ? 'tons' : $normalized;
                }
            }
        }

        if ($request->has('startingBid')) {
            $startingBid = $this->extractNumeric($request, 'startingBid');
            if ($startingBid === null || $startingBid < 0) {
                $errors['startingBid'] = 'Starting bid must be zero or greater.';
            } else {
                $payload['starting_bid'] = round($startingBid, 2);
            }
        }

        if ($request->has('endTime')) {
            $endTimeRaw = $this->extractString($request, 'endTime');
            if ($endTimeRaw === null || $endTimeRaw === '') {
                $errors['endTime'] = 'End time is required.';
            } else {
                $timestamp = strtotime($endTimeRaw);
                if ($timestamp === false) {
                    $errors['endTime'] = 'End time is invalid.';
                } elseif ($timestamp <= time()) {
                    $errors['endTime'] = 'End time must be in the future.';
                } else {
                    $payload['end_time'] = date('Y-m-d H:i:s', $timestamp);
                }
            }
        }

        if ($request->has('notes')) {
            $notesRaw = $request->get('notes');
            if ($notesRaw === null) {
                $payload['notes'] = null;
            } else {
                $notes = trim((string) $notesRaw);
                $payload['notes'] = $notes === '' ? null : $notes;
            }
        }

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        return ['payload' => $payload];
    }

    private function extractString(Request $request, string $key): ?string
    {
        $value = $request->get($key);
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);
        return $string;
    }

    private function extractInt(Request $request, string $key): ?int
    {
        $value = $request->get($key);
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $int = (int) $value;
        return $int > 0 ? $int : null;
    }


    private function extractNumeric(Request $request, string $key): ?float
    {
        $value = $request->get($key);
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    public function checkAvailability(Request $request): Response
    {
        $categoryId = $request->query('id');
        $categoryName = $request->query('categoryName') ?? $request->query('name');

        try {
            // Case 1: No category specified - return all availabilities
            if (!$categoryId && !$categoryName) {
                // Get full inventory status from the view
                $inventory = $this->inventory->getInventoryStatus();

                $data = array_map(function ($item) {
                    return [
                        'id' => $item['categoryId'],
                        'name' => $item['categoryName'],
                        'available' => (float) $item['availableQuantity'],
                        'unit' => $item['unit']
                    ];
                }, $inventory);

                return Response::json([
                    'success' => true,
                    'data' => $data
                ]);
            }

            // Case 2: Specific category requested
            $targetId = null;

            if ($categoryId) {
                $targetId = (int) $categoryId;
            } elseif ($categoryName) {
                $cat = $this->categories->findByName($categoryName);
                if ($cat) {
                    $targetId = (int) $cat['id'];
                }
            }

            if (!$targetId) {
                return Response::json([
                    'success' => true,
                    'available' => 0.0,
                    'unit' => 'kg' // Default
                ]);
            }

            // Get availability from WasteInventory model
            $item = $this->inventory->getAvailableByCategory($targetId);

            // Get category details for pricing
            $cat = $this->categories->findById($targetId);
            $pricePerUnit = $cat['pricePerUnit'] ?? 0.0;
            $markupPercentage = $cat['markupPercentage'] ?? 0.0;

            if (!$item) {
                return Response::json([
                    'success' => true,
                    'available' => 0.0,
                    'unit' => $cat['unit'] ?? 'kg',
                    'pricePerUnit' => $pricePerUnit,
                    'markupPercentage' => $markupPercentage
                ]);
            }

            return Response::json([
                'success' => true,
                'available' => (float) $item['availableQuantity'],
                'unit' => $item['unit'],
                'pricePerUnit' => $pricePerUnit,
                'markupPercentage' => $markupPercentage
            ]);

        } catch (\Throwable $e) {
            return Response::errorJson('Failed to check availability', 500, ['detail' => $e->getMessage()]);
        }
    }

    /**
     * Get bid history across all rounds or for a specific round
     * 
     * @param Request $request
     * @return Response
     */
    public function getBidHistory(Request $request): Response
    {
        try {
            $roundId = $request->query('roundId');
            $limit = (int) ($request->query('limit') ?? 100);

            // Fetch bids
            $bids = $this->rounds->getAllBidsWithRoundInfo($roundId, $limit);

            // Fetch rounds for dropdown (only if not filtering by specific round)
            $rounds = [];
            if (!$roundId) {
                $rounds = $this->rounds->getRoundsWithBids();
            }

            return Response::json([
                'success' => true,
                'bids' => $bids,
                'rounds' => $rounds,
            ]);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to fetch bid history', 500, ['detail' => $e->getMessage()]);
        }
    }


}
