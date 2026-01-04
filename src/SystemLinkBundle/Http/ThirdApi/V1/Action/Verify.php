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

namespace SystemLinkBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;

use SystemLinkBundle\Http\Controllers\Controller as Controller;

use SystemLinkBundle\Services\OmsQueueLogService;

class Verify extends Controller
{
    public function omeApi(Request $request)
    {
        $params = $request->all();

        app('log')->debug('ome_request=>:'.var_export($params, 1));

        foreach ((array)$params as $key => $val) {
            $params[$key] = trim($val);
        }

        $omeAct = [
            'store.trade.fullinfo.get' => 'Order@getOrderInfo', // ome获取订单详情
            'store.trade.invoice' => 'Order@ReceiveOrderInvoice', //发票信息接收
            'store.trade.update.status' => 'Order@updateOrderReviewStatus', //订单审核修改状态
            'store.trade.status.update' => 'Order@updateOrderStatus', //订单状态修改，目前由于关闭订单

            'store.logistics.offline.send' => 'Delivery@createDelivery', // ome订单发货
            'store.trade.reship.add' => 'Delivery@returnDelivery', //确认退货

            'store.trade.refund.status.update' => 'Refund@updateRefundStatus', //ome同意订单退款
            'store.refund.refuse' => 'Refund@closeOrderRefund', // ome拒绝订单退款
            'store.trade.refund.add' => 'Refund@updateOrderRefund', //ome同意订单退款

            'store.trade.aftersale.status.update' => 'Aftersales@updateAftersalesStatus', // ome 更新售后申请单
            'store.trade.aftersale.add' => 'Aftersales@omsAddAftersale', // ome 更新售后申请单

            'store.items.quantity.list.update' => 'Item@updateItemStore', // ome更新商品库存
            'ome.items.create' => 'Item@createItems', // 添加商品
            'ome.category.create' => 'Item@createCategory', // 添加商品分类
            'ome.member.create' => 'Item@creatMember', // 添加商品 这个方法不存在？？？
            'ome.item.up' => 'Item@uploadItem', // 上传商品
            'ome.user.up' => 'Item@uploadUser', // 上传会员
        ];
        //$api->post('/wxapp/member',  ['as' => 'front.wxapp.member.create',  'uses' => 'Members@creatMember']);


        if (!isset($params['method']) || !isset($omeAct[trim($params['method'])]) || !$omeAct[trim($params['method'])]) {
            app('log')->debug('ome_request_result=>:'.$params['method'].'接口不存在');
            $this->api_response('fail', '接口不存在');
        }

        list($ctl, $act) = explode('@', trim($omeAct[$params['method']]));

        if (!$ctl || !$act) {
            app('log')->debug('ome_request_result=>:'.$ctl.'或'.$act.'方法不存在');
            $this->api_response('fail', '方法不存在');
        }

        //IP白名单检查
        $ipchk = $this->whiteIP();
        if (!$ipchk) {
            $this->api_response('fail', 'ip access denied');
        }

        //记录接口日志
        $logId = 0;
        $method = $params['method'] ?? 'none';
        $result = [];
        $logResult = $this->saveResponseLog($method, 0, $params, $result);
        if ($logResult) {
            $logId = $logResult['id'] ?? 0;
        }

        $className = 'SystemLinkBundle\Http\ThirdApi\V1\Action\\'.$ctl;

        $ctlObj = new $className($logId);

        return  $ctlObj->$act($request);
    }

    //淘打接口 白名单
    public function whiteIP()
    {
        //IP白名单检查  IP白名单列表
        /*
        $whiteIPList = array( '116.247.96.146',
                              '122.144.135.195',
                            );
        */
        if (config('common.oms_white_ip')) {
            $whiteIPList = explode(';', config('common.oms_white_ip'));
        } else {
            return true;
        }

        $whiteIP = $whiteIPList;
        $ip = $this->getIPtaoda();

        $ip_check = false;
        foreach ($ip as $k => $v) {
            if (in_array($v, $whiteIP)) {
                $ip_check = true;
            }
        }
        if (!$ip_check) {
            app('log')->debug('ome_request=>:'.var_export($ip, 1));
            //$this->api_response('fail','没有权限'.json_encode($ip),'');
        }
        return $ip_check;
    }

    public function getIPtaoda()
    {
        $realip = array();
        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $realip[] = $_SERVER["HTTP_X_FORWARDED_FOR"];
            }
            if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $realip[] = $_SERVER["HTTP_CLIENT_IP"];
            }
            if (isset($_SERVER["REMOTE_ADDR"])) {
                $realip[] = $_SERVER["REMOTE_ADDR"];
            }
            if (isset($_SERVER["HTTP_CDN_SRC_IP"])) {
                $realip[] = $_SERVER["HTTP_CDN_SRC_IP"];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                $realip[] = getenv("HTTP_X_FORWARDED_FOR");
            }
            if (getenv("HTTP_CLIENT_IP")) {
                $realip[] = getenv("HTTP_CLIENT_IP");
            }
            if (getenv("REMOTE_ADDR")) {
                $realip[] = getenv("REMOTE_ADDR");
            }
            if (getenv("HTTP_CDN_SRC_IP")) {
                $realip[] = getenv("HTTP_CDN_SRC_IP");
            }
        }
        return $realip;
    }

    private function saveResponseLog($api, $runtime, $params, $result)
    {
        $logParams = [
            'result' => $result,
            'runtime' => $runtime,
            'company_id' => config('common.system_companys_id'),//todo 这里要改
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
