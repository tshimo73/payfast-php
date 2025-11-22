<?php

namespace TshimologoMoeng\Payfast\SimpleIntegration;


use TshimologoMoeng\Payfast\Payfast;
use TshimologoMoeng\Payfast\Enums\PaymentMethod;


/**
 * Initiate a simple payfast integration.
 * 
 * @param string $merchant_id - The Merchant ID as given by the Payfast system. Used to uniquely identify the receiving account. This can be found on the merchant’s settings page.
 * @param string $merchant_key - The Merchant Key as given by the Payfast system. Used to uniquely identify the receiving account. This provides an extra level of certainty concerning the correct account as both the ID and the Key must be correct in order for the transaction to proceed. This can be found on the merchant’s settings page.
 * @param boolean $testing_mode - True by default
 * @param ?string $passphrase -  An extra security feature, used as a ‘salt’, and is set by the Merchant in the Settings section of their Payfast Dashboard.
 * @param ?string $return_url - The URL where the user is returned to after payment has been successfully taken.
 * @param ?string $cancel_url - The URL where the user should be redirected should they choose to cancel their payment while on the Payfast system.
 * @param ?string $notify_url - The URL which is used by Payfast to post the **Instant Transaction Notifications (ITNs)({ @link https://developers.payfast.co.za/docs#step_4_confirm_payment}) for this transaction.
 * @param ?int $fica_idnumber - The Fica ID Number provided of the buyer must be a valid South African ID Number.
 */
class PayfastSimpleIntegration extends Payfast
{

    //  Customer Detail Variables
    protected ?string $name_first = null;
    protected ?string $name_last = null;
    protected ?string $email_address = null;
    protected ?string $cell_number = null;

    //  Transaction Detail Variable
    protected float $amount;
    protected string $item_name;
    protected ?string $item_description = null;
    protected ?string $m_payment_id = null;
    protected ?bool $email_confirmation = true;
    protected ?string $confirmation_address = null;
    protected ?PaymentMethod $payment_method = null;

    function __construct(
        string $merchant_id,
        string $merchant_key,
        bool $testing_mode = true,
        ?string $passphrase = null,
        protected ?string $return_url = null,
        protected ?string $cancel_url = null,
        protected ?string $notify_url = null,
        protected readonly ?int $fica_idnumber = null
    ) {
        $this->merchant_id = $merchant_id;
        $this->merchant_key = $merchant_key;
        $this->testing_mode = $testing_mode;
        $this->passphrase = $passphrase;
    }

    /**
     * (Optional) -
     * Sets the details the customer
     * 
     * @param ?string $name_first - The customer's first name.
     * @param ?string $name_last - The customer's last name.
     * @param ?string $email_address - The customer's email address
     * @param ?string $cell_number - The customer's valid cell number
     * 
     * @return void
     */
    public function set_customer_details(
        ?string $name_first = null,
        ?string $name_last = null,
        ?string $email_address = null,
        ?string $cell_number = null
    ) : void {
        $this->name_first = $name_first;
        $this->name_last = $name_last;
        $this->email_address = $email_address;
        $this->cell_number = $cell_number;
    }

    /**
     * Sets the transaction details
     * 
     * 
     * @param float $amount - The amount which the payer must pay in ZAR.
     * @param string $item_name - The name of the item being charged for, or in the case of multiple items the order number.
     * @param ?string $item_description - The name of the item being charged for, or in the case of multiple items the order number.
     * @param ?string $m_payment_id - Unique payment ID on the merchant’s system.
     * @param ?bool $email_confirmation - Whether to send an email confirmation to the merchant of the transaction. The email confirmation is automatically sent to the payer. 1 = on, 0 = off
     * @param ?string $confirmation_address - The email address to send the confirmation email to. This value can be set globally on your account. Using this field will override the value set in your account for this transaction.
     * @param ?PaymentMethod $payment_method - When this field is set, only the SINGLE payment method specified can be used when the customer reaches Payfast. If this field is blank, or not included, then all available payment methods will be shown.
     * 
     * @return void
     * 
     * - For Payment Method selection use: 
     * @see TshimologoMoeng\Payfast\Enums\PaymentMethod
     */
    public function set_transaction_details(
        float $amount,
        string $item_name,
        ?string $item_description = null,
        ?string $m_payment_id = null,
        ?bool $email_confirmation = true,
        ?string $confirmation_address = null,
        ?PaymentMethod $payment_method = null
    ) : void {
        $this->amount = $amount;
        $this->item_name = $item_name;
        $this->item_description = $item_description;
        $this->m_payment_id = $m_payment_id;
        $this->email_confirmation = $email_confirmation;
        $this->confirmation_address = $confirmation_address;
        $this->payment_method = $payment_method;
    }

    /**
     * Sets up the transaction data needed to send to payfast
     * 
     * @return array
     */
    public function transaction_setup() : array
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
            'payment_method' => $this->payment_method?->value
        );

        $data = array_filter($data, fn($value) => $value !== null && $value !== '');

        $data['signature'] = $this->generate_signature($data, $this->passphrase);

        return $data;
    }

    /**
     * Returns the payment url that you need to redirect your users to
     * 
     * - Users are redirected to PayFasts page to complete the transaction
     * 
     * @return string
     */
    public function get_payment_url(): string {
        $data = $this->transaction_setup();

        $queryString = http_build_query($data);

        return "https://" . $this->get_base_url() . "/eng/process?" . $queryString;
    }
}

