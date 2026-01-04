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

namespace ThirdPartyBundle\Services\ShansongCenter;

use Dingo\Api\Exception\ResourceException;

use OrdersBundle\Entities\NormalOrdersRelDada;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Events\OrderProcessLogEvent;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Services\CompanyRelShansongService;
use ThirdPartyBundle\Services\Map\MapService;

use ThirdPartyBundle\Services\ShansongCenter\Api\OrderCalculateApi;
use ThirdPartyBundle\Services\ShansongCenter\Api\OrderPlaceApi;
use ThirdPartyBundle\Services\ShansongCenter\Api\AbortOrderApi;
use ThirdPartyBundle\Services\ShansongCenter\Api\ConfirmGoodsReturnApi;
use ThirdPartyBundle\Services\ShansongCenter\Client\Request;

use DistributionBundle\Services\DistributorService;
use WorkWechatBundle\Jobs\sendDeliveryKnightAcceptNoticeJob;
use WorkWechatBundle\Jobs\sendDeliveryKnightArriveNoticeJob;
use WorkWechatBundle\Jobs\sendDeliveryKnightCancelNoticeJob;
use WorkWechatBundle\Jobs\sendFinishedFailNoticeJob;
use ThirdPartyBundle\Events\TradeUpdateEvent as SaasErpUpdateEvent;

class OrderService
{
    use GetOrderServiceTrait;

