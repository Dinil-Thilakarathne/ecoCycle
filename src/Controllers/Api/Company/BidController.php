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
    private array $minimumBids;

    public function __construct()
    {
        $this->bids = new Bid();
        $this->rounds = new BiddingRound();
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

        try {
            $this->bids->updateBid($bidId, $companyId, $newTotal);
        } catch (\DomainException $e) {
            return Response::errorJson($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to update bid.', 500, ['detail' => $e->getMessage()]);
        }

        $updated = $this->bids->findForCompanyById($bidId, $companyId);
        $roundId = $updated['roundId'] ?? null;
        $round = null;
        if ($roundId) {
            $round = $this->rounds->findById($roundId);
        }

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

        try {
            $bidId = $this->bids->placeBid($round['id'], $companyId, $totalAmount);
        } catch (\DomainException $e) {
            return Response::errorJson($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return Response::errorJson('Failed to place bid.', 500, [
                'detail' => $e->getMessage(),
            ]);
        }

        $bid = $this->bids->findForCompanyById($bidId, $companyId);
        $updatedRound = $this->rounds->findById($round['id']);
        $lot = $updatedRound ? $this->formatRoundForLot($updatedRound) : null;

        return Response::json([
            'success' => true,
            'message' => 'Bid placed successfully.',
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
