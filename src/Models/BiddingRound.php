<?php

namespace Models;

class BiddingRound extends BaseModel
{
    protected string $table = 'bidding_rounds';

    public function findById(string $id): ?array
    {
        $row = $this->fetchDetailedRow($id);
        return $row ? $this->normalizeRound($row) : null;
    }

    public function findByLotId(string $lotId): ?array
    {
        $trimmed = trim($lotId);
        if ($trimmed === '') {
            return null;
        }

        $row = $this->db->fetch("SELECT id FROM {$this->table} WHERE lot_id = ? LIMIT 1", [$trimmed]);
        if (!$row || empty($row['id'])) {
            return null;
        }

        return $this->findById($row['id']);
    }

    public function existsByLotId(string $lotId): bool
    {
        $trimmed = trim($lotId);
        if ($trimmed === '') {
            return false;
        }

        $row = $this->db->fetch("SELECT id FROM {$this->table} WHERE lot_id = ? LIMIT 1", [$trimmed]);
        return (bool) $row;
    }

    public function existsByLotIdExcept(string $lotId, string $excludeId): bool
    {
        $trimmed = trim($lotId);
        $ignore = trim($excludeId);
        if ($trimmed === '' || $ignore === '') {
            return false;
        }

        $row = $this->db->fetch(
            "SELECT id FROM {$this->table} WHERE lot_id = ? AND id <> ? LIMIT 1",
            [$trimmed, $ignore]
        );

        return (bool) $row;
    }

    public function createRound(array $payload): array
    {
        $id = $payload['id'] ?? $this->generateId();
        $sql = "INSERT INTO {$this->table} (id, lot_id, waste_category_id, quantity, unit, starting_bid, current_highest_bid, leading_company_id, status, end_time, notes, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $params = [
            $id,
            $payload['lot_id'] ?? null,
            $payload['waste_category_id'] ?? null,
            $payload['quantity'] ?? null,
            $payload['unit'] ?? null,
            $payload['starting_bid'] ?? 0.0,
            $payload['current_highest_bid'] ?? 0.0,
            $payload['leading_company_id'] ?? null,
            $payload['status'] ?? 'active',
            $payload['end_time'] ?? null,
            $payload['notes'] ?? null,
        ];

        $this->db->query($sql, $params);

