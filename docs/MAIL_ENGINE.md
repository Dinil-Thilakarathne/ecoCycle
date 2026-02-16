# EcoCycle Custom Mail Engine

## Overview

The EcoCycle system now includes a custom mail engine built from scratch without relying on third-party libraries like PHPMailer or SwiftMailer. The mail engine supports SMTP transport, HTML/text email templates, and provides an easy-to-use API for sending emails throughout the application.

## Features

- ✅ **Pure PHP SMTP Implementation** - No third-party dependencies
- ✅ **TLS/SSL Support** - Secure email transmission
- ✅ **Template System** - HTML and plain text email templates
- ✅ **Multipart Emails** - Automatic HTML + text email generation
- ✅ **Easy Integration** - Simple helper functions for sending emails
- ✅ **Error Handling** - Graceful error handling with logging

## Configuration

### Environment Variables

Add the following to your `.env` file:

```bash
# Mail Configuration
MAIL_DRIVER=smtp
MAIL_FROM_ADDRESS=noreply@ecocycle.com
MAIL_FROM_NAME="EcoCycle"

# SMTP Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
SMTP_ENCRYPTION=tls
SMTP_TIMEOUT=30
```

### Gmail Setup

If using Gmail:

1. Enable 2-Factor Authentication
2. Generate an App Password: https://myaccount.google.com/apppasswords
3. Use the App Password in `SMTP_PASS`

### Other SMTP Providers

- **Mailtrap** (for testing): `smtp.mailtrap.io` (port 587)
- **SendGrid**: `smtp.sendgrid.net` (port 587)
- **Mailgun**: `smtp.mailgun.org` (port 587)

## Usage

### Sending Emails

#### Using the Helper Function

```php
// Send a simple notification
sendMail(
    'user@example.com',
    'notification',
    [
        'username' => 'John Doe',
        'title' => 'Welcome to EcoCycle',
        'message' => 'Thank you for joining our platform!'
    ],
    'Welcome to EcoCycle'
);

// Send a pickup request notification
sendMail(
    'customer@example.com',
    'pickup-request-created',
    [
        'customer_name' => 'Jane Smith',
        'request_id' => '12345',
        'waste_type' => 'Plastic',
        'pickup_address' => '123 Main St',
        'pickup_date' => '2026-02-10',
        'status' => 'Pending',
        'view_url' => url('/customer/pickup-requests/12345')
    ],
    'Your Pickup Request Has Been Created'
);
```

#### Using the Mailer Class Directly

```php
$mailer = mailer();

$mailer->sendTemplate(
    'recipient@example.com',
    'bid-received',
    [
        'customer_name' => 'John Doe',
        'bid_amount' => 150.00,
        'company_name' => 'EcoRecycle Ltd',
        'waste_type' => 'Paper',
        'quantity' => 50,
        'view_url' => url('/customer/bids/67890')
    ],
    'New Bid Received'
);
```

## Available Templates

### 1. `notification` - General Notification

Variables:

- `username` - Recipient's name
- `title` - Email title
- `message` - Main message content
- `action_url` (optional) - Button URL
- `action_text` (optional) - Button text
- `details` (optional) - Array of key-value details

### 2. `pickup-request-created` - Pickup Request Created

Variables:

- `customer_name` - Customer's name
- `request_id` - Request ID
- `waste_type` - Type of waste
- `pickup_address` - Pickup location
- `pickup_date` - Scheduled date
- `status` - Request status
- `view_url` - URL to view request

### 3. `pickup-status-updated` - Pickup Status Update

Variables:

- `customer_name` - Customer's name
- `request_id` - Request ID
- `status` - New status
- `status_message` (optional) - Status message
- `collector_name` (optional) - Collector name
- `view_url` - URL to view request

### 4. `bid-received` - New Bid Notification

Variables:

- `customer_name` - Customer's name
- `bid_amount` - Bid amount
- `company_name` - Company name
- `waste_type` - Type of waste
- `quantity` - Waste quantity
- `bid_message` (optional) - Bid message
- `view_url` - URL to view bid

## Creating Custom Templates

Create two files in `resources/emails/`:

1. **HTML Template** (`your-template.html.php`)
2. **Text Template** (`your-template.text.php`)

### Example HTML Template

```php
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?= htmlspecialchars($subject ?? 'Email') ?></title>
    <!-- Add your styles here -->
</head>
<body>
    <h1><?= htmlspecialchars($title) ?></h1>
    <p>Hi <?= htmlspecialchars($username) ?>,</p>
    <p><?= htmlspecialchars($message) ?></p>
</body>
</html>
```

### Example Text Template

```php
Hi <?= htmlspecialchars($username) ?>,

<?= htmlspecialchars($message) ?>

Regards,
The EcoCycle Team
```

## Testing

### 1. Test SMTP Connection

```bash
php scripts/test-mail-connection.php
```

### 2. Test Template Rendering

```bash
php scripts/test-mail-templates.php
```

### 3. Send Test Email

```bash
php scripts/test-send-mail.php your-email@example.com
```

## Integration with Notifications

To send emails when notifications are created, add this to your notification creation logic:

```php
// After creating a notification in the database
$notification = /* your notification data */;

// Send email notification
sendMail(
    $user['email'],
    'notification',
    [
        'username' => $user['name'],
        'title' => $notification['title'],
        'message' => $notification['message'],
        'action_url' => url('/customer/notifications/' . $notification['id']),
        'action_text' => 'View Notification'
    ],
    $notification['title']
);
```

## Troubleshooting

### Email Not Sending

1. **Check SMTP credentials** - Verify username and password in `.env`
2. **Check firewall** - Ensure port 587 (TLS) or 465 (SSL) is not blocked
3. **Check logs** - Error messages are logged via `error_log()`
4. **Test connection** - Run `php scripts/test-mail-connection.php`

### Gmail Issues

- Enable 2FA and use App Password
- Allow less secure apps (not recommended)
- Check "Allow access to your Google Account"

### Template Not Found

- Ensure template files exist in `resources/emails/`
- Check file naming: `template-name.html.php` and `template-name.text.php`
- Verify file permissions (readable by PHP)

## Architecture

### Components

1. **SmtpMailer** (`src/Core/Mail/SmtpMailer.php`)
   - Low-level SMTP protocol implementation
   - Socket-based communication
   - TLS/SSL support
   - AUTH LOGIN authentication

2. **Mailer** (`src/Core/Mail/Mailer.php`)
   - High-level email sending API
   - Template rendering
   - Multipart message building
   - RFC 2822 compliance

3. **Configuration** (`config/mail.php`)
   - Mail settings
   - SMTP configuration
   - Template paths

4. **Helper Functions** (`src/helpers.php`)
   - `mailer()` - Get Mailer instance
   - `sendMail()` - Quick email sending

## Security Considerations

- ✅ SMTP credentials stored in `.env` (not in version control)
- ✅ TLS/SSL encryption for secure transmission
- ✅ HTML escaping in templates to prevent XSS
- ✅ Error messages logged, not exposed to users
- ✅ No third-party dependencies to reduce attack surface

## Performance

- Singleton pattern for Mailer instance (reuse connection)
- Template caching via PHP's output buffering
- Minimal overhead (pure PHP, no external libraries)

## Future Enhancements

- [ ] Queue support for asynchronous email sending
- [ ] Email attachments
- [ ] Multiple recipients (CC, BCC)
- [ ] Email tracking (open rates, click rates)
- [ ] Template caching for better performance
- [ ] Support for other authentication methods (CRAM-MD5, PLAIN)
