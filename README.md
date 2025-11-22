# PayFast PHP Package

Modern PHP package for PayFast payment integration with automatic signature generation and webhook handling.

## Features

- ✅ Simple payment integration
- ✅ Automatic signature generation
- ✅ Webhook (ITN) validation
- ✅ Sandbox/Production mode toggle
- ✅ PHP 8.1+ with full type safety
- ✅ Clean, fluent API

## Installation
```bash
composer require tshimologomoeng/payfast-php
```

## Requirements

- PHP 8.1 or higher
- cURL extension

## Quick Start

### Simple Payment Integration
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use TshimologoMoeng\Payfast\PayfastSimpleIntegration;
use TshimologoMoeng\Payfast\Enums\PaymentMethod;

// Initialize PayFast
$payfast = new PayfastSimpleIntegration(
    merchant_id: 'your_merchant_id',
    merchant_key: 'your_merchant_key',
    testing_mode: true, // Set to false for production
    passphrase: 'your_passphrase', // Optional
    return_url: 'https://yoursite.com/success',
    cancel_url: 'https://yoursite.com/cancel',
    notify_url: 'https://yoursite.com/webhook'
);

// Set customer details (optional)
$payfast->set_customer_details(
    name_first: 'John',
    name_last: 'Doe',
    email_address: 'john@example.com',
    cell_number: '0821234567'
);

// Set transaction details
$payfast->set_transaction_details(
    amount: 100.00,
    item_name: 'Product Name',
    item_description: 'Product description',
    m_payment_id: 'ORDER-123', // Your unique order ID
    payment_method: PaymentMethod::Credit_Card // Optional: force specific payment method
);

// Redirect user to PayFast
$paymentUrl = $payfast->get_payment_url();
header("Location: $paymentUrl");
exit;
```

### Webhook (ITN) Handler

Create a webhook endpoint (e.g., `/webhook/payfast.php`):
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use TshimologoMoeng\Payfast\PayfastWebhookHandler;

// Initialize webhook handler
$webhook = new PayfastWebhookHandler(
    merchant_id: 'your_merchant_id',
    merchant_key: 'your_merchant_key',
    testing_mode: true,
    passphrase: 'your_passphrase' // Optional
);

// Handle the webhook with expected amount
if ($webhook->handle_webhook(expected_amount: 100.00)) {
    
    // Check if payment was successful
    if ($webhook->is_payment_complete()) {
        // Payment successful - update your database
        $data = $webhook->get_data();
        
        $paymentId = $data['m_payment_id']; // Your order ID
        $payfastId = $data['pf_payment_id']; // PayFast transaction ID
        $amount = $data['amount_gross'];
        
        // Update order status in your database
        // ...
        
        echo "Payment processed successfully";
    } else {
        // Payment failed or pending
        $status = $webhook->get_payment_status();
        echo "Payment status: $status";
    }
} else {
    // Validation failed
    http_response_code(400);
    echo "Invalid webhook";
}
```

## Available Payment Methods

You can force a specific payment method using the `PaymentMethod` enum:
```php
use TshimologoMoeng\Payfast\Enums\PaymentMethod;

$payfast->set_transaction_details(
    amount: 100.00,
    item_name: 'Product',
    payment_method: PaymentMethod::Credit_Card
);
```

Available options:
- `PaymentMethod::EFT` - Electronic Funds Transfer
- `PaymentMethod::Credit_Card` - Credit Card
- `PaymentMethod::Debit_Card` - Debit Card
- `PaymentMethod::Apple_Pay` - Apple Pay
- `PaymentMethod::Samsung_Pay` - Samsung Pay
- `PaymentMethod::Google_Pay` - Google Pay
- `PaymentMethod::SnapScan` - SnapScan
- `PaymentMethod::Zapper` - Zapper
- `PaymentMethod::Mobicred` - Mobicred
- `PaymentMethod::MoreTyme` - MoreTyme
- And more...

