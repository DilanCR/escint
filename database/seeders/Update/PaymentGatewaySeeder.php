<?php

namespace Database\Seeders\Update;

use App\Models\Admin\PaymentGateway;
use App\Models\Admin\PaymentGatewayCurrency;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        //Paystack
        $paystack = PaymentGateway::orderBy('id',"DESC")->first();
            $payment_gateways_id = $paystack->id+1;
            $payment_gateways_code = PaymentGateway::max('code')+5;

            $payment_gateways = array(
                array('id' => $payment_gateways_id,'slug' => 'add-money','code' => $payment_gateways_code,'type' => 'AUTOMATIC','name' => 'Paystack','title' => 'Paystack Gateway','alias' => 'paystack','image' => 'seeder/paystack.webp','credentials' => '[{"label":"Secret Key","placeholder":"Enter Secret Key","name":"secret-key","value":"sk_test_d094bb8359027eab06ca8ea9a3b757e47e35684b"},{"label":"Public Key","placeholder":"Enter Public Key","name":"public-key","value":"pk_test_64a32791e5d7acc43acafb3646a1b9ce898519ea"}]','supported_currencies' => '["NGN","USD","GHS","ZAR","KES"]','crypto' => '0','desc' => NULL,'input_fields' => NULL,'status' => '1','last_edit_by' => '1','created_at' => now(),'updated_at' => now(),'env' => 'SANDBOX')
            );
            PaymentGateway::insert($payment_gateways);

            $payment_gateway_currencies = array(
                array('payment_gateway_id' => $payment_gateways_id,'name' => 'Paystack NGN','alias' => 'add-money-paystack-ngn-automatic','currency_code' => 'NGN','currency_symbol' => '₦','image' => NULL,'min_limit' => '1000.00000000','max_limit' => '100000.00000000','percent_charge' => '1.00000000','fixed_charge' => '1.00000000','rate' => '1590.00000000','created_at' => now(),'updated_at' => now()),
                array('payment_gateway_id' => $payment_gateways_id,'name' => 'Paystack USD','alias' => 'add-money-paystack-usd-automatic','currency_code' => 'USD','currency_symbol' => '$','image' => NULL,'min_limit' => '1.00000000','max_limit' => '100.00000000','percent_charge' => '1.00000000','fixed_charge' => '1.00000000','rate' => '1.00000000','created_at' => now(),'updated_at' => now()),
                array('payment_gateway_id' => $payment_gateways_id,'name' => 'Paystack GHS','alias' => 'add-money-paystack-ghs-automatic','currency_code' => 'GHS','currency_symbol' => 'GH₵','image' => NULL,'min_limit' => '100.00000000','max_limit' => '10000.00000000','percent_charge' => '1.00000000','fixed_charge' => '1.00000000','rate' => '15.59000000','created_at' => now(),'updated_at' => now()),
                array('payment_gateway_id' => $payment_gateways_id,'name' => 'Paystack ZAR','alias' => 'add-money-paystack-zar-automatic','currency_code' => 'ZAR','currency_symbol' => 'R','image' => NULL,'min_limit' => '20.00000000','max_limit' => '1000.00000000','percent_charge' => '1.00000000','fixed_charge' => '1.00000000','rate' => '17.73000000','created_at' => now(),'updated_at' => now()),
                array('payment_gateway_id' => $payment_gateways_id,'name' => 'Paystack KES','alias' => 'add-money-paystack-kes-automatic','currency_code' => 'KES','currency_symbol' => 'KSh','image' => NULL,'min_limit' => '100.00000000','max_limit' => '1000.00000000','percent_charge' => '1.00000000','fixed_charge' => '1.00000000','rate' => '129.00000000','created_at' => now(),'updated_at' => now())
              );
            PaymentGatewayCurrency::insert($payment_gateway_currencies);


    }
}
