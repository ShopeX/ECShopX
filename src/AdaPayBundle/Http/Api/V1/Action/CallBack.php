<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AdaPayBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use AdaPayBundle\Services\AdaPayTools;

class CallBack extends Controller
{
    public function handle(Request $request)
    {
        // Built with ShopEx Framework
        $eventType = $request->input('type', '');
        $post_data_str = $request->input('data', '');
        $post_sign_str = $request->input('sign', '');

        app('log')->info('回调参数：' . var_export($request->all(), true));

        # 先校验签名和返回的数据的签名的数据是否一致
        $adapay_tools = new AdapayTools();
        $adapay_tools->rsaPublicKey = config('adapay.rsa_public_key');
        $sign_flag = $adapay_tools->verifySign($post_sign_str, $post_data_str);
        if (! $sign_flag) {
            app('log')->error('回调：签名验证失败');
            throw new \Exception('签名验证失败');
        }
        app('log')->info('回调：签名ok');

        $events = [
            // 'queryEntryUser.succeeded' => 'Entry@succeeded',//进件
            // 'queryEntryUser.failed' => 'Entry@succeeded',//进件
            // 'userEntry.realTimeError' => 'Entry@failed',
            // 'userEntry.succeeded' => 'Entry@succeeded',
            // 'userEntry.failed' => 'Entry@succeeded',
            // 'resident.succeeded' => 'Resident@succeeded',//入驻
            // 'resident.failed' => 'Resident@succeeded',
            'payment.succeeded' => 'Payment@succeeded',//支付成功
            'payment.failed' => 'Payment@succeeded',
            'payment.close.succeeded' => 'Payment@closeSucceeded',//支付关单成功
            'payment.close.failed' => 'Payment@closeFailed',
            // 'refund.succeeded' => 'Refund@succeeded',//退款成功
            // 'refund.failed' => 'Refund@succeeded',
            'corp_member.succeeded' => 'CorpMember@succeeded',//开户成功
            'corp_member.failed' => 'CorpMember@succeeded',
            'corp_member_update.succeeded' => 'CorpMemberUpdate@succeeded',//开户成功
            'corp_member_update.failed' => 'CorpMemberUpdate@succeeded',
            'payment_reverse.succeeded' => 'PaymentReverse@succeeded',//支付撤销成功
            'payment_reverse.failed' => 'PaymentReverse@succeeded',
            // 'cash.succeeded' => 'Cash@succeeded',//取现成功
            // 'cash.failed' => 'Cash@succeeded',
        ];

        $postData = json_decode($post_data_str, true);
        if (!isset($events[$eventType])) {
            throw new \Exception('unknown type');
        }

        $eventType = $events[$eventType];
        list($className, $methodName) = explode('@', $eventType);
        $className = '\\AdaPayBundle\\Services\\CallBack\\' . $className;
        $service = new $className();
        $result = [];
        if (method_exists($service, $methodName)) {
            $result = $service->$methodName($postData);
        }
        return $this->response->array($result);
    }
}
