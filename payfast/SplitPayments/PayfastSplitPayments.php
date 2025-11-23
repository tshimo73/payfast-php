<?php

namespace TshimologoMoeng\Payfast\SplitPayments;

use TshimologoMoeng\Payfast\Exceptions\PayfastValidationException;

/**
 * Instantly split a portion of an online payment with a third party.
 * 
 * - You will need to enable Split Payments on your account. 
 * - Only one receiving merchant can be allocated a Split Payment, per split transaction. Therefore the Merchant ID of this receiving merchant is mandatory.
 * 
 * -  If both percentage and amount are specified, then the percentage will be deducted first, and then the amount will be deducted from the rest.
 * @example Split amount: (40,000 – (40,000/10)) – 500) = 35,500 cents
 * 
 * -  If the split amount is bigger than the max, then the max will be used instead of the split amount.
 * @example Split amount: 40,000 – (40,000/10) = 36,000 cents (> max) = 20 000 cents
 * 
 * @param string $third_party_merchant_id - The third party merchant that the payment is being split with.
 * @param float $amount - The amount in Rands (ZAR), that will go to the third party merchant.
 * - REQUIRED IF NOT USING PERCENTAGE
 * @param int $percentage - The percentage allocated to the third party merchant.
 * - REQUIRED IF NOT USING AMOUNT
 * @param float $min - The minimum amount that will be split, in Rands (ZAR)
 * @param float $max - The maximum amount that will be split, in Rands (ZAR)
 */
class PayfastSplitPayments
{
    public function __construct(
        protected string $third_party_merchant_id,
        protected ?float $amount = null,
        protected ?int $percentage = null,
        protected ?float $min = null,
        protected ?float $max = null
    ) {}

    private function convert_rands_to_cents(float $rands): float
    {
        return $rands * 100;
    }

    /**
     *  If both percentage and amount are specified, then the percentage will be deducted first, and then the amount will be deducted from the rest.
     */
    public function setup()
    {
        $data = [];

        try {
            $this->error_checks();

            $data = [
                'split_payment' => [
                    'merchant_id' => $this->third_party_merchant_id,
                    'percentage' => $this->percentage,
                    'amount' => $this->convert_rands_to_cents($this->amount),
                    'min' => $this->convert_rands_to_cents($this->min),
                    'max' => $this->convert_rands_to_cents($this->max)
                ]
            ];
        } catch (PayfastValidationException $e) {
            echo $e->getMessage();
        }

        return json_encode(
            array_filter($data, fn($value) => $value !== null)
        );
    }


    private function error_checks(): void
    {
        if ($this->percentage === null &&  $this->amount === null) {
            throw new PayfastValidationException(
                "Invalid parameters.",
                [
                    'percentage' => 'Cannot be null if amount is also null.',
                    'amount' => 'Cannot be null if percentage is also null.'
                ],
                PayfastValidationException::INVALID_PARAMETER
            );
        }
    }
}
