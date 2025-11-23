<?php

namespace TshimologoMoeng\Payfast\WebhookHandler;

use TshimologoMoeng\Payfast\WebhookHandler\PayfastWebhookHandler;

class PayfastSubscriptionWebhookHandler extends PayfastWebhookHandler{
    public function __construct(string $merchant_id, string $merchant_key, bool $testing_mode = true, ?string $passphrase = null)
    {
        return parent::__construct($merchant_id, $merchant_key, $testing_mode, $passphrase);
    }
}