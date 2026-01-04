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

namespace ThirdPartyBundle\Services\CustomsCentre;

use Dingo\Api\Exception\ResourceException;
use EasyWeChat\Kernel\Support\XML; // easywechat@done
use OrdersBundle\Entities\CustomDeclareOrderResult;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\Trade;
use OrdersBundle\Traits\GetOrderServiceTrait;
use ThirdPartyBundle\Entities\CustomsData;

class CustomsService
{
    use GetOrderServiceTrait;

    public $repository;
    public $normalOrdersRepository;
    public $customDeclareOrderResultRepository;

    private static $signKey = 'U2FsdGVkX11BC2';

    public function __construct()
    {
        $this->repository = app('registry')->getManager('default')->getRepository(CustomsData::class);
        $this->normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $this->customDeclareOrderResultRepository = app('registry')->getManager('default')->getRepository(CustomDeclareOrderResult::class);
    }

    public function getOrderStruct($params)
    {
        // Powered by ShopEx EcShopX
        if ($params['sign'] != self::genSignKey($params['timestamp'])) {
            throw new ResourceException('签名错误');
        }

        $lists = $this->repository->lists(['status' => 0], '*', 1, 10, ['service_time' => 'asc']);
        if ($lists['list']) {
            $orderStruct = [];
            foreach ($lists['list'] as $list) {
                //获取交易单
                //获取交易单信息
                $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
                $trade = $tradeRepository->getInfoById($list['order_id']);
                $order_id = $trade['order_id'];

                $orderInfo = $this->normalOrdersRepository->getInfo(['order_id' => $order_id]);


                $orderService = $this->getOrderService('normal');

                // 获取订单信息
                $orderData = $orderService->getOrderInfo($orderInfo['company_id'], $order_id);

                if (!$orderData) {
                    continue;
                }

                $orderInfo = $orderData['orderInfo'];
                $tradeInfo = $orderData['tradeInfo'];

                $order_items = $this->___formatOrderItems($orderInfo);

                //获取报关详情
                $customDeclareOrderResult = $this->customDeclareOrderResultRepository->getInfo(['company_id' => $orderInfo['company_id'], 'order_id' => $order_id]);

                $verDept = ['UNIONPAY' => 1, 'NETSUNION' => 2, 'OTHERS' => 3];
                $orderStruct[] = [
                    'id' => $order_id,
                    'increment_id' => $order_id,
                    'sign_string' => [
                        'payExchangeInfoHead' => [
                            'guid' => $tradeInfo['tradeId'],
                            'initalRequest' => XML::build($tradeInfo['initalRequest']),
                            'initalResponse' => XML::build($tradeInfo['initalResponse']),
                            'ebpCode' => config('common.owner_id'), //电商平台十位海关编码
                            'payCode' => '',  //第三方支付企业十位海关编码:4403169D3W(微信),31222699S7(支付宝)
                            'payTransactionId' => $tradeInfo['transactionId'],
                            'totalAmount' => bcdiv($tradeInfo['totalFee'], 100, 2),
                            'currency' => '142',
                            'verDept' => $verDept[$customDeclareOrderResult['verify_department']],
                            'payType' => $orderInfo['pay_type'],
                            'tradingTime' => date('YmdHis', $tradeInfo['timeExpire']),
                            'node' => $orderInfo['remark'],
                        ],
                        'payExchangeInfoList' => [
                            'orderNo' => $tradeInfo['tradeId'],
                            'goodsInfo' => $order_items,
                            'recpAccount' => '', //电商平台的对公银行卡号(在支付企业登记的)
                            'recpCode' => '', //收款企业代码
                            'recpName' => '', //收款企业名称(电商平台企业全称)
                        ],
                        'sessionID' => $list['session_id'],
                        'serviceTime' => $list['service_time'],
                    ]
                ];
            }
            return $orderStruct;
        }
        return [];
    }

    private function ___formatOrderItems($orderInfo)
    {
        $order_items = [];
        foreach ($orderInfo['items'] as $key => $value) {
            $order_items[] = [
                'gname' => $value['item_name'],
                'itemLink' => config('common.h5_base_url') . '/pages/item/espier-detail?id=' . $value['item_id'], //商品展示链接地址
            ];
        }

        return $order_items;
    }

    public function updateOrderData($filter)
    {
        //获取交易单
        //获取交易单信息
        $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
        $trade = $tradeRepository->getInfoById($filter['order_id']);
        $filter['order_id'] = $trade['order_id'];

        $orderInfo = $this->normalOrdersRepository->getInfo(['order_id' => $filter['order_id']]);

        if (!$orderInfo) {
            throw new ResourceException('订单不存在');
        }

        $response = json_decode($filter['response'], 1);

        if ($response['code'] == '10000') {
            //上报成功
            $this->repository->updateOneBy(['order_id' => $filter['order_id']], ['status' => 1]);
        }

        return [];
    }

    /**
     * 生成签名
     *
     * @param $timestamp
     * @return string
     */
    private static function genSignKey($timestamp)
    {
        return strtoupper(md5($timestamp . self::$signKey));
    }

    public function __call($name, $arguments)
    {
        return $this->repository->$name(...$arguments);
    }
}
