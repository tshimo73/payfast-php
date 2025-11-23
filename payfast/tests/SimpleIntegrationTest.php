<?php

namespace TshimologoMoeng\Payfast\Tests;

require './vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use TshimologoMoeng\Payfast\SimpleIntegration\PayfastSimpleIntegration;

class SimpleIntegrationTest extends TestCase
{
    public function test_it_generates_correct_payment_url()
    {
        $payfast = new PayfastSimpleIntegration(
            merchant_id: '10000100',
            merchant_key: '46f0cd694581a',
            testing_mode: true
        );

        $payfast->set_transaction_details(16.99, 'Test');

        $url = $payfast->get_payment_url();

        $this->assertStringContainsString('sandbox.payfast.co.za', $url);

        $this->assertStringContainsString('amount=16.99', $url);

        $this->assertStringContainsString('item_name=Test', $url);
    }

    public function test_it_formats_amount_correctly()
    {
        $payfast = new PayfastSimpleIntegration('id', 'key');
        $payfast->set_transaction_details(100, 'Test');

        $data = $payfast->transaction_setup();

        // Amount should be formatted to 2 decimals
        $this->assertEquals('100.00', $data['amount']);
    }

    public function test_it_removes_null_values_from_data()
    {
        $payfast = new PayfastSimpleIntegration('id', 'key');
        $payfast->set_transaction_details(100, 'Test');

        $data = $payfast->transaction_setup();

        $this->assertArrayNotHasKey('name_first', $data);
        $this->assertArrayNotHasKey('email_address', $data);
    }
}