## Configuration

### Sandbox vs Production
```php
// Sandbox (testing)
$payfast = new PayfastSimpleIntegration(
    merchant_id: '10000100',
    merchant_key: '46f0cd694581a',
    testing_mode: true
);

// Production
$payfast = new PayfastSimpleIntegration(
    merchant_id: 'your_live_merchant_id',
    merchant_key: 'your_live_merchant_key',
    testing_mode: false
);
```

### Using Passphrase (Recommended)

For added security, set a passphrase in your PayFast dashboard and include it:
```php
$payfast = new PayfastSimpleIntegration(
    merchant_id: 'your_merchant_id',
    merchant_key: 'your_merchant_key',
    testing_mode: true,
    passphrase: 'your_secure_passphrase'
);
```

## Webhook Security

The webhook handler performs these security checks:
1. ✅ **Signature verification** - Ensures data wasn't tampered with
2. ✅ **IP validation** - Confirms request is from PayFast servers
3. ✅ **Amount validation** - Verifies payment amount matches expected
4. ✅ **Server confirmation** - Validates with PayFast's servers

All checks must pass for `handle_webhook()` to return `true`.

## Testing

### PayFast Sandbox Credentials

For testing, use PayFast's sandbox credentials:
- Merchant ID: `10000100`
- Merchant Key: `46f0cd694581a`
- Sandbox URL: `https://sandbox.payfast.co.za`

### Test Card Details

Use these test cards in sandbox mode:
- **Successful payment**: 4000 0000 0000 0002
- **Failed payment**: 4000 0000 0000 0010

## Error Handling
```php
try {
    $paymentUrl = $payfast->get_payment_url();
    header("Location: $paymentUrl");
} catch (\Exception $e) {
    // Handle errors
    error_log("PayFast error: " . $e->getMessage());
    echo "Payment processing error";
}
```

## API Reference

### PayfastSimpleIntegration

#### Constructor
```php
__construct(
    string $merchant_id,
    string $merchant_key,
    bool $testing_mode = true,
    ?string $passphrase = null,
    ?string $return_url = null,
    ?string $cancel_url = null,
    ?string $notify_url = null,
    ?int $fica_idnumber = null
)
```

#### Methods

**set_customer_details()**
```php
set_customer_details(
    ?string $name_first = null,
    ?string $name_last = null,
    ?string $email_address = null,
    ?string $cell_number = null
): void
```

**set_transaction_details()**
```php
set_transaction_details(
    float $amount,
    string $item_name,
    ?string $item_description = null,
    ?string $m_payment_id = null,
    ?bool $email_confirmation = true,
    ?string $confirmation_address = null,
    ?PaymentMethod $payment_method = null
): void
```

**get_payment_url()**
```php
get_payment_url(): string
```

### PayfastWebhookHandler

#### Constructor
```php
__construct(
    string $merchant_id,
    string $merchant_key,
    bool $testing_mode = true,
    ?string $passphrase = null
)
```

#### Methods

**handle_webhook()**
```php
handle_webhook(float $expected_amount): bool
```

**is_payment_complete()**
```php
is_payment_complete(): bool
```

**get_payment_status()**
```php
get_payment_status(): ?string
```

**get_data()**
```php
get_data(): array
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

MIT License - see LICENSE file for details

## Support

- 📧 Email: tshimologomoeng08@gmail.com
- 🐛 Issues: [GitHub Issues](https://github.com/tshimologomoeng/payfast-php/issues)
- 📚 PayFast Docs: [https://developers.payfast.co.za](https://developers.payfast.co.za)

## Changelog

### 1.0.2 (Current)
- Fixed autoload issues
- Improved documentation
- Added webhook validation

### 1.0.0
- Initial release
- Simple payment integration
- Webhook handler

---

**Note:** This is an unofficial package and is not affiliated with PayFast. Always refer to the [official PayFast documentation](https://developers.payfast.co.za) for the latest API specifications.