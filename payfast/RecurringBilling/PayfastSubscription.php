<?php

namespace TshimologoMoeng\Payfast\RecurringBilling;

use DateTime;
use TshimologoMoeng\Payfast\SimpleIntegration\PayfastSimpleIntegration;
use TshimologoMoeng\Payfast\Enums\SubscriptionFrequency;
/**
 * - Custom Integration: Recurring Billing is set up in exactly the same manner as standard payments 
 * - (@see TshimologoMoeng\Payfast\SimpleIntegration\PayfastSimpleIntegration)
 * - On successful payment completion and all subsequent recurring payments you will be sent a notification (see Confirm payment is successful).
 * - A “token” parameter will be sent as part of the notification and is to be used for all further API calls related to the subscription.
 * 
 * - On failed payments, Payfast will try a number of times to reprocess a payment where the customer does not have funds on their credit card. On failure, the customer will be notified, allowing some time for the problem to be resolved. On a complete failure (after X amount of times), the subscription will be ‘locked’ and will need some action from the merchant to reactivate on the Payfast account or via the API pause endpoint.
 */
class PayfastSubscription extends PayfastSimpleIntegration
{
    /*
            WEBHOOK EXAMPLE FROM PAYFAST
        {
            "type":
            "subscription.free-trial",
            "token":
            "dc0521d3-55fe-269b-fa00-b647310d760f",
            "initial_amount":
            0,
            "amount":
            10000,
            "next_run":
            "2021-03-30",
            "frequency":
            "3",
            "item_name":
            "Test Item",
            "item_description":
            "A test product",
            "name_first":
            "John",
            "name_last":
            "Doe",
            "email_address":
            "john@example.com"
        }
*/

    /**
     * - Initiate a subscription
     * - Please note that for Subscriptions a passphrase is REQUIRED in your signature.
     * - To get a passphrase while testing in the sandbox, visit {@link https://sandbox.payfast.co.za}, under SETTINGS edit the "Salt Passphrase". You can now use the new passphrase and merchant credentials for your sandbox testing.
     * 
     * @param string $merchant_id - The Merchant ID as given by the Payfast system. Used to uniquely identify the receiving account. This can be found on the merchant’s settings page.
     * @param string $merchant_key - The Merchant Key as given by the Payfast system. Used to uniquely identify the receiving account. This provides an extra level of certainty concerning the correct account as both the ID and the Key must be correct in order for the transaction to proceed. This can be found on the merchant’s settings page.
     * @param boolean $testing_mode - True by default
     * @param string $passphrase -  An extra security feature, used as a ‘salt’, and is set by the Merchant in the Settings section of their Payfast Dashboard. (Required for Subscriptions)
     * @param SubscriptionFrequency $frequency - The cycle period. 1 - Daily, 2 - Weekly, 3 - Monthly, 4 - Quarterly, 5 - Biannually, 6 - Annual
     * @param int $subscription_cycles - The number of payments/cycles that will occur for this subscription. Set to 0 for indefinite subscription.
     * @param ?DateTime $billing_date - The date from which future subscription payments will be made. Eg. 2020-01-01. Defaults to current date if not set.
     * @param ?float $recurring_amount - Future recurring amount for the subscription in ZAR. Defaults to the ‘amount’ value if not set. There is a minimum value of 5.00.
     * It is possible to set up a subscription or tokenization payment with an initial amount of R0.00. This would be used with subscriptions if the first cycle/period is free, or, in the case of tokenization payments it is used to set up the customers account on the merchants site, allowing for future payments. If the initial amount is R0.00 the customer will be redirected to Payfast, where they will input their credit card details and go through 3D Secure, but no money will be deducted.
     * @param ?bool $subscription_notify_email - Send the merchant an email notification 7 days before a subscription trial ends, or before a subscription amount increases.
     * This setting is enabled by default and can be changed via the merchant dashboard: Settings -> Recurring Billing.
     * @param ?bool $subscription_notify_webhook - Send the merchant a webhook notification 7 days before a subscription trial ends, or before a subscription amount increases.
     *The webhook notification URL can be set via the merchant dashboard: Settings -> Recurring Billing.
     * @param ?bool $subscription_notify_buyer - Send the buyer an email notification 7 days before a subscription trial ends, or before a subscription amount increases.
     *This setting is enabled by default and can be changed via the merchant dashboard: Settings -> Recurring Billing.
     * @param ?int $fica_idnumber - The Fica ID Number provided of the buyer must be a valid South African ID Number.
     */
    public function __construct(
        string $merchant_id,
        string $merchant_key,
        bool $testing_mode = true,
        string $passphrase,
        protected SubscriptionFrequency $frequency,
        protected int $subscription_cycles = 0,
        protected ?DateTime $billing_date = null,
        protected ?float $recurring_amount = null,
        protected ?bool $subscription_notify_email,
        protected ?bool $subscription_notify_webhook,
        protected ?bool $subscription_notify_buyer,
        ?int $fica_idnumber = null
    ) {
        return parent::__construct($merchant_id, $merchant_key, $testing_mode, $passphrase, $fica_idnumber);
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
            'subscription_type' => 1, // 1 – sets type to a subscription
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
}
