-- Populate notifications table with dummy data

-- 1. System Maintenance Alert (All Users)
INSERT INTO notifications (type, title, message, recipient_group, recipients, status, sent_at, created_at, updated_at)
VALUES (
    'alert',
    'System Maintenance Scheduled',
    'We will be performing system maintenance on Sunday at 2 AM UTC. Service may be intermittent.',
    'all',
    NULL,
    'sent',
    NOW(),
    NOW(),
    NOW()
);

-- 2. Welcome Message for a specific user (e.g., User ID 1)
INSERT INTO notifications (type, title, message, recipient_group, recipients, status, sent_at, created_at, updated_at)
VALUES (
    'info',
    'Welcome to EcoCycle!',
    'Thank you for joining EcoCycle. We are glad to have you on board.',
    NULL,
    '["user:1"]',
    'sent',
    NOW() - INTERVAL '1 day',
    NOW() - INTERVAL '1 day',
    NOW() - INTERVAL '1 day'
);

-- 3. Bidding Round Update (Companies)
INSERT INTO notifications (type, title, message, recipient_group, recipients, status, sent_at, created_at, updated_at)
VALUES (
    'info',
    'New Bidding Round Available',
    'A new bidding round for Plastic waste has started. Check the dashboard for details.',
    'companies',
    NULL,
    'sent',
    NOW() - INTERVAL '2 hours',
    NOW() - INTERVAL '2 hours',
    NOW() - INTERVAL '2 hours'
);

-- 4. Pickup Request Status Update (Specific Customer - User ID 2)
INSERT INTO notifications (type, title, message, recipient_group, recipients, status, sent_at, created_at, updated_at)
VALUES (
    'success',
    'Pickup Request Confirmed',
    'Your pickup request #12345 has been confirmed by a collector.',
    NULL,
    '["user:2"]',
    'unread',
    NOW(),
    NOW(),
    NOW()
);

-- 5. Payment Received (Specific Company - User ID 3)
INSERT INTO notifications (type, title, message, recipient_group, recipients, status, sent_at, created_at, updated_at)
VALUES (
    'success',
    'Payment Received',
    'We have received your payment of $500.00 for Invoice #INV-001.',
    NULL,
    '["company:3"]',
    'unread',
    NOW() - INTERVAL '30 minutes',
    NOW() - INTERVAL '30 minutes',
    NOW() - INTERVAL '30 minutes'
);
