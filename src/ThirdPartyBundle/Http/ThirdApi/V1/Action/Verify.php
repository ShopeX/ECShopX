<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;

use SystemLinkBundle\Services\OmsQueueLogService;
use ThirdPartyBundle\Http\Controllers\Controller as Controller;
use ThirdPartyBundle\Services\SaasCertCentre\CertService;

class Verify extends Controller
{
    public function saasErpApi(Request $request)
    {
        $params = $request->all();

        app('log')->debug('saaserp request params=>:'.var_export($params, 1)."\n");

        foreach ((array)$params as $key => $val) {
            $params[$key] = trim($val);
        }
        $saasErpAct = [
            'get_orders_info' => 'Order@getOrderInfo', // ome获取订单详情
            'ome_create_delivery' => 'Delivery@createDelivery', // ome订单发货、添加退货单
            'update_store' => 'Item@updateItemStore', // ome更新商品库存
            'update_order_status' => 'Order@updateOrderReviewStatus', //更新订单状态
            'get_return_status' => 'Aftersales@updateAftersalesStatus', // ome 更新售后申请单
            'ome_create_reimburse' => 'Refund@updateOrderRefund', //ome处理退换申请（同意、拒绝）
            // 'store.trade.invoice'                 => 'Order@ReceiveOrderInvoice', //发票信息接收
             'b2c.invoice.send' => 'Order@InvoiceCallback', //发票状态接收
        ];

        if (!isset($params['act']) || !isset($saasErpAct[trim($params['act'])]) || !$saasErpAct[trim($params['act'])]) {
            app('log')->debug('saaserp request result=>:'.$params['act'].'接口不存在');
            $this->api_response('fail', '接口不存在');
        }

        list($ctl, $act) = explode('@', trim($saasErpAct[$params['act']]));

        if (!$ctl || !$act) {
            app('log')->debug('saaserp request result=>:'.$ctl.'或'.$act.'方法不存在');
            $this->api_response('fail', '方法不存在');
        }

        //根据节点号获取 company_id
        $nodeId = $params['to_node_id'] ?? '';
        $certService = new CertService();
        $companyId = $certService->getCompanyId($nodeId);
        $params['company_id'] = $companyId;

        //记录接口日志
        $logId = 0;
        $method = $params['act'] ?? 'none';
        $result = [];
        $logResult = $this->saveResponseLog($method, 0, $params, $result);
        if ($logResult) {
            $logId = $logResult['id'] ?? 0;
        }

        $className = 'ThirdPartyBundle\Http\ThirdApi\V1\Action\\'.$ctl;

        $ctlObj = new $className($logId, $companyId);

        return  $ctlObj->$act($request);
    }

    private function saveResponseLog($api, $runtime, $params, $result)
    {
        $logParams = [
            'result' => $result,
            'runtime' => $runtime,
            'company_id' => $params['company_id'],
            'api_type' => 'response',
            'worker' => $api,
            'params' => json_encode($params, 256),
        ];
        if (isset($result['data']['rsp']) && $result['data']['rsp'] == 'succ') {
            $logParams['status'] = 'success';
        } else {
            $logParams['status'] = 'fail';
        }

        $omsQueueLogService = new OmsQueueLogService();
        $logResult = $omsQueueLogService->create($logParams);
        return $logResult;
    }
}
