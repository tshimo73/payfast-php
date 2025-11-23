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

    function __construct(
        string $merchant_id,
        string $merchant_key,
        bool $testing_mode = true,
        ?string $passphrase = null,
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

