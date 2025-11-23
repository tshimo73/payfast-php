# PayFast PHP Package

Modern PHP package for PayFast payment integration with automatic signature generation and webhook handling.

## Features

- ✅ Simple payment integration
- ✅ Onsite payments (embedded checkout)
- ✅ Recurring billing (subscriptions)
- ✅ Tokenization payments
- ✅ Split payments
- ✅ Automatic signature generation
- ✅ Webhook (ITN) validation
- ✅ Sandbox/Production mode toggle
- ✅ PHP 8.1+ with full type safety

## Installation
```bash
composer require tshimologomoeng/payfast-php
```

## Requirements

- PHP 8.1 or higher
- cURL extension
- HTTPS required for onsite payments

---

## Table of Contents

1. [Simple Payment Integration](#simple-payment-integration)
2. [Onsite Payments](#onsite-payments)
3. [Recurring Billing (Subscriptions)](#recurring-billing-subscriptions)
4. [Tokenization](#tokenization)
5. [Split Payments](#split-payments)
6. [Webhook Handler](#webhook-handler)
7. [Testing](#testing)

---

## Simple Payment Integration

Redirect users to PayFast's payment page.
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use TshimologoMoeng\Payfast\SimpleIntegration\PayfastSimpleIntegration;
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

---

## Onsite Payments

Embed PayFast's payment form directly on your checkout page.

**Important:** Requires HTTPS for security.

### PHP Setup
```php
<?php

use TshimologoMoeng\Payfast\OnsitePayment\PayfastOnsitePayment;

$payfast = new PayfastOnsitePayment(
    merchant_id: 'your_merchant_id',
    merchant_key: 'your_merchant_key',
    testing_mode: true,
    passphrase: 'your_passphrase'
);

$data = [
    'merchant_id' => 'your_merchant_id',
    'merchant_key' => 'your_merchant_key',
    'amount' => 100.00,
    'item_name' => 'Product Name',
    'return_url' => 'https://yoursite.com/success',
    'cancel_url' => 'https://yoursite.com/cancel',
    'notify_url' => 'https://yoursite.com/webhook'
];

// Generate payment identifier (UUID)
$uuid = $payfast->setup_onsite_payment($data);
?>

<!-- Your HTML -->
<div id="payfast-container"></div>

<script>
    window.payfast_do_onsite_payment({
        "uuid": "<?php echo $uuid; ?>",
        "return_url": "https://yoursite.com/success",
        "cancel_url": "https://yoursite.com/cancel"
    });
</script>
```

For complete JavaScript implementation, see [PayFast Onsite Documentation](https://developers.payfast.co.za/docs#onsite_payments).

---

## Recurring Billing (Subscriptions)

Set up automatic recurring payments.

**Important:** Passphrase is **required** for subscriptions.
```php
<?php

use TshimologoMoeng\Payfast\RecurringBilling\PayfastSubscription;
use TshimologoMoeng\Payfast\Enums\SubscriptionFrequency;

$subscription = new PayfastSubscription(
    merchant_id: 'your_merchant_id',
    merchant_key: 'your_merchant_key',
    testing_mode: true,
    passphrase: 'your_passphrase', // REQUIRED
    frequency: SubscriptionFrequency::Monthly,
    subscription_cycles: 12, // 0 for indefinite
    billing_date: new DateTime('2025-12-01'), // Optional
    recurring_amount: 99.99, // Optional, defaults to initial amount
    subscription_notify_email: true,
    subscription_notify_webhook: true,
    subscription_notify_buyer: true
);

// Set customer and transaction details (same as simple integration)
$subscription->set_customer_details('John', 'Doe', 'john@example.com');
$subscription->set_transaction_details(
    amount: 99.99,
    item_name: 'Monthly Subscription',
    item_description: 'Premium membership'
);

// Redirect to PayFast
$url = $subscription->get_payment_url();
header("Location: $url");
```

### Subscription Frequencies

- `SubscriptionFrequency::Daily`
- `SubscriptionFrequency::Weekly`
- `SubscriptionFrequency::Monthly`
- `SubscriptionFrequency::Quarterly`
- `SubscriptionFrequency::Biannually`
- `SubscriptionFrequency::Annually`

### Free Trial

Set initial amount to R0.00 for a free trial period:
```php
$subscription->set_transaction_details(
    amount: 0.00, // Free trial
    item_name: 'Trial Subscription'
);
```

---

## Tokenization

Save customer payment details for future ad-hoc charges.
```php
<?php

use TshimologoMoeng\Payfast\RecurringBilling\PayfastTokenisation;
use TshimologoMoeng\Payfast\Enums\SubscriptionFrequency;

$tokenization = new PayfastTokenisation(
    merchant_id: 'your_merchant_id',
    merchant_key: 'your_merchant_key',
    testing_mode: true,
    passphrase: 'your_passphrase',
    frequency: SubscriptionFrequency::Monthly,
    subscription_cycles: 0
);

// Set up with R0.00 to just save card details
$tokenization->set_transaction_details(
    amount: 0.00,
    item_name: 'Card Setup'
);

// Get payment URL
$url = $tokenization->get_payment_url();
header("Location: $url");
```

### Update Card Details

Generate a link for customers to update their saved card:
```php
$updateLink = $tokenization->update_card_details_link(
    token: 'customer-token-from-webhook',
    return: 'https://yoursite.com/account'
);

echo "<a href='$updateLink'>Update Card Details</a>";
```

---

## Split Payments

Instantly split payments with a third party merchant.

**Note:** Must be enabled on your PayFast account.
```php
<?php

use TshimologoMoeng\Payfast\SplitPayments\PayfastSplitPayments;
use TshimologoMoeng\Payfast\Exceptions\PayfastValidationException;

try {
    // Split by percentage
    $split = new PayfastSplitPayments(
        third_party_merchant_id: '10000200',
        percentage: 10, // 10% goes to third party
        min: 5.00, // Minimum R5
        max: 200.00 // Maximum R200
    );
    
    // OR split by fixed amount
    $split = new PayfastSplitPayments(
        third_party_merchant_id: '10000200',
        amount: 50.00 // R50 goes to third party
    );
    
    // OR combine both (percentage first, then amount)
    $split = new PayfastSplitPayments(
        third_party_merchant_id: '10000200',
        percentage: 10,
        amount: 5.00
    );
    
    // Get JSON data to send with payment
    $splitData = $split->setup();
    
} catch (PayfastValidationException $e) {
    echo $e->getMessage();
    print_r($e->getErrors());
}
```

**Calculation Examples:**

- **Percentage only:** R400 with 10% = R360 to you, R40 to third party
- **Amount only:** R400 with R50 = R350 to you, R50 to third party
- **Both:** R400 with 10% + R5 = (R400 - R40) - R5 = R355 to you, R45 to third party
- **With max:** R400 with 10%, max R20 = R380 to you, R20 to third party

---

## Webhook Handler

### Simple Payment Webhook
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use TshimologoMoeng\Payfast\WebhookHandler\PayfastWebhookHandler;

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

### Subscription Webhook
```php
<?php

use TshimologoMoeng\Payfast\WebhookHandler\PayfastSubscriptionWebhookHandler;

$webhook = new PayfastSubscriptionWebhookHandler(
    merchant_id: 'your_merchant_id',
    merchant_key: 'your_merchant_key',
    testing_mode: true,
    passphrase: 'your_passphrase'
);

if ($webhook->handle_webhook(expected_amount: 99.99)) {
    $data = $webhook->get_data();
    
    $token = $data['token']; // Use for API calls
    $type = $data['type']; // subscription.free-trial, subscription.payment, etc.
    $nextRun = $data['next_run']; // Next billing date
    
    // Process subscription payment
    // ...
}
```

### Webhook Security

The webhook handler performs these security checks:
1. ✅ **Signature verification** - Ensures data wasn't tampered with
2. ✅ **IP validation** - Confirms request is from PayFast servers
3. ✅ **Amount validation** - Verifies payment amount matches expected
4. ✅ **Server confirmation** - Validates with PayFast's servers

---

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
- `PaymentMethod::Capitec_Pay` - Capitec Pay
- `PaymentMethod::SnapScan` - SnapScan
- `PaymentMethod::Zapper` - Zapper
- `PaymentMethod::Mobicred` - Mobicred
- `PaymentMethod::MoreTyme` - MoreTyme
- `PaymentMethod::Store_card` - Store Card
- `PaymentMethod::Mukuru` - Mukuru
- `PaymentMethod::SCode` - SCode
- `PaymentMethod::Masterpass_Scan_to_Pay` - Masterpass

---

## Testing

### Running Tests
```bash
vendor/bin/phpunit
```

Or add to `composer.json`:
```json
{
    "scripts": {
        "test": "phpunit"
    }
}
```

Then run:
```bash
composer test
```

### PayFast Sandbox Credentials

For testing, use PayFast's sandbox credentials:
- Merchant ID: `10000100`
- Merchant Key: `46f0cd694581a`
- Sandbox URL: `https://sandbox.payfast.co.za`

### Test Card Details

Use these test cards in sandbox mode:
- **Successful payment**: 4000 0000 0000 0002
- **Failed payment**: 4000 0000 0000 0010

---

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

**Note:** Passphrase is **required** for subscriptions and tokenization.

---

## Error Handling
```php
use TshimologoMoeng\Payfast\Exceptions\PayfastValidationException;

try {
    $paymentUrl = $payfast->get_payment_url();
    header("Location: $paymentUrl");
} catch (PayfastValidationException $e) {
    echo $e->getMessage();
    print_r($e->getErrors());
} catch (\Exception $e) {
    error_log("PayFast error: " . $e->getMessage());
    echo "Payment processing error";
}
```

---

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

---

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/your-feature-name`)
3. Commit your changes (`git commit -m 'your-message (informative)'`)
4. Push to the branch (`git push origin feature/your-feature-name`)
5. Open a Pull Request

---

## License

MIT License - see LICENSE file for details

---

## Support
-name
- 📧 Email: tshimologomoeng08@gmail.com
- 🐛 Issues: [GitHub Issues](https://github.com/tshimo73/payfast-php/issues)
- 📚 PayFast Docs: [https://developers.payfast.co.za](https://developers.payfast.co.za)

---

## Changelog

### 2.0.0 (Current)
- ✨ Added onsite payments
- ✨ Added recurring billing/subscriptions
- ✨ Added tokenization
- ✨ Added split payments
- ✨ Added custom exceptions
- ✨ Added tests
- 📝 Improved documentation

### 1.0.2
- Fixed autoload issues
- Improved documentation
- Added webhook validation

### 1.0.0
- Initial release
- Simple payment integration
- Webhook handler

---

**Note:** This is an unofficial package and is not affiliated with PayFast. Always refer to the [official PayFast documentation](https://developers.payfast.co.za) for the latest API specifications.