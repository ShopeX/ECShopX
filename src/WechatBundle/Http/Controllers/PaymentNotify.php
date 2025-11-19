<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Http\Controllers;

use PaymentBundle\Services\Payments\WechatH5PayService;
use WechatBundle\Services\OpenPlatform;
use Illuminate\Http\Request;
use EasyWeChat\Kernel\Support\XML; // easywechat@done
use App\Http\Controllers\Controller as Controller;
use PaymentBundle\Services\Payments\WechatPayService;
use OrdersBundle\Services\TradeService;
use DepositBundle\Services\DepositTrade;
use PaymentBundle\Services\Payments\WechatAppPayService;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PaymentNotify extends Controller
{
    /**
     * 接收微信支付回调通知
     *
     * @return mixed
     */
    public function handle(Request $request)
    {
        // libxml_disable_entity_loader(true); //关键代码，XXE漏洞屏蔽，https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=23_5
        $data = XML::parse(strval($request->getContent()));
        app('log')->info('wxpay:response:' . var_export($data, 1));

        $openPlatform = new OpenPlatform();
        $companyId = $openPlatform->getCompanyId($data['appid']);
        // 获取tradeInfo
        $tradeService = new TradeService();
        $tradeInfo = $tradeService->getInfo(['trade_id' => $data['out_trade_no']]);
        $distributorId = $tradeInfo['distributor_id'] ?? 0;
        $services = new WechatPayService($distributorId);
        if ($companyId) {
            $payment = $services->getPayment($data['appid'], $data['appid'], $companyId);
        } else {
            if ($data['trade_type'] == 'APP') {
                $companyId = app('redis')->get('wechatAppPayment:companyId:' . $data['appid']);
                $services = new WechatAppPayService($distributorId);
            } else {
                $companyId = app('redis')->get('wechatPayment:companyId:' . $data['appid']);
                $services = new WechatH5PayService($distributorId);
            }

            if (!$companyId) {
                $companyId = app('redis')->get('wechatServicerPayment:companyId:' . $data['appid']);
            }

            $payment = $services->getPayment($data['appid'], $data['appid'], $companyId);
        }

        $response = $payment->handlePaidNotify(function ($notify, $successful) use ($data) {
            parse_str(urldecode($notify['attach']), $returnData);
            $tradeId = $notify['out_trade_no'];
            //支付成功
            if ($successful) {
                $status = 'SUCCESS';
            } else {
                $status = $notify['trade_state'] ?? 'PAYERROR';
            }

            $options['bank_type'] = isset($notify['bank_type']) ? $notify['bank_type'] : null;
            $options['transaction_id'] = isset($notify['transaction_id']) ? $notify['transaction_id'] : null;
            $options['total_fee'] = isset($notify['total_fee']) ? $notify['total_fee'] : null;

            if (isset($returnData['attach']) && $returnData['attach'] == 'depositRecharge') {
                try {
                    $depositTrade = new DepositTrade();
                    $options['pay_type'] = $returnData['pay_type'];
                    $depositTrade->rechargeCallback($tradeId, $status, $options);
                } catch (BadRequestHttpException $e) {
                    // 订单不存在，或者已更新 不需要在处理
                }
            } else {
                try {
                    $tradeService = new TradeService();
                    $tradeService->updateOneBy(['trade_id' => $tradeId], ['inital_response' => json_encode($data)]);
                    $options['pay_type'] = $returnData['pay_type'];
                    $tradeService->updateStatus($tradeId, $status, $options);
                } catch (BadRequestHttpException $e) {
                    // 订单不存在，或者已更新 不需要在处理
                }
            }
            return true;
        });
        return $response;
        //$response->send();
    }
}
