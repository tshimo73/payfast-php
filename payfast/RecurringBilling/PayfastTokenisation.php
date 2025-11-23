<?php

namespace TshimologoMoeng\Payfast\RecurringBilling;

use TshimologoMoeng\Payfast\Enums\SubscriptionFrequency;
use DateTime;
use TshimologoMoeng\Payfast\RecurringBilling\PayfastSubscription;

/**
 * A recurring charge where the future dates and amounts of payments may be unknown.
 * Payfast will only charge the customer's card when instructed to do so via the API.
 */
class PayfastTokenisation extends PayfastSubscription {
    public function __construct(string $merchant_id, string $merchant_key, bool $testing_mode = true, string $passphrase, SubscriptionFrequency $frequency, int $subscription_cycles = 0, ?DateTime $billing_date = null, ?float $recurring_amount = null, ?bool $subscription_notify_email, ?bool $subscription_notify_webhook, ?bool $subscription_notify_buyer, ?int $fica_idnumber = null)
    {
        return parent::__construct($merchant_id, $merchant_key, $testing_mode, $passphrase, $frequency, $subscription_cycles, $billing_date, $recurring_amount, $subscription_notify_email, $subscription_notify_webhook, $subscription_notify_buyer, $fica_idnumber);
    }

        /**
     * Sets up the transaction data needed to send to payfast
     * 
     * @return array
     */
    public function transaction_setup(): array
    {
        $data = array(
            //  Merchant Details
            'merchant_id' => $this->merchant_id,
            'merchant_key' => $this->merchant_key,
            'return_url' => $this->return_url,
            'cancel_url' => $this->cancel_url,
            'notify_url' => $this->notify_url,

            // Buyer Details
            'name_first' => $this->name_first,
            'name_last' => $this->name_last,
            'email_address' => $this->email_address,
            'cell_number' => $this->cell_number,

            //  Transaction Details
            'm_payment_id' => $this->m_payment_id,
            'amount' => number_format(sprintf('%.2f', $this->amount), 2, '.', ''),
            'item_name' => $this->item_name,
            'item_description' => $this->item_description,
            'email_confirmation' => $this->email_confirmation,
            'confirmation_address' => $this->confirmation_address,
            'payment_method' => $this->payment_method?->value,

            // Subscription Details
            'subscription_type' => 2, //2 – sets type to a tokenization payment
            'billing_date' => $this->billing_date,
            'recurring_amount' => $this->recurring_amount,
            'frequency' => $this->frequency,
            'cycles' => $this->subscription_cycles,
            'subscription_notify_email' => $this->subscription_notify_email,
            'subscription_notify_webhook' => $this->subscription_notify_webhook,
            'subscription_notify_buyer' => $this->subscription_notify_buyer
        );

        $data = array_filter($data, fn($value) => $value !== null && $value !== '');

        $data['signature'] = $this->generate_signature($data, $this->passphrase);

        return $data;
    }

    public function update_card_details_link(string $token, ?string $return = '') : string {
        $url = 'https://www.payfast.co.za/eng/recurring/update/' . $token;

        return $return === '' ?  $url : $url . '?return=' . $return;
    }
}