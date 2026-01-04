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

namespace ThirdPartyBundle\Services\DadaCenter;

use Dingo\Api\Exception\ResourceException;

use OrdersBundle\Entities\NormalOrdersRelDada;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Events\OrderProcessLogEvent;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Services\CompanyRelDadaService;

use ThirdPartyBundle\Services\DadaCenter\Api\QueryDeliverFeeApi;
use ThirdPartyBundle\Services\DadaCenter\Api\AddAfterQueryApi;
use ThirdPartyBundle\Services\DadaCenter\Api\FormalCancelApi;
use ThirdPartyBundle\Services\DadaCenter\Api\ReAddOrderApi;
use ThirdPartyBundle\Services\DadaCenter\Api\ConfirmGoodsApi;
use ThirdPartyBundle\Services\DadaCenter\Client\DadaRequest;

use DistributionBundle\Services\DistributorService;
use WorkWechatBundle\Jobs\sendDeliveryKnightAcceptNoticeJob;
use WorkWechatBundle\Jobs\sendDeliveryKnightArriveNoticeJob;
use WorkWechatBundle\Jobs\sendDeliveryKnightCancelNoticeJob;
use WorkWechatBundle\Jobs\sendFinishedFailNoticeJob;
use ThirdPartyBundle\Events\TradeUpdateEvent as SaasErpUpdateEvent;

class OrderService
{
    use GetOrderServiceTrait;

    public $callback = 'openapi/dada/callback';