    /**
     * 商家接单
     * @param  string $companyId 企业Id
     * @param  string $orderId   订单号
     * @param  array $operator  管理员信息 operator_type:管理员类型 operator_id:管理员id
     */
    public function businessReceipt($companyId, $orderId, $operator)
    {
        // ShopEx EcShopX Core Module
        $normalOrdersRelDadaRepository = app('registry')->getManager('default')->getRepository(NormalOrdersRelDada::class);
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
        ];
        $info = $normalOrdersRelDadaRepository->getInfo($filter);
        if (!$info) {
            throw new ResourceException('未查询到闪送订单');
        }
        if ($info['dada_status'] != '0') {
            throw new ResourceException('闪送订单状态不正确，无需此操作');
        }
        $update_data = [
            'dada_status' => 1,
            'accept_time' => time(),
        ];
        $this->orderPlaceApi($companyId, $info['dada_delivery_no']);
        // 修改订单状态
        $normalOrdersRelDadaRepository->updateOneBy($filter, $update_data);
        // 记录订单日志
        $orderProcessLog = [
            'order_id' => $orderId,
            'company_id' => $companyId,
            'operator_type' => $operator['operator_type'] ?? 'system',
            'operator_id' => $operator['operator_id'] ?? 0,
            'remarks' => '商家接单',
            'detail' => '订单号：' . $orderId . '，商家已接单',
            'params' => [],
        ];
        event(new OrderProcessLogEvent($orderProcessLog));
        return true;
    }

    /**
     * 商家确认退回
     * @param  string $companyId 企业Id
     * @param  string $orderId   订单号
     * @param  array $operator  管理员信息 operator_type:管理员类型 operator_id:管理员id
     */
    public function confirmGoods($companyId, $orderId, $operator)
    {
        $normalOrdersRelDadaRepository = app('registry')->getManager('default')->getRepository(NormalOrdersRelDada::class);
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
        ];
        $info = $normalOrdersRelDadaRepository->getInfo($filter);
        if (!$info) {
            throw new ResourceException('未查询到闪送订单');
        }
        if ($info['dada_status'] != '9') {
            throw new ResourceException('订单状态不正确，无需此操作');
        }

        $this->confirmGoodsReturn($companyId, $info['dada_delivery_no']);
        // 修改订单状态
        // $orderAssociationService = new OrderAssociationService();
        // $order = $orderAssociationService->getOrder($companyId, $orderId);
        // if (!$order) {
        //     throw new ResourceException('此订单不存在！');
        // }
        // $orderService = $this->getOrderServiceByOrderInfo($order);
        // $params = [
        //     'company_id' => $companyId,
        //     'order_id' => $orderId,
        //     'user_id' => $order['user_id'],
        // ];
        // $result = $orderService->confirmReceipt($params, $operator);
        // $update_data = [
        //     'dada_status' => 10,
        //     'delivered_time' => time(),
        // ];
        // $normalOrdersRelDadaRepository->updateOneBy($filter, $update_data);
        return true;
    }

    /**
     * 妥投异常之物品返回完成,请求闪送
     * @param string $companyId  企业Id
     * @param string $orderId 订单号
     */
    public function confirmGoodsReturn($companyId, $deliveryNo)
    {
        $params = [
            'issOrderNo' => $deliveryNo,
        ];
        $confirmGoodsReturnApi = new ConfirmGoodsReturnApi(json_encode($params));
        $client = new Request($companyId, $confirmGoodsReturnApi);
        $resp = $client->makeRequest();
        if ($resp->status == 'fail') {
            throw new ResourceException($resp->msg);
        }

        return true;
    }

    /**
     * 查询运费后发单接口,请求达达
     * @param string $companyId  企业Id
     * @param string $deliveryNo 达达平台订单号(查询订单运费接口返回)
     */
    public function orderPlaceApi($companyId, $deliveryNo)
    {
        $params = [
            'issOrderNo' => $deliveryNo,
        ];
        $orderPlaceApi = new OrderPlaceApi(json_encode($params));
        $client = new Request($companyId, $orderPlaceApi);
        $resp = $client->makeRequest();
        if ($resp->status == 'fail') {
            throw new ResourceException($resp->msg);
        }

        return true;
    }

    /**
     * 获取闪送的运费
     * @param  array $orderData 订单数据
     * @return array            处理完运费的订单数据
     */
    public function getFreightFee($orderData, $reAddOrder = false)
    {
        $orderStruct = $this->getOrderStruct($orderData);
        $orderCalculateApi = new OrderCalculateApi(json_encode($orderStruct));

        $client = new Request($orderData['company_id'], $orderCalculateApi);
        $resp = $client->makeRequest();
        if ($resp->status == 'success') {
            $companyRelShansongService = new CompanyRelShansongService();
            $config = $companyRelShansongService->getInfo(['company_id' => $orderData['company_id']]);
            $orderData['dada_delivery_no'] = $resp->result['orderNumber'];
            if ($config['freight_type'] == 1 && !$reAddOrder) {
                $orderData['freight_fee'] = $resp->result['totalFeeAfterSave'];
                $orderData['total_fee'] = $orderData['total_fee'] + $orderData['freight_fee'];
            }
        } else {
            throw new ResourceException($resp->msg);
        }
        return $orderData;
    }

    /**
     * 获取请求闪送接口的订单结构体
     * @param  array $orderData 订单数据
     * @return array            处理完成的订单数据
     */
    public function getOrderStruct($orderData)
    {
        $storeInfo = $this->__getStoreInfo($orderData['company_id'], $orderData['distributor_id']);
        $toAddress = MapService::make($orderData['company_id'])->getLatAndLng($orderData['receiver_city'], $orderData['receiver_address']);
        $orderStruct = [
            'cityName' => $orderData['receiver_city'],
            'appointType' => 0,
            'storeId' => $storeInfo['shansong_store_id'],
            'sender' => [
                'fromAddress' => $storeInfo['province'].$storeInfo['city'].$storeInfo['area'].$storeInfo['address'],
                'fromSenderName' => $storeInfo['contact'],
                'fromMobile' => $storeInfo['mobile'],
                'fromLatitude' => $storeInfo['lat'],
                'fromLongitude' => $storeInfo['lng'],
            ],
            'receiverList' => [
                'orderNo' => $orderData['order_id'],
                'toAddress' => $orderData['receiver_state'].$orderData['receiver_city'].$orderData['receiver_district'].$orderData['receiver_address'],
                'toLatitude' => $toAddress->getLat(),
                'toLongitude' => $toAddress->getLng(),
                'toReceiverName' => $orderData['receiver_name'],
                'toMobile' => $orderData['receiver_mobile'],
                'goodType' => $storeInfo['business'],
                'weight' => $this->__getCargoWeight($orderData['items']),
            ],
        ];
        return $orderStruct;
    }

    /**
     * 根据店铺id获取店铺的编号
     * 如果distributor_id=0,获取总部自提点的数据
     * @param  string $companyId     企业ID
     * @param  string $distributorId 店铺ID
     * @return string                店铺编号
     */
    private function __getStoreInfo($companyId, $distributorId)
    {
        // 后续增加条件，是否开启同城配
        $distributorService = new DistributorService();
        if (intval($distributorId) > 0) {
            return $distributorService->getInfo(['company_id' => $companyId, 'distributor_id' => $distributorId]);
        } else {
            return $distributorService->getDistributorSelf($companyId, true);
        }
    }

    /**
     * 根据订单中的商品，计算总重量
     * @param  array $items 订单的商品数据
     */
    private function __getCargoWeight($items)
    {
        $weight = array_column($items, 'weight');
        return array_sum($weight);
    }

    /**
     * 根据订单中的商品，获取达达商品结构
     * @param  array $items 订单中的商品数据
     * @return array        达达的商品结构
     */
    private function __getProductList($items)
    {
        $product_list = [];
        foreach ($items as $key => $item) {
            $product_list[] = [
                'sku_name' => $item['item_name'],
                'src_product_no' => $item['item_bn'],
                'count' => $item['num'],
                'unit' => $item['item_unit'],
            ];
        }
        return $product_list;
    }

    private function __mappingDadaStatus($status, $subStatus) {
        switch ($status) {
            case 20:
            return '1';
            case 30:
            switch ($subStatus) {
                case 1:
                return '2';
                case 2:
                return '100';
            }
            case 40:
            switch ($subStatus) {
                case 1:
                case 2:
                case 4:
                return '3';
                case 3:
                return '9';
            }
            case 50:
            return '4';
            case 60:
            return '5';
        }
    }

    private function __mappingDadaCancelFrom($abortType) {
        switch ($abortType) {
            case 1:
            return '2';
            case 3:
            return '1';
            case 10:
            return '3';
        }
    }

    /**
     * 保存订单和达达的关联数据
     * @param  array $data 关联数据
     */
    public function saveOrderRelDada($data)
    {
        $normalOrdersRelDadaRepository = app('registry')->getManager('default')->getRepository(NormalOrdersRelDada::class);
        return $normalOrdersRelDadaRepository->create($data);
    }

    /**
     * 订单回调，修改订单状态
     * @param  string $companyId 企业ID
     * @param  array $data      回调的数据
     */
    public function callbackUpdateOrderStatus($companyId, $data)
    {
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $data['orderNo']);
        if (!$order) {
            throw new ResourceException('未查询到闪送订单');
        }
        $orderService = $this->getOrderServiceByOrderInfo($order);
        $orderDetail = $orderService->getOrderInfo($companyId, $data['orderNo']);
        $dada_status = $orderDetail['orderInfo']['dada']['dada_status'];
        $dada_cancel_from = $orderDetail['orderInfo']['dada']['dada_cancel_from'];
        $filter = [
            'company_id' => $companyId,
            'order_id' => $data['orderNo'],
        ];

        $status = $this->__mappingDadaStatus($data['status'], $data['subStatus']);
        if ($status == '1') {
            return true;
        }
        $data['order_status'] = $status;
        $update_data = [
            'dada_status' => $status,
            'dm_id' => $data['courier']['id'],
            'dm_name' => $data['courier']['name'],
            'dm_mobile' => $data['courier']['mobile'],
        ];
        $remarks = '同城配送';
        $detail = '';
        $orderLog = false;
        // 订单状态(待接单＝1,待取货＝2,配送中＝3,已完成＝4,已取消＝5, 指派单=8,妥投异常之物品返回中=9, 妥投异常之物品返回完成=10, 骑士到店=100,创建达达运单失败=1000 ）
        $operator = [
            'operator_type' => 'system',
            'operator_id' => 0,
        ];
        switch ($status) {
            case '2':// 待取货
                if (!in_array($dada_status, ['1'])) {
                    throw new ResourceException('要修改的状态不正确 订单号： '.$data['orderNo'].','.$dada_status.'=>'.$status);
                }
                $orderLog = true;
                //$remarks = '骑士接单';
                $detail = '骑士已接单';
                break;
            case '100':// 骑士到店
                if (!in_array($dada_status, ['2'])) {
                    throw new ResourceException('要修改的状态不正确 订单号： '.$data['orderNo'].','.$dada_status.'=>'.$status);
                }
                $orderLog = true;
                //$remarks = '骑士到店';
                $detail = '骑士到店取货';
                break;
            case '3':// 配送中
                if (!in_array($dada_status, ['100', '2'])) {
                    throw new ResourceException('要修改的状态不正确 订单号： '.$data['orderNo'].','.$dada_status.'=>'.$status);
                }
                $orderLog = true;
                //$remarks = '骑士已取货';
                $detail = '骑士配送中';

                $update_data['pickup_time'] = time();// 取货时间
                break;
            case '4':// 已完成
                if (!in_array($dada_status, ['3', '9'])) {
                    throw new ResourceException('要修改的状态不正确 订单号： '.$data['orderNo'].','.$dada_status.'=>'.$status);
                }

                // 主单改为已发货
                $deliveryParams = [
                    'company_id' => $companyId,
                    'delivery_type' => 'batch',
                    'order_id' => $data['orderNo'],
                    'delivery_corp' => 'OTHER',
                    'delivery_code' => 'shansong',
                    'type' => 'new',
                    'operator_type' => 'system',
                    'operator_id' => 0,
                ];
                $orderService->delivery($deliveryParams);

                // 主单改为已完成
                $confirmParams = [
                    'company_id' => $companyId,
                    'order_id' => $data['orderNo'],
                    'user_id' => $orderDetail['orderInfo']['user_id'],
                ];
                $orderService->confirmReceipt($confirmParams, $operator);
                $update_data['delivered_time'] = time();// 送达时间

                //送达触发订单oms更新的事件
                event(new SaasErpUpdateEvent($orderDetail['orderInfo']));
                break;
            case '5':// 已取消
                if (!in_array($dada_status, ['1', '2', '100', '3', '9']) || $dada_cancel_from > 0) {
                    throw new ResourceException('要修改的状态不正确 订单号： '.$data['orderNo'].','.$dada_status.'=>'.$status);
                }
                $cancelFrom = $this->__mappingDadaCancelFrom($data['abortType']);
                $update_data['dada_cancel_from'] = $cancelFrom;
                if ($cancelFrom == '2') {
                    $confirmCancelParams = [
                        'company_id' => $companyId,
                        'order_id' => $data['orderNo'],
                        'check_cancel' => 1,
                    ];
                    $orderService->confirmCancelOrder($confirmCancelParams);
                } else {
                    $cancelParams = [
                        'company_id' => $companyId,
                        'order_id' => $data['orderNo'],
                        'user_id' => $orderDetail['orderInfo']['user_id'],
                        'cancel_from' => 'system',
                        'cancel_reason' => 'other_reason',
                        'other_reason' => $data['abortReason'],
                    ];
                    $orderService->cancelOrder($cancelParams);
                }
                break;
            case '9':// 妥投异常物品返回中
                if (!in_array($dada_status, ['3'])) {
                    throw new ResourceException('要修改的状态不正确 订单号： '.$data['orderNo'].','.$dada_status.'=>'.$status);
                }
                $orderLog = true;
                $remarks = '同城配送 - 妥投异常';
                $detail = '订单号：' . $data['orderNo'] . '，订单妥投异常物品返回中';
                break;
            default:
                app('log')->info('shansongCallback request error msg:状态无需处理 订单号： '.$data['orderNo'].','.$dada_status.'=>'.$status);
                return true;
                break;
        }
        $normalOrdersRelDadaRepository = app('registry')->getManager('default')->getRepository(NormalOrdersRelDada::class);
        $normalOrdersRelDadaRepository->updateOneBy($filter, $update_data);

        // 记录订单日志
        if ($orderLog) {
            $orderProcessLog = [
                'order_id' => $data['orderNo'],
                'company_id' => $companyId,
                'operator_type' => 'system',
                'operator_id' => 0,
                'remarks' => $remarks,
                'detail' => $detail,
                'params' => $data,
            ];
            event(new OrderProcessLogEvent($orderProcessLog));
        }
        $this->sendWorkWechatNotify($companyId, $data);
        return true;
    }

    /**
     * 取消订单,请求达达
     * @param string $companyId  企业Id
     * @param string $orderId 订单号
     * @param string $cancelReason 取消原因
     */
    public function formalCancel($companyId, $orderId, $cancelReason)
    {
        $normalOrdersRelDadaRepository = app('registry')->getManager('default')->getRepository(NormalOrdersRelDada::class);
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
        ];
        $info = $normalOrdersRelDadaRepository->getInfo($filter);
        if (!$info) {
            throw new ResourceException('未查询到闪送订单');
        }
        if ($info['dada_status'] != '0') {
            throw new ResourceException('闪送订单状态不正确，无需此操作');
        }

        $params = [
            'issOrderNo' => $info['dada_delivery_no'],
        ];
        $abortOrderApi = new AbortOrderApi(json_encode($params));
        $client = new Request($companyId, $abortOrderApi);
        $resp = $client->makeRequest();
        if ($resp->status == 'fail') {
            throw new ResourceException($resp->msg);
        }

        return true;
    }

    /**
     * 重发订单,请求达达
     * @param string $orderData 订单详情数据
     */
    public function reAddOrder($data)
    {
        return true;
    }
    /**
     * 发送企业微信消息通知
     * @param string $company_id 企业Id
     * @param array $data 回调数据
     */
    public function sendWorkWechatNotify($company_id, $data)
    {
        ## 取消
        if ($data['order_status'] == 5 && $data['cancel_from'] == 1) {
            $gotoJob = (new sendDeliveryKnightCancelNoticeJob($company_id, $data['orderNo']))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        ## 已接单
        if ($data['order_status'] == 2) {
            $gotoJob = (new sendDeliveryKnightAcceptNoticeJob($company_id, $data['orderNo']))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        ## 已到店
        if ($data['order_status'] == 100) {
            $gotoJob = (new sendDeliveryKnightArriveNoticeJob($company_id, $data['orderNo']))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        ## 妥投异常
        if ($data['order_status'] == 9) {
            $gotoJob = (new sendFinishedFailNoticeJob($company_id, $data['orderNo']))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        return true;
    }
}
