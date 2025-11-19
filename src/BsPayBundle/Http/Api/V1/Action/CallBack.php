<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace BsPayBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use BsPayBundle\Sdk\Core\BsPayTools;

class CallBack extends Controller
{
    public function handle($eventType, Request $request)
    {
        app('log')->info('bspay eventType:'.$eventType);
        app('log')->info('bspay回调参数：' . var_export($request->all(), true));
        $post_data_str = $request->input('resp_data', '');
        $post_sign_str = $request->input('sign', '');

        # 先校验签名和返回的数据的签名的数据是否一致
        $bsPayTools = new BsPayTools();
        $rsaPublicKey = config('bspay.rsa_public_key');
        
        $sign_flag = $bsPayTools->verifySign($post_sign_str, $post_data_str, $rsaPublicKey);
        if (! $sign_flag) {
            app('log')->error('回调：签名验证失败');
            throw new \Exception('签名验证失败');
        }
        app('log')->info('回调：签名ok');

        $events = [
            'pay.wx_lite' => 'Payment@handle',      // 微信小程序支付
            'pay.wx_pub' => 'Payment@handle',       // 微信公众号支付
            'pay.wx_qr' => 'Payment@handle',        // 微信二维码支付
            'pay.alipay_wap' => 'Payment@handle',   // 支付宝H5支付
            'pay.alipay_qr' => 'Payment@handle',    // 支付宝二维码支付
            'withdraw.bspay' => 'Withdraw@handle',  // 提现回调处理
        ];

        $postData = json_decode($post_data_str, true);
        if (!isset($events[$eventType])) {
            throw new \Exception('unknown type');
        }

        $event = $events[$eventType];
        list($className, $methodName) = explode('@', $event);
        $className = '\\BsPayBundle\\Services\\CallBack\\' . $className;
        $service = new $className();
        $result = [];
        if (method_exists($service, $methodName)) {
            $result = $service->$methodName($postData, $eventType);
        }
        return $this->response->array($result);
    }
}
