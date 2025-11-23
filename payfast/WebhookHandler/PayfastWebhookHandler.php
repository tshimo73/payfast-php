<?php

namespace TshimologoMoeng\Payfast\WebhookHandler;

use TshimologoMoeng\Payfast\Payfast;

/**
 * PayFast Webhook (ITN) Handler
 * 
 * Handles and validates Instant Transaction Notifications (ITNs) sent by PayFast
 * after a payment is processed. This class performs security validations including
 * signature verification, IP verification, amount validation, and server confirmation.
 * 
 * Usage:
 * ```php
 * $webhook = new PayfastWebhookHandler(
 *     merchant_id: '10000100',
 *     merchant_key: '46f0cd694581a',
 *     testing_mode: true,
 *     passphrase: 'your-passphrase'
 * );
 * 
 * if ($webhook->handle_webhook(expected_amount: 100.00)) {
 *     if ($webhook->is_payment_complete()) {
 *         // Process successful payment
 *         $data = $webhook->get_data();
 *     }
 * }
 * ```
 * 
 * @package TshimologoMoeng\Payfast
 * @link https://developers.payfast.co.za/docs#step_4_confirm_payment
 */
class PayfastWebhookHandler extends Payfast
{


    /*
        Transaction Webhook Payload Example
        $ITN_Payload = [
            'm_payment_id' => 'SuperUnique1',
            'pf_payment_id' => '1089250',
            'payment_status' => 'COMPLETE',
            'item_name' => 'test+product',
            'item_description' => '',
            'amount_gross' => 200.00,
            'amount_fee' => -4.60,
            'amount_net' => 195.40,
            'custom_str1' => '',
            'custom_str2' => '',
            'custom_str3' => '',
            'custom_str4' => '',
            'custom_str5' => '',
            'custom_int1' => '',
            'custom_int2' => '',
            'custom_int3' => '',
            'custom_int4' => '',
            'custom_int5' => '',
            'name_first' => '',
            'name_last' => '',
            'email_address' => '',
            'merchant_id' => '10012577',
            'signature' => 'ad8e7685c9522c24365d7ccea8cb3db7'
        ];
    */

    protected array $payfast_data = [];
    protected string $payfast_param_string = '';

    public function __construct(
        string $merchant_id,
        string $merchant_key,
        bool $testing_mode = true,
        ?string $passphrase = null
    ) {
        $this->merchant_id = $merchant_id;
        $this->merchant_key = $merchant_key;
        $this->testing_mode = $testing_mode;
        $this->passphrase = $passphrase;
    }

    /**
     * Handle the incoming webhook from PayFast
     * 
     * @param float $expected_amount - The amount you expect the customer to have paid
     * @return bool - True if all validations pass
     */
    public function handle_webhook(float $expected_amount): bool
    {
        // Tells PayFast that this page is reachable
        header('HTTP/1.0 200 OK');
        flush();

        // Only process POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }

        // Get posted data from ITN
        $this->payfast_data = $_POST;

        // Strip any slashes in the data
        foreach ($this->payfast_data as $key => $val) {
            $this->payfast_data[$key] = stripslashes($val);
        }

        // Build parameter string (excluding signature)
        $this->payfast_param_string = '';
        foreach ($this->payfast_data as $key => $val) {
            if ($key !== 'signature') {
                $this->payfast_param_string .= $key . '=' . urlencode($val) . '&';
            }
        }
        // Remove trailing ampersand
        $this->payfast_param_string = substr($this->payfast_param_string, 0, -1);

        // Run all validation checks
        return $this->complete_payfast_checks($expected_amount);
    }

    /**
     * Verify the security signature in the notification
     * 
     * @return bool
     */
    public function is_valid_signature(): bool
    {
        // Calculate security signature
        $temp_param_string = $this->payfast_param_string;

        if ($this->passphrase !== null) {
            $temp_param_string .= '&passphrase=' . urlencode($this->passphrase);
        }

        $signature = md5($temp_param_string);

        return isset($this->payfast_data['signature']) &&
            $this->payfast_data['signature'] === $signature;
    }

    /**
     * Check that the notification has come from a valid PayFast domain
     * 
     * @return bool
     */
    public function is_valid_ip(): bool
    {
        $valid_hosts = [
            'www.payfast.co.za',
            'sandbox.payfast.co.za',
            'w1w.payfast.co.za',
            'w2w.payfast.co.za',
        ];

        $valid_ips = [];

        foreach ($valid_hosts as $payfast_host_name) {
            $ips = gethostbynamel($payfast_host_name);

            if ($ips !== false) {
                $valid_ips = array_merge($valid_ips, $ips);
            }
        }

        // Remove duplicates
        $valid_ips = array_unique($valid_ips);

        // Get the IP of the requester
        if (!isset($_SERVER['HTTP_REFERER'])) {
            return false;
        }

        $referer_ip = gethostbyname(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST));

        return in_array($referer_ip, $valid_ips, true);
    }

    /**
     * The amount you expected the customer to pay should match the "amount_gross" value sent in the notification
     * 
     * @param float $expected_amount - The amount you expected the customer to pay
     * @return bool
     */
    public function is_valid_payment_data(float $expected_amount): bool
    {
        if (!isset($this->payfast_data['amount_gross'])) {
            return false;
        }

        // Allow for floating point rounding errors (0.01 tolerance)
        return abs((float)$expected_amount - (float)$this->payfast_data['amount_gross']) < 0.01;
    }

    /**
     * Validate the data by contacting PayFast's server and confirming the order details
     * 
     * @return bool
     */
    public function confirm_with_payfast(): bool
    {
        // Check if cURL is available
        if (!in_array('curl', get_loaded_extensions(), true)) {
            return false;
        }

        $url = 'https://' . $this->get_base_url() . '/eng/query/validate';

        // Create cURL request
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->payfast_param_string);

        // Execute request
        $response = curl_exec($ch);
        $ch = null;

        return $response === 'VALID';
    }

    /**
     * Run all PayFast validation checks
     * 
     * @param float $expected_amount - The amount you expect the customer to have paid
     * @return bool - True if all checks pass
     */
    public function complete_payfast_checks(float $expected_amount): bool
    {
        return $this->is_valid_signature() &&
            $this->is_valid_ip() &&
            $this->is_valid_payment_data($expected_amount) &&
            $this->confirm_with_payfast();
    }

    /**
     * Get the payment status from the webhook data
     * 
     * @return string|null
     */
    public function get_payment_status(): ?string
    {
        return $this->payfast_data['payment_status'] ?? null;
    }

    /**
     * Get all the webhook data
     * 
     * @return array
     */
    public function get_data(): array
    {
        return $this->payfast_data;
    }

    /**
     * Check if payment was successful
     * 
     * @return bool
     */
    public function is_payment_complete(): bool
    {
        return $this->get_payment_status() === 'COMPLETE';
    }
}
