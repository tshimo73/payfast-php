<?php

namespace TshimologoMoeng\Payfast;

use TshimologoMoeng\Payfast\Enums\PaymentMethod;

abstract class Payfast
{
    //  Merchant details
    protected string $merchant_id;
    protected string $merchant_key;
    protected bool $testing_mode;
    protected ?string $passphrase = null;

    //  Url redirects
    protected ?string $return_url = null;
    protected ?string $cancel_url = null;
    protected ?string $notify_url = null;


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

    /**
     * Returns the payfast url in use 
     * 
     * @example <strong>Official Website / API</strong>
     * @example <strong>The PayFast Sandbox</strong>
     * 
     * @return string
     */
    public function get_base_url(): string
    {
        return $this->testing_mode ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
    }

    /**
     * Genratated MD5 signature that is passed as a hidden input and then used to ensure the integrity of the data transfer.
     * 
     * @param array $data
     * @param ?string $passphrase - An extra security feature, used as a ‘salt’, and is set by the Merchant in the Settings section of their Payfast Dashboard.
     * @return string
     */
    public function generate_signature(array $data, ?string $passphrase = null): string
    {
        // Create parameter string
        $pf_output = '';
        foreach ($data as $key => $val) {
            if ($val !== '') {
                $pf_output .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }
        // Remove last ampersand
        $get_string = substr($pf_output, 0, -1);
        if ($passphrase !== null) {
            $get_string .= '&passphrase=' . urlencode(trim($passphrase));
        }

        return md5($get_string);
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
    ): void {
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
            'payment_method' => $this->payment_method?->value
        );

        $data = array_filter($data, fn($value) => $value !== null && $value !== '');

        $data['signature'] = $this->generate_signature($data, $this->passphrase);

        return $data;
    }
}
