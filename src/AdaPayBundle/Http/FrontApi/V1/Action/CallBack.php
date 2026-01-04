<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace AdaPayBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use Dingo\Api\Exception\ResourceException;

class CallBack extends Controller
{
    public function handle(Request $request)
    {
        $eventType = $request->input('type', '');
        $post_data_str = $request->input('data', '');
        $post_sign_str = $request->input('sign', '');

        //storage_path('logs')
        app('log')->debug('adapay callback =>' . var_export($request->all(), 1));

        // 此处只是个示例 需要测试请去掉注释
        //$post_data_str = "{\"app_id\":\"app_fe1ec54d-e7cd-432a-a994-c12c3d8295f8\",\"created_time\":\"20201115182858\",\"end_time\":\"20201115182904\",\"expend\":{\"bank_type\":\"OTHERS\",\"open_id\":\"o8jhotwaUEffs1fyWE5O3N4HWvbk\",\"sub_open_id\":\"o4WGIxA59TYBzEKdwz_s6actNIYY\"},\"id\":\"002112020111518285710173995928213929984\",\"order_no\":\"SH20201115182857625624\",\"out_trans_id\":\"4200000839202011155051561044\",\"party_order_id\":\"02212011156653808201465\",\"pay_amt\":\"0.01\",\"pay_channel\":\"wx_pub\",\"status\":\"succeeded\"}";
        //$post_sign_str = "YXOWP5pyL38cZvXbVTyr4Lp9tpr2IzYmc5+EXuNofMTPPlCMfgXX4aBHT8QhxmKMYe95TBklWrM6IAdSLqIBXyc7CYnEYh0o54QHH4H\/yKy5yiOqFCbcHAHPhtJPU28rj+dHbG7YG\/4Qk5psFoBuOTP99ACizLy\/uiILYY3UhJk=";

        if ($eventType != 'userEntry.realTimeError') {
            # 先校验签名和返回的数据的签名的数据是否一致
            $sign_flag = $this->verifySign($post_sign_str, $post_data_str);
            if (!$sign_flag) {
                app('log')->error('adapay callback => 签名验证失败');
                throw new ResourceException('签名验证失败');
            } else {
                app('log')->info('adapay callback => 签名ok');
            }
        }


        $events = [
            'queryEntryUser.succeeded' => 'Entry@succeeded',//进件
            'queryEntryUser.failed' => 'Entry@succeeded',//进件
            'userEntry.realTimeError' => 'Entry@failed',
            'userEntry.succeeded' => 'Entry@succeeded',
            'userEntry.failed' => 'Entry@succeeded',
            'resident.succeeded' => 'Resident@succeeded',//入驻
            'resident.failed' => 'Resident@succeeded',
            'payment.succeeded' => 'Payment@succeeded',//支付成功
            'payment.failed' => 'Payment@succeeded',
            'payment.close.succeeded' => 'Payment@closeSucceeded',//支付关单成功
            'payment.close.failed' => 'Payment@closeFailed',
            'refund.succeeded' => 'Refund@succeeded',//退款成功
            'refund.failed' => 'Refund@succeeded',
            'corp_member.succeeded' => 'CorpMember@succeeded',//开户成功
            'corp_member.failed' => 'CorpMember@succeeded',
            'corp_member_update.succeeded' => 'CorpMemberUpdate@succeeded',//开户成功
            'corp_member_update.failed' => 'CorpMemberUpdate@succeeded',
            'payment_reverse.succeeded' => 'PaymentReverse@succeeded',//支付撤销成功
            'payment_reverse.failed' => 'PaymentReverse@succeeded',
            'cash.succeeded' => 'Cash@succeeded',//取现成功
            'cash.failed' => 'Cash@succeeded',
            'fastpay.succeeded',//快捷支付确认成功
            'fastpay.failed',
        ];

        $postData = json_decode($post_data_str, true);
        if (!in_array($eventType, $events) && !isset($events[$eventType])) {
            throw new ResourceException('unknown type');
        }

        $result = [];
        if (isset($events[$eventType])) {
            $eventType = $events[$eventType];
        }

        list($className, $methodName) = explode('@', $eventType);
//        $className = str_replace('_', ' ', $className);
//        $className = ucwords($className);
//        $className = str_replace(' ', '', $className);
        $className = '\\AdaPayBundle\\Services\\CallBack\\' . $className;
        $service = new $className();
        if (method_exists($service, $methodName)) {
            $result = $service->$methodName($postData);
        }
        return $this->response->array($result);
    }


    public function verifySign($signature, $data)
    {
        $pubKey = config('adapay.rsa_public_key');
        $key = "-----BEGIN PUBLIC KEY-----\n".wordwrap($pubKey, 64, "\n", true)."\n-----END PUBLIC KEY-----";

        if (openssl_verify($data, base64_decode($signature), $key)) {
            return true;
        } else {
            return false;
        }
    }
}
