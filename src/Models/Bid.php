<?php

namespace Models;

class Bid extends BaseModel
{
    protected string $table = 'bids';

    public function companyHistory(int $companyId, int $limit = 20): array
    {
        if ($companyId <= 0) {
            return [];
        }

        $limit = max(1, (int) $limit);

        $sql = "SELECT
                    b.id,
                    b.bidding_round_id,
                    b.amount,
                    b.is_winner,
                    b.created_at,
                    br.lot_id,
                    br.quantity,
                    br.unit,
                    br.status AS round_status,
                    br.current_highest_bid,
                    br.leading_company_id,
                    br.end_time,
                    wc.name AS waste_category_name
                FROM {$this->table} b
                INNER JOIN bidding_rounds br ON br.id = b.bidding_round_id
                LEFT JOIN waste_categories wc ON wc.id = br.waste_category_id
                WHERE b.company_id = ?
                ORDER BY b.created_at DESC
                LIMIT {$limit}";

        $rows = $this->db->fetchAll($sql, [$companyId]);
        if (!$rows) {
            return [];
        }

        return array_map(fn(array $row): array => $this->mapCompanyHistoryRow($row, $companyId), $rows);
    }

    public function findByRoundAndCompany(string $roundId, int $companyId): ?array
    {
        if ($roundId === '' || $companyId <= 0) {
            return null;
        }

        $sql = "SELECT id, amount, company_id, bidding_round_id FROM {$this->table} WHERE bidding_round_id = ? AND company_id = ? LIMIT 1";
        $row = $this->db->fetch($sql, [$roundId, $companyId]);

        return $row ?: null;
    }

    public function findForCompanyById(int $bidId, int $companyId): ?array
    {
        if ($bidId <= 0 || $companyId <= 0) {
            return null;
        }

        $sql = "SELECT
                    b.id,
                    b.amount,
                    b.is_winner,
                    b.created_at,
                    b.bidding_round_id,
                    br.lot_id,
                    br.quantity,
                    br.unit,
                    br.status AS round_status,
                    br.current_highest_bid,
                    br.leading_company_id,
                    br.end_time,
                    wc.name AS waste_category_name
                FROM {$this->table} b
                INNER JOIN bidding_rounds br ON br.id = b.bidding_round_id
                LEFT JOIN waste_categories wc ON wc.id = br.waste_category_id
                WHERE b.id = ? AND b.company_id = ?
                LIMIT 1";

        $row = $this->db->fetch($sql, [$bidId, $companyId]);
        if (!$row) {
            return null;
        }

        return $this->mapCompanyHistoryRow($row, $companyId);
    }

