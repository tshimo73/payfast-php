<?php

namespace TshimologoMoeng\Payfast\OnsitePayment;

use TshimologoMoeng\Payfast\Payfast;

/**
 * Integrate Payfast’s secure payment engine directly into the checkout page.
 * 
 * - Important: Please note that for security reasons Onsite Payments requires that your application be served over HTTPS.
 */
class PayfastOnsitePayment extends Payfast
{
    public function __construct(
        string $merchant_id,
        string $merchant_key,
        bool $testing_mode,
        ?string $passphrase = null
    ) {
        $this->merchant_id = $merchant_id;
        $this->merchant_key = $merchant_key;
        $this->testing_mode = $testing_mode;
        $this->passphrase = $passphrase;
    }

    /**
     * Payfast's website to send transaction and customer details to.
     */
    public function get_payfast_url(): string
    {
        return 'https://' . $this->get_base_url() . '/onsite/process';
    }

    /**
     * Converts the data to a string
     */
    public function convert_data_to_string(array $data_array): string
    {
        // Create parameter string
        $pf_output = '';
        foreach ($data_array as $key => $val) {
            if ($val !== '') {
                $pf_output .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }
        // Remove last ampersand
        return substr($pf_output, 0, -1);
    }

    /**
     * Generates the payment identifier
     * 
     * @param string $payfast_param_string - The data convert to a string with the convert_data_to_string() method
     * @param ?string $payfast_proxy - Your payfast proxy (optional)
     */
    public function generate_payment_identifier(string $payfast_param_string, $payfast_proxy = null)
    {
        // Use cURL (if available)
        if (in_array('curl', get_loaded_extensions(), true)) {
            // Variable initialization
            $url = $this->get_payfast_url();

            // Create default cURL object
            $ch = curl_init();

            // Set cURL options - Use curl_setopt for greater PHP compatibility
            // Base settings
            curl_setopt($ch, CURLOPT_USERAGENT, NULL);  // Set user agent
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);      // Return output as string rather than outputting it
            curl_setopt($ch, CURLOPT_HEADER, false);             // Don't include header in output
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            // Standard settings
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payfast_param_string);
            if (!empty($payfast_proxy))
                curl_setopt($ch, CURLOPT_PROXY, $payfast_proxy);

            // Execute cURL
            $response = curl_exec($ch);
            $ch = null;
            echo $response;
            $rsp = json_decode($response, true);
            if ($rsp['uuid']) {
                return $rsp['uuid'];
            }
        }
        return null;

        
    }

    /**
     * Once you have successfully retrieved the above UUID for the payment you can pass that into the Javascript for the payment page initialisation.
     * - Place the following script inside of your document to initialise the payment modal on your website.
     * 
     * - From here you will to proceed in JavaScript
     * - Please refer to { @link https://developers.payfast.co.za/docs#onsite_payments }
     * 
     * @param array $data - Your data array
     * @param ?string $payfast_proxy - Your payfast proxy (optional)
     */
    public function setup_onsite_payment(array $data, ?string $payfast_proxy = null): string
    {
        $data['signature'] = $this->generate_signature($data, $this->passphrase);

        $pf_param_string = $this->convert_data_to_string($data);

        $payment_identifier = $this->generate_payment_identifier($pf_param_string, $payfast_proxy);

        return $payment_identifier;
    }
}