# EcoCycle Future Development Roadmap

This document outlines key areas for future development to enhance the usability, robustness, and engagement of the EcoCycle platform.

## 1. Automation & Efficiency (Immediate Priority)

Focus on removing manual data entry and connecting isolated systems.

- **Customer Payout Automation (Pricing Engine Phase 2)**:
  - **Goal**: Automatically calculate payouts when a pickup is marked "Completed".
  - **Mechanism**: Trigger event -> Calculate `Weight * Price` -> Create `Pending Payout` record.
- **Company Invoice Automation (Pricing Engine Phase 3)**:
  - **Goal**: Automatically generate invoices when a bid is awarded.
  - **Mechanism**: Trigger event -> Create `Pending Invoice` record for the winning bid amount.

## 2. Financial Integration

Transition from "tracking" payments to "processing" them.

- **Payment Gateway Integration**:
  - **Goal**: Allow real-time digital transactions.
  - **Tools**: Stripe, PayPal, or local payment gateways.
  - **Usage**: deeply integrate so companies can pay for bids instantly and the platform can disburse funds to customers automatically.
- **Wallet System**:
  - **Goal**: Users can accumulate earnings in an internal wallet before putting in a withdrawal request.

## 3. Collector Logistics (Mobile First)

Empower the field workforce with better tools.

- **Route Optimization**:
  - **Goal**: Reduce fuel costs and travel time.
  - **Feature**: Visualize daily pickups on a map and suggest the most efficient route sequence.
- **Live Tracking**:
  - **Goal**: improving customer trust.
  - **Feature**: Real-time location tracking of collectors (Uber-style) for customers waiting for pickups.

## 4. Customer Engagement (Gamification)

Make recycling a habit through positive reinforcement.

- **Eco-Points System**:
  - **Concept**: Award points for every kg recycled in addition to/instead of cash.
  - **Redemption**: Points exchangeable for coupons or discounts with sustainability partners.
- **Leaderboards & Badges**:
  - **Concept**: "Top Recycler of the Month" or "Plastic Warrior" badges.
  - **Goal**: Foster a sense of community and friendly competition.

## 5. Technical Improvements

Ensure the platform scales and remains stable.

- **Real-Time Notifications**:
  - **Tool**: WebSockets (Pusher/Socket.io).
  - **Benefit**: Users see status updates (e.g., "Collector Arrived") instantly without refreshing.
- **Automated Testing Suite**:
  - **Goal**: Prevent regressions.
  - **Action**: Implement PHPUnit tests for core logic (Pricing Engine, Payments) and Cypress/Selenium for critical UI flows.

## 6. Pricing Engine Ecosystem (Detailed View)

Deep dive into the evolution of the Pricing Engine.

### 6.1. Dynamic Pricing Models (Medium Term)

- **Concept**: Automatically adjust `price_per_unit` based on market demand or inventory levels.
- **Use Case**: If the warehouse is full of "Plastic", automatically lower the buying price to discourage more inflow.
- **Implementation Strategy**:
  - Add `max_capacity` and `current_stock` columns to `waste_categories`.
  - Implement pricing formula: `EffectivePrice = BasePrice * (1 - (CurrentStock / MaxCapacity))`.

### 6.2. Tiered Pricing Structures

- **Concept**: Offer better rates for higher volumes to encourage bulk recycling.
- **Use Case**: "Get Rs 55/kg for Plastic if you recycle more than 100kg at once" (Base price: Rs 50/kg).
- **Implementation**:
  - Create `price_tiers` table: `category_id`, `min_quantity`, `bonus_percentage`.

### 6.3. Financial Robustness & Auditing (Long Term)

- **Price History & Auditing**:
  - **Objective**: Strict record of who changed a price and when.
  - **Action**: Create `price_history` table (`category_id`, `old_price`, `new_price`, `changed_by`, `date`).
- **Internal Wallet System**:
  - **Objective**: Reduce transaction overhead.
  - **Flow**: Pickup Complete -> Wallet Balance +500. User requests withdrawal -> Create single Payment record.
- **Automated Invoicing**:
  - **Objective**: Generate PDF receipts for every transaction.
  - **Tooling**: `dompdf` or `mpdf`.

### 6.4. Money Transaction Log (Ledger System)

- **Concept**: A separate, immutable table to record _every_ change in a user's balance. This replaces relying solely on the mutable `users.total_earnings` column for truth.
- **Why**:
  - **Audibility**: "Why do I have Rs 500?" -> Trace back through individual credits/debits.
  - **Consistency**: The sum of all transaction logs for a user should exactly match their current wallet balance.
  - **Debugging**: Easier to spot double-payments or missing payouts.
- **Proposed Schema (`wallet_transactions`)**:
  - `id` (PK)
  - `user_id` (FK to users)
  - `amount` (DECIMAL, e.g., +500.00 or -200.00)
  - `type` (ENUM: 'credit', 'debit')
  - `source_type` (ENUM: 'pickup_payout', 'withdrawal', 'penalty', 'correction')
  - `source_id` (INT, ID of the related pickup/payment record)
  - `balance_after` (DECIMAL, snapshot of balance after this txn)
  - `description` (TEXT)
  - `created_at` (TIMESTAMP)