    /**
     * 商家接单
     * @param  string $companyId 企业Id
     * @param  string $orderId   订单号
     * @param  array $operator  管理员信息 operator_type:管理员类型 operator_id:管理员id
     */
    public function businessReceipt($companyId, $orderId, $operator)
    {
        $normalOrdersRelDadaRepository = app('registry')->getManager('default')->getRepository(NormalOrdersRelDada::class);
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
        ];
        $info = $normalOrdersRelDadaRepository->getInfo($filter);
        if (!$info) {
            throw new ResourceException('未查询到达达相关数据');
        }
        if ($info['dada_status'] != '0') {
            throw new ResourceException('订单状态不正确，无需此操作');
        }
        $update_data = [
            'dada_status' => 1,
            'accept_time' => time(),
        ];
        if (time() - $info['update_time'] > 60 * 2 + 55) {
            $orderAssociationService = new OrderAssociationService();
            $order = $orderAssociationService->getOrder($companyId, $orderId);
            if (!$order) {
                throw new ResourceException('此订单不存在！');
            }
            $orderService = $this->getOrderServiceByOrderInfo($order);
            $orderDetail = $orderService->getOrderInfo($companyId, $orderId);
            $orderInfo = $orderDetail['orderInfo'];
            unset($orderDetail);
            $orderInfo['total_fee'] = bcsub($orderInfo['total_fee'], $orderInfo['freight_fee']);
            $orderInfo['total_fee'] = bcadd($orderInfo['total_fee'], $orderInfo['point_fee']);
            $orderInfo['freight_fee'] = 0;
            $orderData = $this->getFreightFee($orderInfo);
            $this->addAfterQuery($companyId, $orderData['dada_delivery_no']);
            $update_data['dada_delivery_no'] = $orderData['dada_delivery_no'];
        } else {
            $this->addAfterQuery($companyId, $info['dada_delivery_no']);
        }
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
            throw new ResourceException('未查询到达达相关数据');
        }
        if ($info['dada_status'] != '9') {
            throw new ResourceException('订单状态不正确，无需此操作');
        }

        $this->dadaConfirmGoods($companyId, $orderId);
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
        // 记录订单日志
        // $orderProcessLog = [
        //     'order_id' => $orderId,
        //     'company_id' => $companyId,
        //     'operator_type' => $operator['operator_type'] ?? 'system',
        //     'operator_id' => $operator['operator_id'] ?? 0,
        //     'remarks' => '确认退回',
        //     'detail' => '订单号：' . $orderId . '，妥投异常，商家确认退回',
        //     'params' => [],
        // ];
        // event(new OrderProcessLogEvent($orderProcessLog));
        return true;
    }

    /**
     * 妥投异常之物品返回完成,请求达达
     * @param string $companyId  企业Id
     * @param string $orderId 订单号
     */
    public function dadaConfirmGoods($companyId, $orderId)
    {
        $params = [
            'order_id' => $orderId,
        ];
        $confirmGoodsApi = new ConfirmGoodsApi(json_encode($params));
        $dada_client = new DadaRequest($companyId, $confirmGoodsApi);
        $resp = $dada_client->makeRequest();
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
    public function addAfterQuery($companyId, $deliveryNo)
    {
        $params = [
            'deliveryNo' => $deliveryNo,
        ];
        $addAfterQueryApi = new AddAfterQueryApi(json_encode($params));
        $dada_client = new DadaRequest($companyId, $addAfterQueryApi);
        $resp = $dada_client->makeRequest();
        if ($resp->status == 'fail') {
            throw new ResourceException($resp->msg);
        }

        return true;
    }

    /**
     * 获取达达的运费
     * @param  array $orderData 订单数据
     * @return array            处理完运费的订单数据
     */
    public function getFreightFee($orderData)
    {
        $orderStruct = $this->getOrderStruct($orderData);
        $queryDeliverFeeApi = new QueryDeliverFeeApi(json_encode($orderStruct));

        $dada_client = new DadaRequest($orderData['company_id'], $queryDeliverFeeApi);
        $resp = $dada_client->makeRequest();
        if ($resp->status == 'success') {
            $companyRelDadaService = new CompanyRelDadaService();
            $relDadaInfo = $companyRelDadaService->getInfo(['company_id' => $orderData['company_id']]);

            $orderData['dada_delivery_no'] = $resp->result['deliveryNo'];
            if ($relDadaInfo['freight_type'] == 1) {
                $orderData['freight_fee'] = bcmul($resp->result['fee'], 100);
                $orderData['total_fee'] = $orderData['total_fee'] > 0 ? $orderData['total_fee'] + $orderData['freight_fee'] : 0; // 订单总金额
            }
        } else {
            throw new ResourceException($resp->msg);
        }
        return $orderData;
    }

    /**
     * 获取请求达达接口的订单结构体
     * @param  array $orderData 订单数据
     * @return array            处理完成的订单数据
     */
    public function getOrderStruct($orderData)
    {
        $orderStruct = [
            'shop_no' => $this->__getShopNo($orderData['company_id'], $orderData['distributor_id']),
            'origin_id' => $orderData['order_id'],
            'city_code' => $this->__getCityCode($orderData['company_id'], $orderData['receiver_city']),
            'cargo_price' => bcdiv($orderData['total_fee'], 100, 2),
            'is_prepay' => 0,// 是否需要垫付 1:是 0:否 (垫付订单金额，非运费)
            'receiver_name' => $orderData['receiver_name'],
            'receiver_address' => $orderData['receiver_state'] . $orderData['receiver_city'] . $orderData['receiver_district'] . $orderData['receiver_address'],
            'callback' => config('common.api_base_url').$this->callback.'/'.$orderData['company_id'],
            'cargo_weight' => $this->__getCargoWeight($orderData['items']),
            'receiver_phone' => $orderData['receiver_mobile'],
            'info' => $orderData['remark'] ?? '',
            'product_list' => $this->__getProductList($orderData['items']),
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
    private function __getShopNo($companyId, $distributorId)
    {
        // 后续增加条件，是否开启同城配
        $distributorService = new DistributorService();
        if (intval($distributorId) > 0) {
            $distributor_info = $distributorService->getInfo(['company_id' => $companyId, 'distributor_id' => $distributorId]);
        } else {
            $distributor_info = $distributorService->getDistributorSelf($companyId, true);
        }
        return $distributor_info['shop_code'] ?? '';
    }

    /**
     * 根据城市名称获取城市的编码
     * @param  string $city_name 城市名称
     * @return string            城市编码
     */
    private function __getCityCode($company_id, $city_name)
    {
        $companyRelDadaService = new CompanyRelDadaService();
        $cityList = $companyRelDadaService->getCityList($company_id);
        if (!$cityList) {
            throw new ResourceException("当前城市不支持同城配，请重新选择收货地址");
        }
        $city_name = rtrim($city_name, '市');
        $cityCode = '';
        foreach ($cityList as $city) {
            if ($city_name == $city['cityName']) {
                $cityCode = $city['cityCode'];
                break;
            }
        }
        if (!$cityCode) {
            throw new ResourceException("当前城市不支持同城配，请重新选择收货地址");
        }
        return $cityCode;
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
        $order = $orderAssociationService->getOrder($companyId, $data['order_id']);
        if (!$order) {
            throw new ResourceException('未查询到达达相关数据');
        }
        $orderService = $this->getOrderServiceByOrderInfo($order);
        $orderDetail = $orderService->getOrderInfo($companyId, $data['order_id']);
        $dada_status = $orderDetail['orderInfo']['dada']['dada_status'];
        $dada_cancel_from = $orderDetail['orderInfo']['dada']['dada_cancel_from'];
        $filter = [
            'company_id' => $companyId,
            'order_id' => $data['order_id'],
        ];
        $update_data = [
            'dada_status' => $data['order_status'],
            'dm_id' => $data['dm_id'],
            'dm_name' => $data['dm_name'],
            'dm_mobile' => $data['dm_mobile'],
        ];
        $remarks = '同城配送';
        $detail = '';
        $orderLog = false;
        // 订单状态(待接单＝1,待取货＝2,配送中＝3,已完成＝4,已取消＝5, 指派单=8,妥投异常之物品返回中=9, 妥投异常之物品返回完成=10, 骑士到店=100,创建达达运单失败=1000 ）
        $operator = [
            'operator_type' => 'system',
            'operator_id' => 0,
        ];
        switch ($data['order_status']) {
            case '2':// 待取货
                if (!in_array($dada_status, ['1'])) {
                    throw new ResourceException('要修改的状态不正确 订单号： '.$data['order_id'].','.$dada_status.'=>'.$data['order_status']);
                }
                $orderLog = true;
                //$remarks = '骑士接单';
                $detail = '骑士已接单';
                break;
            case '100':// 骑士到店
                if (!in_array($dada_status, ['2'])) {
                    throw new ResourceException('要修改的状态不正确 订单号： '.$data['order_id'].','.$dada_status.'=>'.$data['order_status']);
                }
                $orderLog = true;
                //$remarks = '骑士到店';
                $detail = '骑士到店取货';
                break;
            case '3':// 配送中
                if (!in_array($dada_status, ['100', '2'])) {
                    throw new ResourceException('要修改的状态不正确 订单号： '.$data['order_id'].','.$dada_status.'=>'.$data['order_status']);
                }
                $orderLog = true;
                //$remarks = '骑士已取货';
                $detail = '骑士配送中';

                $update_data['dm_id'] = $data['dm_id'];
                $update_data['dm_name'] = $data['dm_name'];
                $update_data['dm_mobile'] = $data['dm_mobile'];
                $update_data['pickup_time'] = time();// 取货时间
                break;
            case '4':// 已完成
                if (!in_array($dada_status, ['3', '9'])) {
                    throw new ResourceException('要修改的状态不正确 订单号： '.$data['order_id'].','.$dada_status.'=>'.$data['order_status']);
                }

                // 主单改为已发货
                $deliveryParams = [
                    'company_id' => $companyId,
                    'delivery_type' => 'batch',
                    'order_id' => $data['order_id'],
                    'delivery_corp' => 'OTHER',
                    'delivery_code' => 'dada',
                    'type' => 'new',
                    'operator_type' => 'system',
                    'operator_id' => 0,
                ];
                $orderService->delivery($deliveryParams);

                // 主单改为已完成
                $confirmParams = [
                    'company_id' => $companyId,
                    'order_id' => $data['order_id'],
                    'user_id' => $orderDetail['orderInfo']['user_id'],
                ];
                $orderService->confirmReceipt($confirmParams, $operator);
                $update_data['delivered_time'] = time();// 送达时间

                //送达触发订单oms更新的事件
                event(new SaasErpUpdateEvent($orderDetail['orderInfo']));
                break;
            case '5':// 已取消
                if (!in_array($dada_status, ['1', '2', '100', '3', '9']) || $dada_cancel_from > 0) {
                    throw new ResourceException('要修改的状态不正确 订单号： '.$data['order_id'].','.$dada_status.'=>'.$data['order_status']);
                }
                $update_data['dada_cancel_from'] = $data['cancel_from'];
                // 商家主动取消时，主单改为已取消
                if ($data['cancel_from'] == '2' || $data['cancel_from'] == '3') {
                    $cancelParams = [
                        'company_id' => $companyId,
                        'order_id' => $data['order_id'],
                        'user_id' => $orderDetail['orderInfo']['user_id'],
                        'cancel_from' => 'system',
                        'cancel_reason' => 'other_reason',
                        'other_reason' => $data['cancel_reason'],
                    ];
                    $orderService->cancelOrder($cancelParams);
                }

                break;
            case '9':// 妥投异常物品返回中
                if (!in_array($dada_status, ['3'])) {
                    throw new ResourceException('要修改的状态不正确 订单号： '.$data['order_id'].','.$dada_status.'=>'.$data['order_status']);
                }
                $orderLog = true;
                $remarks = '同城配送 - 妥投异常';
                $detail = '订单号：' . $data['order_id'] . '，订单妥投异常物品返回中';
                break;
            case '10':// 妥投异常之物品返回完成
                if (!in_array($dada_status, ['9'])) {
                    throw new ResourceException('要修改的状态不正确 订单号： '.$data['order_id'].','.$dada_status.'=>'.$data['order_status']);
                }
                $cancelParams = [
                    'company_id' => $companyId,
                    'order_id' => $data['order_id'],
                    'user_id' => $orderDetail['orderInfo']['user_id'],
                    'cancel_from' => 'system',
                    'cancel_reason' => 'other_reason',
                    'other_reason' => '妥投异常之物品返回完成',
                ];
                $orderService->cancelOrder($cancelParams);
                break;
            default:
                app('log')->info('dadaCallback request error msg:状态无需处理 订单号： '.$data['order_id'].','.$dada_status.'=>'.$data['order_status']);
                return true;
                break;
        }
        $normalOrdersRelDadaRepository = app('registry')->getManager('default')->getRepository(NormalOrdersRelDada::class);
        $normalOrdersRelDadaRepository->updateOneBy($filter, $update_data);

        // 记录订单日志
        if ($orderLog) {
            $orderProcessLog = [
                'order_id' => $data['order_id'],
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
        $params = [
            'order_id' => $orderId,
            'cancel_reason_id' => '10000',
            'cancel_reason' => $cancelReason,
        ];
        $formalCancelApi = new FormalCancelApi(json_encode($params));
        $dada_client = new DadaRequest($companyId, $formalCancelApi);
        $resp = $dada_client->makeRequest();
        if ($resp->status == 'fail') {
            throw new ResourceException($resp->msg);
        }

        return true;
    }

    /**
     * 重发订单,请求达达
     * @param string $orderData 订单详情数据
     */
    public function reAddOrder($dadaData)
    {
        $companyId = $dadaData['company_id'];
        $orderId = $dadaData['order_id'];
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $orderId);
        if (!$order) {
            throw new ResourceException('此订单不存在！');
        }
        $orderService = $this->getOrderServiceByOrderInfo($order);
        $orderDetail = $orderService->getOrderInfo($companyId, $orderId);
        $orderInfo = $orderDetail['orderInfo'];
        unset($orderDetail);
        if ($orderInfo['receipt_type'] != 'dada') {
            return true;
        }
        $orderInfo['total_fee'] = bcsub($orderInfo['total_fee'], $orderInfo['freight_fee']);
        $orderInfo['total_fee'] = bcadd($orderInfo['total_fee'], $orderInfo['point_fee']);
        $orderInfo['freight_fee'] = 0;
        $orderStruct = $this->getOrderStruct($orderInfo);
        // 根据地址，去高德获取坐标
        // 113.325296,23.095369
        $address = $orderInfo['receiver_state'].$orderInfo['receiver_city'].$orderInfo['receiver_district'].$orderInfo['receiver_address'];
        $latlng = getamap_latlng_by_address($address);
        $_latlng = explode(',', $latlng);
        // $orderStruct['receiver_lng'] = '121.417427';// 收货人地址经度 高德坐标系
        // $orderStruct['receiver_lat'] = '31.175924';// 收货人地址纬度 高德坐标系
        $orderStruct['receiver_lng'] = $_latlng[0] ?? '';// 收货人地址经度 高德坐标系
        $orderStruct['receiver_lat'] = $_latlng[1] ?? '';// 收货人地址纬度 高德坐标系
        $reAddOrderApi = new ReAddOrderApi(json_encode($orderStruct));

        $dada_client = new DadaRequest($companyId, $reAddOrderApi);
        $resp = $dada_client->makeRequest();
        if ($resp->status == 'success') {
            return true;
        } else {
            throw new ResourceException($resp->msg);
        }
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
            $gotoJob = (new sendDeliveryKnightCancelNoticeJob($company_id, $data['order_id']))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        ## 已接单
        if ($data['order_status'] == 2) {
            $gotoJob = (new sendDeliveryKnightAcceptNoticeJob($company_id, $data['order_id']))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        ## 已到店
        if ($data['order_status'] == 100) {
            $gotoJob = (new sendDeliveryKnightArriveNoticeJob($company_id, $data['order_id']))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        ## 妥投异常
        if ($data['order_status'] == 9) {
            $gotoJob = (new sendFinishedFailNoticeJob($company_id, $data['order_id']))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        return true;
    }
}
