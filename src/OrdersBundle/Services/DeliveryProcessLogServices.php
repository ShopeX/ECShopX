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

namespace OrdersBundle\Services;

use Dingo\Api\Exception\ResourceException;
use Illuminate\Support\Arr;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersRelDada;
use OrdersBundle\Entities\Trade;
use OrdersBundle\Repositories\NormalOrdersRelDadaRepository;

/**
 * 获取发货单操作记录日志 | 同城配运送日志
 * Class DeliveryProcessLogServices
 * @package OrdersBundle\Services
 */
class DeliveryProcessLogServices
{
    public function getListByOrderData(&$orderData)
    {
        $order_info = $orderData['orderInfo'];
        $trade_info = $orderData['tradeInfo'];
        $result = [];
        $filter = [
            'order_id' => $order_info['order_id'],
            'company_id' => $order_info['company_id'],
        ];
        // 下单时间
        $result[] = $this->getOrderCreateNode($order_info['create_time']);
        // 付款时间
        if ($trade_info && $trade_info['timeExpire']) {
            $result[] = $this->getPayNode($trade_info['timeExpire']);
        }
        // 物流日志
        $process_log_service = new OrderProcessLogService();
        if ($order_info['receipt_type'] == 'dada') {

            // 接单时间
            /** @var NormalOrdersRelDadaRepository $order_rel_repo */
            $order_rel_repo = app('registry')->getManager('default')->getRepository(NormalOrdersRelDada::class);
            $dada_info = $order_rel_repo->getInfo($filter);
            if ($dada_info && $dada_info['accept_time']) {
                $result[] = $this->getAcceptNode($dada_info['accept_time']);
            }
            // 查询dada物流日志
            $log_filter = array_merge($filter, ['remarks' => '同城配送']);
            $logs = $process_log_service->getLists($log_filter, '*', 1, -1, ['create_time' => 'asc', 'id' => 'desc']);
            foreach ($logs as $log) {
                $result[] = $this->getNode($log['create_time'], $log['detail'], 1);
            }
        }elseif ($order_info['receipt_type'] == 'merchant'){
            $log_filter = array_merge($filter, ['is_show' => true]);
            $logs = $process_log_service->getLists($log_filter, '*', 1, -1, ['create_time' => 'asc', 'id' => 'desc']);
            foreach ($logs as $log) {
                $tmp = $this->getNode($log['create_time'], $log['remarks'], 1);
                $tmp['delivery_remark'] = $log['delivery_remark'];
                $tmp['pics'] = json_decode($log['pics'],true);
                $result[] = $tmp;
            }
        } else {
            // 下单时间
            // 查询发货物流日志
            $log_filter = array_merge($filter, ['remarks' => '订单发货']);
            $logs = $process_log_service->getLists($log_filter, '*', 1, -1, ['create_time' => 'asc', 'id' => 'desc']);
            foreach ($logs as $log) {
                // 解析日志详情
                if (strpos($log['detail'], '信息修改')) {
                    $result[] = $this->getNode($log['create_time'], '物流信息修改');
                } else {
                    $result[] = $this->getNode($log['create_time'], '商家已发货');
                }
            }
        }
        // 确认签收时间
        if ($order_info['end_time']) {
            if ($order_info['receipt_type'] == 'dada' && $order_info['dada']['dada_status'] == '10') {
                // 已完成未妥投的dada订单，追加未妥投时间
                $result[] = $this->getNode($order_info['end_time'], '未妥投');
            } else {
                $result[] = $this->getOverNode($order_info['end_time']);
            }
        }
        $result = Arr::sort($result, function ($result) {
            return $result['time'];
        });
        return array_values($result);
    }

    /**
     * 获取完整日志列表
     * @param $params
     * @return array
     */
    public function getList($params)
    {
        // IDX: 2367340174
        $filter = [
            'order_id' => $params['order_id'],
            'company_id' => $params['company_id'],
        ];
        $order_repo = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $order_info = $order_repo->getInfo($filter);
        if (!$order_info) {
            throw new ResourceException('订单不存在');
        }
        $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
        $trade = $tradeRepository->getTradeList($filter);
        $trade_info = null;
        if ($trade['list']) {
            $trade_info = $trade['list'][0];
        }
        $orderData = [
            'orderInfo' => $order_info,
            'tradeInfo' => $trade_info,
        ];
        return $this->getListByOrderData($orderData);
    }

    public function getOrderCreateNode($create_time)
    {
        return $this->getNode($create_time, '买家已下单');
    }

    public function getPayNode($pay_time)
    {
        return $this->getNode($pay_time, '买家已付款');
    }

    public function getAcceptNode($delivery_time)
    {
        return $this->getNode($delivery_time, '商家已接单');
    }

    public function getOverNode($end_time)
    {
        return $this->getNode($end_time, '收货人确认签收');
    }

    private function getNode($time, $msg, $level = 0)
    {
        return [
            'time' => intval($time),
            'msg' => $msg,
            'level' => $level,
            'delivery_remark'=>'',
            'pics'=>[]
        ];
    }
}
