<?php

namespace TshimologoMoeng\Payfast;

abstract class Payfast
{
    protected string $merchant_id;
    protected string $merchant_key;
    protected bool $testing_mode;
    protected ?string $passphrase = null;

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
}
