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

namespace ThirdPartyBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;

use OrdersBundle\Services\OrderInvoiceService;
use ThirdPartyBundle\Http\Controllers\Controller as Controller;

use ThirdPartyBundle\Events\TradeFinishEvent;
use ThirdPartyBundle\Events\TradeRefundEvent;
use ThirdPartyBundle\Events\TradeAftersalesEvent;
use ThirdPartyBundle\Events\TradeAftersalesLogiEvent;
use ThirdPartyBundle\Events\TradeUpdateEvent as SaasErpUpdateEvent;

use OrdersBundle\Entities\ServiceOrders;

use ThirdPartyBundle\Services\SaasErpCentre\OrderService;



use OrdersBundle\Services\Orders\NormalOrderService;

use PromotionsBundle\Entities\PromotionGroupsTeam;

use AftersalesBundle\Entities\Aftersales;

use OrdersBundle\Services\TradeService;
use OrdersBundle\Services\UserOrderInvoiceService;
use AftersalesBundle\Services\AftersalesService;

class Order extends Controller
{
    // TEST-普通订单测试触发
    public function testEvent($order_id)
    {
        $eventData = new ServiceOrders();
        $eventData->setOrderId($order_id);
        $eventData->setCompanyId(config('common.system_companys_id'));
        $eventData->sourceType = 'normal';
        event(new SaasErpUpdateEvent($eventData));

        // $tradeService = new TradeService();
        // $tradeData = $tradeService->tradeRepository->getInfo(['order_id'=>$order_id]);
        // $tradeId = $tradeData['trade_id'];
        // $eventData = $tradeService->tradeRepository->find($tradeId);
        // $tradeService->finishEvents($eventData);
        // dd($eventData);exit;
        // event(new TradeFinishEvent($eventData));
    }

    // TEST-拼团订单测试触发
    public function testGroupEvent()
    {
        // Debug: 1e2364
        $eventData = new PromotionGroupsTeam();

        $eventData->setTeamId('2614889000068945');
        $eventData->sourceType = 'groups';
        // $eventData['order_class'] = 'groups';

        event(new TradeFinishEvent($eventData));
    }

    // TEST-发送退款单
    public function testRefundEvent()
    {
        $eventData = new ServiceOrders();
        // $eventData->setOrderId('2565683000268913');
        $eventData->setOrderId('2551713000111791');
        $eventData->setCompanyId(1);
        $eventData->setOrderSource('normal');
        event(new TradeRefundEvent($eventData));
    }

    // TEST-发送售后申请
    public function testAftersalesEvent()
    {
        $eventData = new Aftersales();
        // $eventData->setOrderId('2565683000268913');
        $eventData->setAftersalesBn('1910241707360118');
        $eventData->setOrderId('2850662000050018');
        $eventData->setCompanyId(1);
        $eventData->setItemId(2642);
        event(new TradeAftersalesEvent($eventData));
    }

    // TEST-更新退货物流信息
    public function testAfterLogiEvent()
    {
        // Debug: 1e2364
        $aftersalesService = new AftersalesService();
        $params = [
            'aftersales_bn' => 1910301941370118,
            'detail_id' => 17,
            'company_id' => 1,
            'order_id' => 2858783000100018,
        ];
        $eventData = $aftersalesService->aftersalesRepository->get($params);
        event(new TradeAftersalesLogiEvent($eventData));
    }

    // TEST-用户取消售后
    public function testAftersalesCancelEvent()
    {
        $eventData = new Aftersales();
        $eventData->setOrderId('2566577000098916');
        $eventData->setCompanyId(1);
        $eventData->setAftersalesBn('1812201010300951');
        // dd($eventData);exit;
        event(new TradeAftersalesEvent($eventData));
    }