    public function placeBid(string $roundId, int $companyId, float $amount): int
    {
        $roundId = trim($roundId);
        if ($roundId === '' || $companyId <= 0 || $amount <= 0) {
            throw new \DomainException('Invalid bid payload.');
        }

        $pdo = $this->db->pdo();
        $pdo->beginTransaction();

        try {
            $round = $this->db->fetch(
                "SELECT id, status, end_time, current_highest_bid FROM bidding_rounds WHERE id = ? FOR UPDATE",
                [$roundId]
            );

            if (!$round) {
                throw new \DomainException('Selected bidding round could not be found.');
            }

            $status = $round['status'] ?? 'pending';
            if ($status !== 'active') {
                throw new \DomainException('Bidding on this round is closed.');
            }

            if (!empty($round['end_time']) && strtotime((string) $round['end_time']) <= time()) {
                throw new \DomainException('This bidding round has already ended.');
            }

            $currentHighest = isset($round['current_highest_bid']) ? (float) $round['current_highest_bid'] : 0.0;
            if ($amount <= $currentHighest) {
                throw new \DomainException('Bid must exceed the current highest bid of Rs ' . number_format($currentHighest, 2) . '.');
            }

            if ($this->db->isPgsql()) {
                $row = $this->db->fetch(
                    "INSERT INTO bids (bidding_round_id, company_id, amount, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP) RETURNING id",
                    [$roundId, $companyId, $amount]
                );

                if (!$row || !isset($row['id'])) {
                    throw new \RuntimeException('Failed to record bid.');
                }

                $bidId = (int) $row['id'];
            } else {
                $this->db->query(
                    "INSERT INTO bids (bidding_round_id, company_id, amount, created_at) VALUES (?, ?, ?, NOW())",
                    [$roundId, $companyId, $amount]
                );

                $bidId = (int) $this->db->lastInsertId();
            }

            $this->db->query(
                "UPDATE bidding_rounds SET current_highest_bid = ?, leading_company_id = ?, updated_at = NOW() WHERE id = ?",
                [$amount, $companyId, $roundId]
            );

            $pdo->commit();

            return $bidId;
        } catch (\DomainException $e) {
            $pdo->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Update a bid amount belonging to a company.
     * Returns true on success.
     */
    public function updateBid(int $bidId, int $companyId, float $newAmount): bool
    {
        if ($bidId <= 0 || $companyId <= 0 || $newAmount <= 0) {
            throw new \DomainException('Invalid update payload.');
        }

        $pdo = $this->db->pdo();
        $pdo->beginTransaction();

        try {
            $row = $this->db->fetch(
                "SELECT b.id, b.bidding_round_id, br.status, br.end_time, br.current_highest_bid FROM {$this->table} b INNER JOIN bidding_rounds br ON br.id = b.bidding_round_id WHERE b.id = ? AND b.company_id = ? FOR UPDATE",
                [$bidId, $companyId]
            );

            if (!$row) {
                throw new \DomainException('Bid not found or you do not have permission to modify it.');
            }

            if (($row['status'] ?? '') !== 'active') {
                throw new \DomainException('Bidding round is closed; cannot update bid.');
            }

            if (!empty($row['end_time']) && strtotime((string) $row['end_time']) <= time()) {
                throw new \DomainException('Bidding round has already ended.');
            }

            $currentHighest = isset($row['current_highest_bid']) ? (float) $row['current_highest_bid'] : 0.0;
            if ($newAmount <= $currentHighest) {
                throw new \DomainException('Updated bid must exceed the current highest bid of Rs ' . number_format($currentHighest, 2) . '.');
            }

            $this->db->query("UPDATE {$this->table} SET amount = ? WHERE id = ? AND company_id = ?", [$newAmount, $bidId, $companyId]);

            // Update round highest if necessary
            $this->db->query("UPDATE bidding_rounds SET current_highest_bid = ?, leading_company_id = ?, updated_at = NOW() WHERE id = ?", [$newAmount, $companyId, $row['bidding_round_id']]);

            $pdo->commit();
            return true;
        } catch (\DomainException $e) {
            $pdo->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Delete (cancel) a bid belonging to a company.
     * Returns the affected bidding round id on success.
     */
    public function deleteBid(int $bidId, int $companyId): ?string
    {
        if ($bidId <= 0 || $companyId <= 0) {
            throw new \DomainException('Invalid delete payload.');
        }

        $pdo = $this->db->pdo();
        $pdo->beginTransaction();

        try {
            $row = $this->db->fetch(
                "SELECT id, bidding_round_id FROM {$this->table} WHERE id = ? AND company_id = ? LIMIT 1 FOR UPDATE",
                [$bidId, $companyId]
            );

            if (!$row) {
                throw new \DomainException('Bid not found or you do not have permission to delete it.');
            }

            $roundId = (string) $row['bidding_round_id'];

            $roundRow = $this->db->fetch(
                "SELECT id, starting_bid FROM bidding_rounds WHERE id = ? LIMIT 1 FOR UPDATE",
                [$roundId]
            );

            if (!$roundRow) {
                throw new \DomainException('Associated bidding round could not be found.');
            }

            $this->db->query("DELETE FROM {$this->table} WHERE id = ? AND company_id = ?", [$bidId, $companyId]);

            $topBid = $this->db->fetch(
                "SELECT company_id, amount FROM {$this->table} WHERE bidding_round_id = ? ORDER BY amount DESC, created_at DESC LIMIT 1",
                [$roundId]
            );

            $highestAmount = 0.0;
            $leadingCompanyId = null;

            if ($topBid) {
                $bidAmount = isset($topBid['amount']) ? (float) $topBid['amount'] : 0.0;
                $highestAmount = round(max(0.0, $bidAmount), 2);
                $leadingCompanyId = isset($topBid['company_id']) ? (int) $topBid['company_id'] : null;
            }

            $this->db->query(
                "UPDATE bidding_rounds SET current_highest_bid = ?, leading_company_id = ?, updated_at = NOW() WHERE id = ?",
                [$highestAmount, $leadingCompanyId, $roundId]
            );

            $pdo->commit();

            return $roundId;
        } catch (\DomainException $e) {
            $pdo->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function mapCompanyHistoryRow(array $row, int $companyId): array
    {
        $quantity = isset($row['quantity']) ? (float) $row['quantity'] : 0.0;
        $unit = $row['unit'] ?? 'kg';
        $category = $row['waste_category_name'] ?? 'Unknown';
        $lotId = $row['lot_id'] ?: ('BR-' . $row['id']);
        $roundId = isset($row['bidding_round_id']) ? (string) $row['bidding_round_id'] : null;
        $amount = isset($row['amount']) ? (float) $row['amount'] : 0.0;
        $createdAt = $row['created_at'] ?? null;
        $roundStatus = $row['round_status'] ?? 'pending';
        $leadingCompanyId = $row['leading_company_id'] ?? null;
        $isWinner = (int) ($row['is_winner'] ?? 0) === 1;

        $status = 'Pending';
        if ($roundStatus === 'active') {
            $status = ((int) $leadingCompanyId === $companyId) ? 'Leading' : 'Lost';
        } elseif ($roundStatus === 'completed') {
            $status = $isWinner ? 'Won' : 'Lost';
        }

        if ($isWinner && $roundStatus !== 'completed') {
            $status = 'Won';
        }

        return [
            'id' => (int) $row['id'],
            'displayId' => 'BID' . str_pad((string) $row['id'], 4, '0', STR_PAD_LEFT),
            'category' => $category,
            'quantity' => $quantity,
            'unit' => $unit,
            'amount' => $amount,
            'status' => $status,
            'roundStatus' => $roundStatus,
            'lotId' => $lotId,
            'roundId' => $roundId,
            'createdAt' => $createdAt,
            'currentHighestBid' => isset($row['current_highest_bid']) ? (float) $row['current_highest_bid'] : 0.0,
            'endTime' => $row['end_time'] ?? null,
        ];
    }

    public function monthlyCounts(int $companyId, int $months = 6): array
    {
        if ($companyId <= 0) {
            return [];
        }

        $months = max(1, $months);
        $periodExpr = $this->db->isPgsql()
            ? "TO_CHAR(b.created_at, 'YYYY-MM')"
            : "DATE_FORMAT(b.created_at, '%Y-%m')";
        $winnerCase = $this->db->isPgsql()
            ? 'SUM(CASE WHEN b.is_winner IS TRUE THEN 1 ELSE 0 END)'
            : 'SUM(CASE WHEN b.is_winner = 1 THEN 1 ELSE 0 END)';

        $startDate = (new \DateTimeImmutable())
            ->modify('-' . $months . ' months')
            ->format('Y-m-d H:i:s');

        $sql = "SELECT {$periodExpr} AS period,
                                             COUNT(*) AS total,
                       {$winnerCase} AS won
                                FROM {$this->table} b
                                WHERE b.company_id = ?
                                    AND b.created_at >= ?
                                GROUP BY {$periodExpr}
                                ORDER BY period ASC";

        $rows = $this->db->fetchAll($sql, [$companyId, $startDate]);
        if (!$rows) {
            return [];
        }

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'period' => $row['period'],
                'total' => (int) ($row['total'] ?? 0),
                'won' => (int) ($row['won'] ?? 0),
            ];
        }

        return $data;
    }

    public function totals(int $companyId): array
    {
        if ($companyId <= 0) {
            return ['total' => 0, 'won' => 0];
        }

        $winnerCase = $this->db->isPgsql()
            ? 'SUM(CASE WHEN is_winner IS TRUE THEN 1 ELSE 0 END)'
            : 'SUM(CASE WHEN is_winner = 1 THEN 1 ELSE 0 END)';

        $row = $this->db->fetch(
            "SELECT COUNT(*) AS total, {$winnerCase} AS won
             FROM {$this->table}
             WHERE company_id = ?",
            [$companyId]
        );

        return [
            'total' => isset($row['total']) ? (int) $row['total'] : 0,
            'won' => isset($row['won']) ? (int) $row['won'] : 0,
        ];
    }

    public function monthlyCategoryAmounts(int $companyId, int $months = 6): array
    {
        if ($companyId <= 0) {
            return [];
        }

        $months = max(1, $months);
        $periodExpr = $this->db->isPgsql()
            ? "TO_CHAR(b.created_at, 'YYYY-MM')"
            : "DATE_FORMAT(b.created_at, '%Y-%m')";

        $startDate = (new \DateTimeImmutable())
            ->modify('-' . $months . ' months')
            ->format('Y-m-d H:i:s');

        $sql = "SELECT
                                        {$periodExpr} AS period,
                                        wc.name AS category,
                                        SUM(b.amount) AS total_amount
                                FROM {$this->table} b
                                INNER JOIN bidding_rounds br ON br.id = b.bidding_round_id
                                LEFT JOIN waste_categories wc ON wc.id = br.waste_category_id
                                WHERE b.company_id = ?
                                    AND b.created_at >= ?
                                GROUP BY {$periodExpr}, wc.name
                                ORDER BY period ASC";

        $rows = $this->db->fetchAll($sql, [$companyId, $startDate]);
        if (!$rows) {
            return [];
        }

        $data = [];
        foreach ($rows as $row) {
            $period = $row['period'];
            $category = $row['category'] ?? 'Other';
            $total = isset($row['total_amount']) ? (float) $row['total_amount'] : 0.0;
            if (!isset($data[$category])) {
                $data[$category] = [];
            }
            $data[$category][$period] = $total;
        }

        return $data;
    }
}
