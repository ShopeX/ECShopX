<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use PaymentBundle\Services\PaymentsService;

class PaypalTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        // $str = '{"create_time":"2025-07-23T03:22:05Z","update_time":"2025-07-23T04:04:24Z","id":"62556285GJ605631S","payment_source":{"paypal":{"email_address":"sb-fomepxxxxxxxx5@personal.example.com","account_id":"JE8EAQ3ANY7UJ","account_status":"VERIFIED","name":{"given_name":"John","surname":"Doe"},"address":{"country_code":"C2"}}},"intent":"CAPTURE","payer":{"email_address":"sb-fomep44798455@personal.example.com","payer_id":"JE8EAQ3ANY7UJ","name":{"given_name":"John","surname":"Doe"},"address":{"country_code":"C2"}},"purchase_units":[{"reference_id":"default","amount":{"currency_code":"USD","value":"10.00","breakdown":{"item_total":{"currency_code":"USD","value":"10.00"},"shipping":{"currency_code":"USD","value":"0.00"},"handling":{"currency_code":"USD","value":"0.00"},"insurance":{"currency_code":"USD","value":"0.00"},"shipping_discount":{"currency_code":"USD","value":"0.00"}}},"payee":{"email_address":"sb-5rmbyxxxxxxxx@business.example.com","merchant_id":"WU3LKUTZZQ92S"},"description":"241413132","custom_id":"company_id=1&pay_type=paypal&attach=orderPay","invoice_id":"5555555","items":[{"name":"\u652f\u4ed8\u6d4b\u8bd5","unit_amount":{"currency_code":"USD","value":"10.00"},"tax":{"currency_code":"USD","value":"0.00"},"quantity":"1"}],"shipping":{},"payments":{"captures":[{"status":"COMPLETED","id":"0DR47934AV6766142","amount":{"currency_code":"USD","value":"10.00"},"invoice_id":"5555555","custom_id":"company_id=1&pay_type=paypal&attach=orderPay","seller_protection":{"status":"ELIGIBLE","dispute_categories":["ITEM_NOT_RECEIVED","UNAUTHORIZED_TRANSACTION"]},"final_capture":true,"seller_receivable_breakdown":{"gross_amount":{"currency_code":"USD","value":"10.00"},"paypal_fee":{"currency_code":"USD","value":"0.64"},"net_amount":{"currency_code":"USD","value":"9.36"}},"disbursement_mode":"INSTANT","links":[{"href":"https:\/\/api.sandbox.paypal.com\/v2\/payments\/captures\/0DR47934AV6766142","rel":"self","method":"GET"},{"href":"https:\/\/api.sandbox.paypal.com\/v2\/payments\/captures\/0DR47934AV6766142\/refund","rel":"refund","method":"POST"},{"href":"https:\/\/api.sandbox.paypal.com\/v2\/checkout\/orders\/62556285GJ605631S","rel":"up","method":"GET"}],"create_time":"2025-07-23T04:04:24Z","update_time":"2025-07-23T04:04:24Z"}]}}],"status":"COMPLETED","links":[{"href":"https:\/\/api.sandbox.paypal.com\/v2\/checkout\/orders\/62556285GJ605631S","rel":"self","method":"GET"}]}';
        // $arr = json_decode($str,true);
        // var_dump($arr['status']);exit;
        $paymentsService = new \PaymentBundle\Services\Payments\PaypalService();

        $service = new PaymentsService($paymentsService);
        $companyId = 1;
        $config= [
          'client_id' => 'AVQNIgbrsj2qgTFTAkV_SxxxxxxxxxxXXXXXXXXXXXXXXXXXXXXXXXX6bQfxzmnPQasQmY2MF-PFqeS',
          'client_secret' => 'EB9yNqeckLc39mQMbhZG1oXucxxxxxxxxxxxXXXXXXXXXXXXXXXXXXnMaDQ9jhay1Y1Ob1LpIi2YzHD',
          'sandbox' => true,
          'webhook_id' => 12,
          'is_open' => true,
        ];
        $data = [
            'company_id'=>1,
            'body'=>'支付测试',
            'transaction_id'=>'0DR47934AV6766142',
            'total_fee'=>1000,
            'refund_fee'=>1000,
            'detail'=>'241413132',
            'order_id'=>5555555,
        ];
        // var_dump($data);exit;
        // $res = $paymentsService->doPay(111,222,$data);
        $res = $paymentsService->doRefund(1,222,$data);
        var_dump($res);
//        $service->setPaymentSetting($companyId, $config);
    }
}