    /**
     * SaasErp 获取订单详情
     * $request order_id
     */
    public function getOrderInfo(Request $request)
    {
        $params = $request->all();

        $rules = [
            'order_id' => ['required', '订单号缺少！'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage);
        }

        $normalOrderService = new NormalOrderService();

        $filter = ['order_id' => $params['order_id']];
        if ($this->companyId) {
            $filter['company_id'] = $this->companyId;
        }
        $tradeInfo = $normalOrderService->getInfo($filter);

        if (!$tradeInfo) {
            $this->api_response('fail', '未找到订单');
        }

        // $order_class = ['normal'];
        $order_class = ['normal', 'groups', 'seckill', 'shopadmin', 'shopguide'];

        if (!in_array($tradeInfo['order_class'], $order_class)) {
            $this->api_response('fail', $tradeInfo['order_class'].'类型订单禁止同步');
        }

        $sourceType = (strpos($tradeInfo['order_class'], 'normal') === 0 ? '' : 'normal_').$tradeInfo['order_class'];

        $orderService = new OrderService();
        $orderStruct = $orderService->getOrderStruct($tradeInfo['company_id'], $params['order_id'], $sourceType);

        if (!$orderStruct) {
            $this->api_response('fail', '获取订单信息失败');
        }

        $result['trade'] = $orderStruct;
        //单拉商品重新组织订单优惠信息
        $promotion_details = json_decode($result['trade']['promotion_details'], 1);
        unset($result['trade']['promotion_details']);
        $temp = array();
        $temp['promotiondetail'] = $promotion_details;
        $result['trade']['promotion_details'] = json_encode($temp);

        $this->api_response('true', '操作成功', $result);
    }

    //发票开票成功后记录发票下载地址
    public function ReceiveOrderInvoice(Request $request)
    {
        $orderId = $request->get('order_id');
        $invoice = $request->get('invoice_url');
        $orderInvoiceService = new UserOrderInvoiceService();
        $result = $orderInvoiceService->saveData($orderId, $invoice);
    }

    public function InvoiceCallback(Request $request)
    {

        $data = $request->all();

        $rules = [
            'invoice_memo' => ['required', '发票状态缺少！'],
            'blue_invoice_code' => ['required', '发票代码缺少！'],
            'pdf_info' => ['required_if:invoice_memo,2', '缺少发票文件！'],
            'blue_invoice_no' => ['required', '发票号缺少！'],
            'invoice_no' => ['required', '发票申请单号缺少！'],
            'order_bn' => ['required', '订单号缺少！'],
        ];

        $errorMessage = validator_params($data, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage);
        }

        $orderInvoiceService = new OrderInvoiceService();
        $result = $orderInvoiceService->handleInvoiceCallback($data);
        $status = isset($result['success']) && $result['success'] ? 'true' : 'fail';
        $this->api_response($status, $result['message'] ?? '', $result['data'] ?? []);
    }

    //todo 先把报错解决，流程后面等产品再理
    public function updateOrderReviewStatus(Request $request)
    {
        $orderId = $request->get('order_id');
        $status = $request->get('status');
        $type = $request->get('type');
        $update_time = $request->get('lastmodify');
        $remark = $request->get('reason');

        if (!$orderId) {
            $this->api_response('fail', '订单信息有误');
        }
        $flag = false;
        if ($status == 'dead' && $type == 'status') {
            $normalOrderService = new NormalOrderService();
            $filter = [
                'order_id' => $orderId,
            ];
            $result = $normalOrderService->normalOrdersRepository->getInfo($filter);
            if ($result['cancel_status'] == 'SUCCESS') {
                $flag = true;
            }
        }

        if ($flag) {
            $this->api_response('true', '操作成功', ['status' => true]);
        } else {
            $this->api_response('fail', '订单信息有误');
        }
    }

    // oms全额退款需要更新状态，以用来关闭订单，回滚库存
    public function updateOrderStatus(Request $request)
    {
        // tid=CN3007292745708000176748
        // status=TRADE_CLOSED
        // type=status
        // modify=2019-07-08 19:20:49
        // is_update_trade_status=true
        // reason=订单全额退款后取消！
        $orderId = $request->get('tid');
        $status = $request->get('status');
        $type = $request->get('type');
        $modify = $request->get('modify');
        $is_update_trade_status = $request->get('is_update_trade_status');
        if (!$orderId) {
            $this->api_response('fail', '订单信息有误');
        }
        $flag = false;
        if ($status == 'TRADE_CLOSED' && $type == 'status') {
            $normalOrderService = new NormalOrderService();
            $filter = [
                'order_id' => $orderId,
            ];
            $result = $normalOrderService->normalOrdersRepository->getInfo($filter);
            if ($result['cancel_status'] == 'SUCCESS') {
                $flag = true;
            }
        }


        if ($flag) {
            $this->api_response('true', '操作成功', ['status' => true]);
        } else {
            $this->api_response('fail', '订单信息有误');
        }
    }
}