        return $this->findById($id) ?? [];
    }

    public function updateRound(string $id, array $attributes): ?array
    {
        if (empty($attributes)) {
            return $this->findById($id);
        }

        $currentRow = $this->db->fetch(
            "SELECT current_highest_bid, leading_company_id FROM {$this->table} WHERE id = ? LIMIT 1",
            [$id]
        );

        if (!$currentRow) {
            return null;
        }

        $updates = $attributes;
        $updates['updated_at'] = date('Y-m-d H:i:s');

        $hasBids = isset($currentRow['current_highest_bid'])
            && (float) $currentRow['current_highest_bid'] > 0
            && !empty($currentRow['leading_company_id']);

        if (!$hasBids && (isset($attributes['starting_bid']) || isset($attributes['quantity']))) {
            $updates['current_highest_bid'] = 0.0;
        }

        $this->updateAttributes($id, $updates);

        return $this->findById($id);
    }

    public function approveRound(string $id, ?int $companyId = null): ?array
    {
        $pdo = $this->db->pdo();
        $pdo->beginTransaction();

        try {
            $round = $this->db->fetch("SELECT * FROM {$this->table} WHERE id = ? FOR UPDATE", [$id]);
            if (!$round) {
                $pdo->rollBack();
                return null;
            }

            $selectedCompanyId = $companyId;
            $winningBidAmount = null;

            if ($selectedCompanyId === null && isset($round['leading_company_id']) && $round['leading_company_id']) {
                $selectedCompanyId = (int) $round['leading_company_id'];
            }

            if ($selectedCompanyId === null) {
                $topBid = $this->db->fetch(
                    "SELECT company_id, amount FROM bids WHERE bidding_round_id = ? ORDER BY amount DESC, created_at DESC LIMIT 1",
                    [$id]
                );
                if ($topBid) {
                    $selectedCompanyId = (int) $topBid['company_id'];
                    $winningBidAmount = isset($topBid['amount']) ? (float) $topBid['amount'] : null;
                }
            }

            $updates = [
                'status' => 'awarded',
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($selectedCompanyId !== null) {
                $updates['leading_company_id'] = $selectedCompanyId;
            }

            if ($winningBidAmount !== null) {
                $updates['current_highest_bid'] = $winningBidAmount;
            }

            if (empty($round['end_time'])) {
                $updates['end_time'] = date('Y-m-d H:i:s');
            }

            $this->updateAttributes($id, $updates);

            if ($selectedCompanyId !== null) {
                $this->db->query(
                    "UPDATE bids SET is_winner = CASE WHEN company_id = ? THEN 1 ELSE 0 END WHERE bidding_round_id = ?",
                    [$selectedCompanyId, $id]
                );
            } else {
                $this->db->query(
                    "UPDATE bids SET is_winner = 0 WHERE bidding_round_id = ?",
                    [$id]
                );
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $this->findById($id);
    }

    public function rejectRound(string $id, ?string $reason = null): ?array
    {
        $updates = [
            'status' => 'cancelled',
            'leading_company_id' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($reason !== null && $reason !== '') {
            $updates['notes'] = $reason;
        }

        $this->updateAttributes($id, $updates);
        $this->db->query("UPDATE bids SET is_winner = 0 WHERE bidding_round_id = ?", [$id]);

        return $this->findById($id);
    }

    public function cancelRound(string $id, ?string $reason = null): ?array
    {
        $trimmedReason = $reason !== null ? trim($reason) : null;

        $updates = [
            'status' => 'cancelled',
            'leading_company_id' => null,
            'end_time' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($trimmedReason !== null && $trimmedReason !== '') {
            $updates['notes'] = $trimmedReason;
        }

        $this->updateAttributes($id, $updates);
        $this->db->query("UPDATE bids SET is_winner = 0 WHERE bidding_round_id = ?", [$id]);

        return $this->findById($id);
    }

    public function availableWasteOverview(): array
    {
        $sql = "SELECT
                    wc.name AS category,
                    SUM(br.quantity) AS total_quantity,
                    COALESCE(br.unit, 'kg') AS unit
                FROM {$this->table} br
                LEFT JOIN waste_categories wc ON wc.id = br.waste_category_id
                WHERE br.status = 'active'
                GROUP BY wc.id, wc.name, COALESCE(br.unit, 'kg')
                HAVING SUM(br.quantity) IS NOT NULL
                ORDER BY SUM(br.quantity) DESC";

        $rows = $this->db->fetchAll($sql);
        if (!$rows) {
            return [];
        }

        return array_map(function (array $row): array {
            return [
                'category' => $row['category'] ?? 'Unknown',
                'quantity' => isset($row['total_quantity']) ? (float) $row['total_quantity'] : 0.0,
                'unit' => $row['unit'] ?? 'kg',
            ];
        }, $rows);
    }

    /**
     * Returns true if there are any bids recorded for the given bidding round id
     */
    public function hasBids(string $id): bool
    {
        if (trim($id) === '') {
            return false;
        }

        $row = $this->db->fetch("SELECT COUNT(1) AS c FROM bids WHERE bidding_round_id = ?", [$id]);
        if (!$row || !isset($row['c']))
            return false;
        return ((int) $row['c']) > 0;
    }

    /**
     * Returns true when the round has a leading company id
     */
    public function hasLeadingCompanyById(string $id): bool
    {
        if (trim($id) === '') {
            return false;
        }

        $row = $this->db->fetch("SELECT leading_company_id FROM {$this->table} WHERE id = ? LIMIT 1", [$id]);
        if (!$row)
            return false;
        return !empty($row['leading_company_id']);
    }

    public function highestBidsByCategory(): array
    {
        $sql = "SELECT
                    br.id,
                    br.lot_id,
                    br.quantity,
                    COALESCE(br.unit, 'kg') AS unit,
                    br.current_highest_bid,
                    br.status,
                    wc.name AS category,
                    br.waste_category_id
                FROM {$this->table} br
                LEFT JOIN waste_categories wc ON wc.id = br.waste_category_id
                WHERE br.current_highest_bid = (
                    SELECT MAX(br2.current_highest_bid)
                    FROM {$this->table} br2
                    WHERE br2.waste_category_id = br.waste_category_id
                )
                AND br.waste_category_id IS NOT NULL";

        $rows = $this->db->fetchAll($sql);
        if (!$rows) {
            return [];
        }

        $seen = [];
        $result = [];
        foreach ($rows as $row) {
            $categoryId = (int) ($row['waste_category_id'] ?? 0);
            if (isset($seen[$categoryId])) {
                continue;
            }
            $seen[$categoryId] = true;

            $result[] = [
                'category' => $row['category'] ?? 'Unknown',
                'lotId' => $row['lot_id'] ?? ($row['id'] ?? null),
                'quantity' => isset($row['quantity']) ? (float) $row['quantity'] : 0.0,
                'unit' => $row['unit'] ?? 'kg',
                'currentHighestBid' => isset($row['current_highest_bid']) ? (float) $row['current_highest_bid'] : 0.0,
                'status' => $row['status'] ?? 'active',
            ];
        }

        return $result;
    }

    public function activeLots(): array
    {
        $this->expireEndedRounds();
        $rows = $this->db->fetchAll(
            "SELECT br.*, wc.name AS waste_category_name
             FROM {$this->table} br
             LEFT JOIN waste_categories wc ON wc.id = br.waste_category_id
             WHERE br.status = 'active'
             ORDER BY br.end_time ASC, br.created_at DESC"
        );

        if (!$rows) {
            return [];
        }

        return array_map(function (array $row): array {
            $quantity = isset($row['quantity']) ? (float) $row['quantity'] : 0.0;
            $startingBid = isset($row['starting_bid']) ? (float) $row['starting_bid'] : 0.0;
            $currentHighestBid = isset($row['current_highest_bid']) ? (float) $row['current_highest_bid'] : 0.0;
            $leadingCompanyId = $row['leading_company_id'] ?? null;

            if ($leadingCompanyId === null || $currentHighestBid <= 0) {
                $currentHighestBid = 0.0;
            }

            $reservePrice = ($startingBid > 0 && $quantity > 0)
                ? round($startingBid * $quantity, 2)
                : null;

            return [
                'id' => $row['id'],
                'lotId' => $row['lot_id'] ?? $row['id'],
                'category' => $row['waste_category_name'] ?? 'Unknown',
                'quantity' => $quantity,
                'unit' => $row['unit'] ?? 'kg',
                'currentHighestBid' => $currentHighestBid,
                'status' => $row['status'] ?? 'active',
                'endTime' => $row['end_time'] ?? null,
                'startingBid' => $startingBid,
                'reservePrice' => $reservePrice,
            ];
        }, $rows);
    }

    public function companyRounds(int $companyId, ?string $status = null, int $limit = 20): array
    {
        // For stats consistency, we might trigger expiry here too, though strictly it matters most for 'active' queries.
        $this->expireEndedRounds();

        if ($companyId <= 0) {
            return [];
        }

        $limit = max(1, (int) $limit);

        $sql = "SELECT br.*, wc.name AS waste_category_name
                FROM {$this->table} br
                INNER JOIN bids b ON b.bidding_round_id = br.id AND b.company_id = ? AND b.is_winner IS TRUE
                LEFT JOIN waste_categories wc ON wc.id = br.waste_category_id";

        $params = [$companyId];

        if ($status !== null) {
            $sql .= " WHERE br.status <> ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY COALESCE(br.end_time, br.updated_at, br.created_at) DESC LIMIT {$limit}";

        $rows = $this->db->fetchAll($sql, $params);
        if (!$rows) {
            return [];
        }

        return array_map(function (array $row): array {
            $quantity = isset($row['quantity']) ? (float) $row['quantity'] : 0.0;
            $startingBid = isset($row['starting_bid']) ? (float) $row['starting_bid'] : 0.0;
            $currentHighestBid = isset($row['current_highest_bid']) ? (float) $row['current_highest_bid'] : 0.0;


            $reservePrice = ($startingBid > 0 && $quantity > 0)
                ? round($startingBid * $quantity, 2)
                : null;

            return [
                'id' => $row['id'],
                'lotId' => $row['lot_id'] ?? $row['id'],
                'category' => $row['waste_category_name'] ?? 'Unknown',
                'quantity' => $quantity,
                'unit' => $row['unit'] ?? 'kg',
                'currentHighestBid' => $currentHighestBid,
                'status' => $row['status'] ?? 'active',
                'endTime' => $row['end_time'] ?? null,
                'startingBid' => $startingBid,
                'reservePrice' => $reservePrice,
            ];
        }, $rows);
    }

    public function listAll(int $limit = 100): array
    {
        $this->expireEndedRounds();

        $limit = max(1, (int) $limit);
        $sql = "SELECT br.*, wc.name AS waste_category_name, u.name AS company_name
                FROM {$this->table} br
                LEFT JOIN waste_categories wc ON wc.id = br.waste_category_id
                LEFT JOIN users u ON u.id = br.leading_company_id
                ORDER BY
                    CASE WHEN br.status = 'active' THEN 0 ELSE 1 END,
                    br.end_time ASC
                LIMIT {$limit}";
        $rows = $this->db->fetchAll($sql);
        if (!$rows) {
            return [];
        }

        return array_map(fn(array $row): array => $this->normalizeRound($row), $rows);
    }

    public function stats(): array
    {
        $this->expireEndedRounds();

        $row = $this->db->fetch(
            "SELECT
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_rounds,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_rounds,
                SUM(current_highest_bid) AS total_bid_value
            FROM {$this->table}"
        );

        return [
            'active' => isset($row['active_rounds']) ? (int) $row['active_rounds'] : 0,
            'completed' => isset($row['completed_rounds']) ? (int) $row['completed_rounds'] : 0,
            'totalValue' => isset($row['total_bid_value']) ? (float) $row['total_bid_value'] : 0.0,
        ];
    }

    public function recent(int $limit = 5): array
    {
        $limit = max(1, (int) $limit);
        $rows = $this->db->fetchAll(
            "SELECT br.id, br.status, br.end_time, br.lot_id, wc.name AS waste_category_name
             FROM {$this->table} br
             LEFT JOIN waste_categories wc ON wc.id = br.waste_category_id
             ORDER BY br.updated_at DESC, br.end_time DESC
             LIMIT {$limit}"
        );
        return $rows ?: [];
    }

    private function fetchDetailedRow(string $id): ?array
    {
        $row = $this->db->fetch(
            "SELECT br.*, wc.name AS waste_category_name, u.name AS company_name
             FROM {$this->table} br
             LEFT JOIN waste_categories wc ON wc.id = br.waste_category_id
             LEFT JOIN users u ON u.id = br.leading_company_id
             WHERE br.id = ?
             LIMIT 1",
            [$id]
        );

        return $row ?: null;
    }

    private function normalizeRound(array $row): array
    {
        $quantity = isset($row['quantity']) ? (float) $row['quantity'] : 0.0;
        $startingBid = isset($row['starting_bid']) ? (float) $row['starting_bid'] : null;
        $currentHighestBid = isset($row['current_highest_bid']) ? (float) $row['current_highest_bid'] : 0.0;
        $leadingCompanyId = array_key_exists('leading_company_id', $row) ? $row['leading_company_id'] : null;

        $hasActiveBid = $leadingCompanyId !== null && $currentHighestBid > 0;
        if (!$hasActiveBid) {
            $currentHighestBid = 0.0;
        }

        $reservePrice = null;
        if ($startingBid !== null && $quantity > 0) {
            $reservePrice = round($startingBid * $quantity, 2);
        }

        return [
            'id' => $row['id'],
            'lotId' => $row['lot_id'] ?? $row['id'],
            'wasteCategory' => $row['waste_category_name'] ?? '',
            'wasteCategoryId' => isset($row['waste_category_id']) ? (int) $row['waste_category_id'] : null,
            'quantity' => $quantity,
            'unit' => $row['unit'] ?? 'kg',
            'startingBid' => $startingBid,
            'currentHighestBid' => $currentHighestBid,
            'biddingCompany' => $row['company_name'] ?? '',
            'leadingCompanyId' => $leadingCompanyId !== null ? (int) $leadingCompanyId : null,
            'status' => $row['status'] ?? 'active',
            'endTime' => $row['end_time'] ?? null,
            'notes' => $row['notes'] ?? null,
            'awardedCompany' => $row['company_name'] ?? '',
            'reservePrice' => $reservePrice,
        ];
    }

    private function updateAttributes(string $id, array $attributes): void
    {
        if (empty($attributes)) {
            return;
        }

        $columns = [];
        $params = [];

        foreach ($attributes as $column => $value) {
            $columns[] = $column . ' = ?';
            $params[] = $value;
        }

        $params[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $columns) . " WHERE id = ?";
        $this->db->query($sql, $params);
    }

    private function generateId(): string
    {
        return 'BR-' . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * Generate a unique lot id for a bidding round. Ensures uniqueness against existing lot_id values.
     */
    public function generateLotId(): string
    {
        // Try to generate a readable LOT-XXX id until we find one that doesn't exist
        for ($i = 0; $i < 8; $i++) {
            $candidate = 'LOT-' . strtoupper(bin2hex(random_bytes(3)));
            if (!$this->existsByLotId($candidate)) {
                return $candidate;
            }
        }

        // Fallback to using the internal id generator if collision persists
        $fallback = $this->generateId();
        // ensure uniqueness by appending an incremental suffix
        $suffix = 1;
        $candidate = $fallback;
        while ($this->existsByLotId($candidate)) {
            $candidate = $fallback . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    /**
     * Lazily expire rounds that have past their end time but remain active.
     */
    private function expireEndedRounds(): void
    {
        // Update status for any round that is 'active' and end_time is in the past
        $this->db->query(
            "UPDATE {$this->table}
             SET status = 'completed', updated_at = NOW()
             WHERE status = 'active' AND end_time <= NOW()"
        );
    }
    /**
     * Get all unique company IDs that have placed bids on this round.
     */
    public function getParticipatingCompanies(string $roundId): array
    {
        if (trim($roundId) === '') {
            return [];
        }

        $rows = $this->db->fetchAll(
            "SELECT DISTINCT company_id FROM bids WHERE bidding_round_id = ?",
            [$roundId]
        );

        if (!$rows) {
            return [];
        }

        return array_map(fn($r) => (int) $r['company_id'], $rows);
    }

    /**
     * Get all company IDs that bid on this round but are not the winner.
     */
    public function getLosingBidders(string $roundId, int $winnerId): array
    {
        $participants = $this->getParticipatingCompanies($roundId);

        // Filter out the winner
        return array_filter($participants, fn($id) => $id !== $winnerId);
    }
}
