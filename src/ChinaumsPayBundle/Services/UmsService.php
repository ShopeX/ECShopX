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

namespace ChinaumsPayBundle\Services;
use PaymentBundle\Services\Payments\ChinaumsPayService;
use AftersalesBundle\Services\AftersalesRefundService;
use OrdersBundle\Services\TradeService;
use OrdersBundle\Events\NormalOrdersBrokerageEvent;
use OrdersBundle\Entities\NormalOrders;
use CommunityBundle\Entities\CommunityBrokerageLog;


class UmsService
{

    public function __construct()
    {
        app('registry')->getManager('default')->clear();
        $this->umsPayServ = new ChinaumsPayService;
        $this->connect   = app('registry')->getConnection('default');
        $this->normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $this->communityBrokerageLogRepository = app('registry')->getManager('default')->getRepository(CommunityBrokerageLog::class);
    }

    public function tsUnifiedOrder()
    {
        $result = [];
        //ojmo45V5aC5rItOzlTgvFx9_DF4A
        //ob9OW5ALmchg7hYtlLe50_UW1hzs
        //oxMqp4qKJTuqPAs1NlF3RZaPS_dI
        try {
            $params = '{"company_id":"1","user_id":"11","total_fee":100,"detail":"\u7cbd\u5b50","order_id":"3811572000090001","trade_id":"demo3811572000190001","body":"\u7cbd\u5b50","open_id":"oxMqp4qKJTuqPAs1NlF3RZaPS_dI","wxa_appid":"wxb7b649fce37007ba","mobile":"15377412898","pay_type":"wxpay","pay_fee":100,"discount_fee":0,"discount_info":[],"fee_rate":1,"fee_type":"CNY","fee_symbol":"\uffe5","shop_id":0,"distributor_id":2,"trade_source_type":"normal","return_url":"","distributor_info":{"distributor_id":"1","shop_id":"0","is_distributor":true,"company_id":"1","mobile":"18434286466","address":"\u5317\u8521","name":"\u5f20\u4e09\u7684\u5e97\u94fa","auto_sync_goods":false,"logo":"https:\/\/shopex-onex-yundian-image.oss-cn-shanghai.aliyuncs.com\/willmaruat\/image\/1\/2022\/04\/24\/3b8f4a3afee5279329e7f90f4b4af0dbTCkVtXmzjpHHznnNWILxi2BBqUHRZIyd","contract_phone":"","banner":"","contact":"\u5f20\u4e09","is_valid":"true","lng":"121.564986","lat":"31.174398","child_count":0,"is_default":false,"is_audit_goods":false,"is_ziti":true,"regions_id":["310000","310100","310115"],"regions":["\u4e0a\u6d77\u5e02","\u4e0a\u6d77\u5e02","\u6d66\u4e1c\u65b0\u533a"],"is_domestic":1,"is_direct_store":1,"province":"\u4e0a\u6d77\u5e02","is_delivery":true,"city":"\u4e0a\u6d77\u5e02","area":"\u6d66\u4e1c\u65b0\u533a","hour":"08:00-21:00","created":1650292726,"updated":1655347468,"shop_code":"01","wechat_work_department_id":0,"distributor_self":0,"regionauth_id":"0","is_open":"false","rate":"","is_dada":false,"business":null,"dada_shop_create":false,"review_status":false,"dealer_id":0,"split_ledger_info":null,"introduce":"","merchant_id":"0","distribution_type":0,"is_require_subdistrict":true,"is_require_building":true,"store_address":"\u4e0a\u6d77\u5e02\u6d66\u4e1c\u65b0\u533a\u5317\u8521","store_name":"\u5f20\u4e09\u7684\u5e97\u94fa","phone":"18434286466"},"authorizer_appid":""}';

            $params = json_decode($params,true);
            $wxaappid = 'wxb7b649fce37007ba';
            $authorizerAppId = '';

            $result = $this->umsPayServ->doPay($authorizerAppId, $wxaappid, $params);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return $result;
    }

    public function tsUmsRefund($resubmit = false)
    {

        $inFilter['create_time|gt'] = '1651334400';
        $inFilter['create_time|lt'] = '1656604800';
        $inFilter['order_status|in'] = ['PAYED','DONE','WAIT_BUYER_CONFIRM'];
        $inFilter['cancel_status|in'] = ['NO_APPLY_CANCEL'];
        $inFilter['order_type'] = 'normal';
        $inFilter['order_class|neq'] = 'community';

        $page = 1;
        do {
            $offset = ($page - 1) * 200;
            $orders = $this->normalOrdersRepository->getList($inFilter, $offset, 200) ?? [];
            foreach ($orders as $key => $value) {
                $brokerageInfo = $this->communityBrokerageLogRepository->getInfo([
                    'user_id' => $value['user_id'],
                    'journal_type' =>1,
                    'order_id' => $value['order_id']
                ]);
                if (!($brokerageInfo ?? [])) {
                    $eventData = [
                        'order_id' => $value['order_id'],
                        'company_id' => $value['company_id'],
                        'user_id' => $value['user_id'],
                        'brokerage' => $value['total_fee'],
                        'journalType' => 1,
                        'status' => true,
                        'record' => '订单完成结算佣金',
                    ];
                    event(new NormalOrdersBrokerageEvent($eventData));
                }
                
            };
        $page++;
        } while ($orders);
        echo 'succ';
        exit;
        
        $result = [];
        $refund_filter = [
            'refund_bn' => '2202206301025753525',
            'company_id' => 1
        ];
        $aftersalesRefundService = new AftersalesRefundService();
        $refundData = $aftersalesRefundService->getInfo($refund_filter);
        if ( ($refundData['refund_status']??'') && $refundData['refund_status'] != 'AUDIT_SUCCESS') {
            return false;
        }

        $tradeService = new TradeService();
        $trade = $tradeService->getInfo(['company_id' => 1, 'trade_id' => $refundData['trade_id'], 'trade_state' => 'SUCCESS']);
        $refundData['pay_fee'] = $trade['pay_fee']; // 支付单原来支付总金额,用于某些支付需要传原始支付金额
        try {
           
            $wxaappid = 'wx37e130294ed62d42';
            $result = $this->umsPayServ->doRefund(1, $wxaappid, $refundData, $resubmit);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return $result;
    }

    //查询订单
    public function tsUmsQueryOrd()
    {
        $result = [];
        try {
            $company_id = 1;
            $trade_id = '013834560000260045';
            $result = $this->umsPayServ->getPayOrderInfo($company_id, $trade_id);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return $result;
    }

    //查询退款单
    public function tsUmsQueryRefs()
    {
        $result = [];
        try {
            $company_id = 1;
            $refund_bn = '2202206301025753525';
            $result = $this->umsPayServ->getRefundOrderInfo($company_id, $refund_bn);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        return $result;
    }
    
}
