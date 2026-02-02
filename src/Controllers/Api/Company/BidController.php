<?php

namespace Controllers\Api\Company;

use Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;
use Models\Bid;
use Models\BiddingRound;

class BidController extends BaseController
{
    private Bid $bids;
    private BiddingRound $rounds;
    private \Models\Notification $notification;
    private array $minimumBids;

    public function __construct()
    {
        $this->bids = new Bid();
        $this->rounds = new BiddingRound();
        $this->notification = new \Models\Notification();
        $this->minimumBids = (array) config('data.minimum_bids', []);
    }

    public function update(Request $request): Response
    {
        $this->mergeJsonBody($request);

        $user = auth();
        $companyId = isset($user['id']) ? (int) $user['id'] : 0;
        if ($companyId <= 0) {
            return Response::errorJson('Unauthorized', 401);
        }

        $bidId = (int) $this->extractString($request, 'id');
        if ($bidId <= 0) {
            return Response::errorJson('Invalid bid id.', 422);
        }

        $bidPerUnit = $this->extractNumeric($request, 'bidPerUnit');
        $wasteAmount = $this->extractNumeric($request, 'wasteAmount');

        if ($bidPerUnit === null || $wasteAmount === null) {
            return Response::errorJson('Invalid payload.', 422);
        }

        $newTotal = round($bidPerUnit * $wasteAmount, 2);

        // Fetch existing bid and round details for notifications
        $existingBid = $this->bids->findForCompanyById($bidId, $companyId);
        if (!$existingBid) {
            return Response::errorJson('Bid not found.', 404);
        }

        $roundId = $existingBid['roundId'] ?? null;
        $round = $roundId ? $this->rounds->findById((string) $roundId) : null;

        if (!$round) {
            return Response::errorJson('Associated bidding round not found.', 404);
        }

        try {
            $this->bids->updateBid($bidId, $companyId, $newTotal);

            // --- Notification Logic ---
            $previousLeaderId = isset($round['leadingCompanyId']) ? (int) $round['leadingCompanyId'] : null;
            $categoryName = $round['wasteCategory'] ?? 'Unknown Category';
            $lotId = $round['lotId'] ?? 'Unknown Lot';

            // 1. Notify Admin: Bid Updated
            $this->notification->create([
                'type' => 'info',
                'title' => 'Bid Updated',
                'message' => "Company #{$companyId} updated their bid to {$newTotal} on {$categoryName} ({$lotId}).",
                'recipient_group' => 'admin',
                'status' => 'pending'
            ]);

            // 2. Notify Previous Leader: Outbid (if there was a different leader before)
            // Note: If the company itself was already the leader, we don't notify them.
            if ($previousLeaderId && $previousLeaderId !== $companyId) {
                $this->notification->create([
                    'type' => 'alert',
                    'title' => 'You have been outbid!',
                    'message' => "Your bid on {$categoryName} ({$lotId}) has been outbid. New highest bid: {$newTotal}.",
                    'recipients' => ['company:' . $previousLeaderId],
                    'status' => 'pending'
                ]);
            }
            // --------------------------

        } catch (\DomainException $e) {
            return Response::errorJson($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to update bid.', 500, ['detail' => $e->getMessage()]);
        }

        $updated = $this->bids->findForCompanyById($bidId, $companyId);
        $round = $this->rounds->findById((string) $roundId);

        return Response::json([
            'success' => true,
            'message' => 'Bid updated successfully.',
            'bid' => $updated,
            'roundId' => $roundId,
            'round' => $round,
            'lot' => $this->formatRoundForLot($round),
        ]);
    }

    public function destroy(Request $request): Response
    {
        $user = auth();
        $companyId = isset($user['id']) ? (int) $user['id'] : 0;
        if ($companyId <= 0) {
            return Response::errorJson('Unauthorized', 401);
        }

        $id = $request->get('id') ?? $this->extractString($request, 'id');
        $bidId = (int) $id;
        if ($bidId <= 0) {
            return Response::errorJson('Invalid bid id.', 422);
        }

        $roundId = null;

        try {
            $roundId = $this->bids->deleteBid($bidId, $companyId);
        } catch (\DomainException $e) {
            return Response::errorJson($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to delete bid.', 500, ['detail' => $e->getMessage()]);
        }

        $round = null;
        $lot = null;
        if (!empty($roundId)) {
            $round = $this->rounds->findById($roundId);
            $lot = $this->formatRoundForLot($round);
        }

        return Response::json([
            'success' => true,
            'message' => 'Bid deleted successfully.',
            'roundId' => $roundId,
            'round' => $round,
            'lot' => $lot,
        ]);
    }

    public function store(Request $request): Response
    {
        $this->mergeJsonBody($request);

        $user = auth();
        $companyId = isset($user['id']) ? (int) $user['id'] : 0;
        if ($companyId <= 0) {
            return Response::errorJson('Unauthorized', 401);
        }

        $roundId = $this->extractString($request, 'roundId');
        $lotId = $this->extractString($request, 'lotId');

        if (($roundId === null || $roundId === '') && $lotId !== null && $lotId !== '') {
            $roundFromLot = $this->rounds->findByLotId($lotId);
            if ($roundFromLot) {
                $roundId = $roundFromLot['id'];
            }
        }

        if ($roundId === null || $roundId === '') {
            return Response::errorJson('Select a waste lot before placing a bid.', 422, [
                'roundId' => 'Waste lot is required.'
            ]);
        }

        $round = $this->rounds->findById($roundId);
        if (!$round) {
            return Response::errorJson('Selected waste lot was not found.', 404);
        }

        if (($round['status'] ?? '') !== 'active') {
            return Response::errorJson('Bidding on this lot is closed.', 422);
        }

        if (!empty($round['endTime']) && strtotime((string) $round['endTime']) <= time()) {
            return Response::errorJson('This bidding round has already ended.', 422);
        }

        // Check if company already has a bid on this round
        $existingBid = $this->bids->findByRoundAndCompany($round['id'], $companyId);
        $isUpdate = $existingBid !== null;

        $wasteType = $this->extractString($request, 'wasteType');
        if ($wasteType === null || $wasteType === '') {
            $wasteType = (string) ($round['wasteCategory'] ?? '');
        }
        $normalizedWasteType = strtolower(trim((string) $wasteType));
        $roundCategory = strtolower(trim((string) ($round['wasteCategory'] ?? '')));
        if ($roundCategory !== '') {
            $normalizedWasteType = $roundCategory;
        }

        $bidPerUnit = $this->extractNumeric($request, 'bidPerUnit');
        $wasteAmount = $this->extractNumeric($request, 'wasteAmount');

        $errors = [];
        if ($bidPerUnit === null || $bidPerUnit <= 0) {
            $errors['bidPerUnit'] = 'Enter a valid bid amount per unit.';
        }

        if ($wasteAmount === null || $wasteAmount <= 0) {
            $errors['wasteAmount'] = 'Enter a valid waste amount.';
        }

        if ($bidPerUnit !== null) {
            $lookup = $normalizedWasteType;
            if ($lookup !== '' && isset($this->minimumBids[$lookup])) {
                $minRequired = (float) $this->minimumBids[$lookup];
                if ($bidPerUnit < $minRequired) {
                    $errors['bidPerUnit'] = 'Bid must be at least Rs ' . number_format($minRequired, 2) . ' for ' . ucfirst($lookup) . '.';
                }
            }
        }

        if (!empty($errors)) {
            return Response::errorJson('Validation failed.', 422, $errors);
        }

        $totalAmount = round($bidPerUnit * $wasteAmount, 2);

        // Ensure requested waste amount does not exceed the lot's available quantity
        $roundQty = isset($round['quantity']) ? (float) $round['quantity'] : 0.0;
        if ($wasteAmount !== null && $roundQty > 0 && $wasteAmount > $roundQty) {
            return Response::errorJson('Requested waste amount exceeds available lot quantity.', 422, [
                'wasteAmount' => 'Cannot bid for more than available lot quantity (' . $roundQty . ').'
            ]);
        }

        if ($isUpdate) {
            // Check if new amount is higher than existing amount
            $currentAmount = isset($existingBid['amount']) ? (float) $existingBid['amount'] : 0.0;
            if ($totalAmount <= $currentAmount) {
                return Response::errorJson('You already have a higher or equal bid on this lot. You can only update to a higher amount.', 422, [
                    'bidPerUnit' => 'Total bid amount must be higher than your current bid of Rs ' . number_format($currentAmount, 2)
                ]);
            }
        }

        try {
            if ($isUpdate) {
                $bidId = (int) $existingBid['id'];
                $this->bids->updateBid($bidId, $companyId, $totalAmount);
                $actionMessage = 'Bid updated successfully.';
            } else {
                $bidId = $this->bids->placeBid($round['id'], $companyId, $totalAmount);
                $actionMessage = 'Bid placed successfully.';
            }

            // --- Notification Logic ---
            $previousLeaderId = isset($round['leadingCompanyId']) ? (int) $round['leadingCompanyId'] : null;
            $categoryName = $round['wasteCategory'] ?? 'Unknown Category';
            $lotId = $round['lotId'] ?? 'Unknown Lot';

            // 1. Notify Admin: New Bid Placed or Updated
            $this->notification->create([
                'type' => 'info',
                'title' => $isUpdate ? 'Bid Updated' : 'New Bid Placed',
                'message' => "Company #{$companyId} " . ($isUpdate ? "updated their" : "placed a") . " bid of {$totalAmount} on {$categoryName} ({$lotId}).",
                'recipient_group' => 'admin',
                'status' => 'pending'
            ]);

            // 2. Notify Previous Leader: Outbid
            if ($previousLeaderId && $previousLeaderId !== $companyId) {
                $this->notification->create([
                    'type' => 'alert',
                    'title' => 'You have been outbid!',
                    'message' => "Your bid on {$categoryName} ({$lotId}) has been outbid. New highest bid: {$totalAmount}.",
                    'recipients' => ['company:' . $previousLeaderId],
                    'status' => 'pending'
                ]);
            }
            // --------------------------

        } catch (\DomainException $e) {
            return Response::errorJson($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to ' . ($isUpdate ? 'update' : 'place') . ' bid.', 500, [
                'detail' => $e->getMessage(),
            ]);
        }

        $bid = $this->bids->findForCompanyById($bidId, $companyId);
        $updatedRound = $this->rounds->findById($round['id']);
        $lot = $updatedRound ? $this->formatRoundForLot($updatedRound) : null;

        return Response::json([
            'success' => true,
            'message' => $actionMessage,
            'bid' => $bid,
            'round' => $updatedRound,
            'lot' => $lot,
        ], 201);
    }

    private function formatRoundForLot(?array $round): ?array
    {
        if (!$round) {
            return null;
        }

        return [
            'id' => $round['id'] ?? null,
            'lotId' => $round['lotId'] ?? null,
            'category' => $round['wasteCategory'] ?? '',
            'quantity' => $round['quantity'] ?? 0,
            'unit' => $round['unit'] ?? 'kg',
            'currentHighestBid' => $round['currentHighestBid'] ?? 0,
            'status' => $round['status'] ?? 'active',
            'endTime' => $round['endTime'] ?? null,
            'reservePrice' => $round['reservePrice'] ?? null,
        ];
    }

    private function mergeJsonBody(Request $request): void
    {
        $json = $request->json();
        if (is_array($json) && method_exists($request, 'mergeBody')) {
            $request->mergeBody($json);
        }
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
}
