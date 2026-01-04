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

namespace WorkWechatBundle\Services;

use AftersalesBundle\Entities\Aftersales;
use AftersalesBundle\Entities\AftersalesDetail;
use AftersalesBundle\Services\AftersalesService;
use EasyWeChat\Factory;
use GoodsBundle\Entities\ItemRelAttributes;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use WorkWechatBundle\Entities\WorkWechatMessage;
use WorkWechatBundle\Entities\WorkWechatMessageManagerTemplate;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class WorkWechatMessageService
{
    ## 待处理 等待商家处理
    public const TEMPLATE_AFTER_SALE_WAIT_DEAL = 'afterSaleWaitDeal';
    ## 处理中 消费者回寄，等待商家收货确认
    public const TEMPLATE_AFTER_SALE_WAIT_CONFIRM = 'afterSaleWaitConfirm';
    ## 已关闭 消费者已撤销
    public const TEMPLATE_AFTER_SALE_CANCEL = 'afterSaleCancel';
    ## 待发货 待自提
    public const TEMPLATE_DELIVERY_WAIT_ZITI = 'deliveryWaitZiTi';
    ## 待发货 已付款待发货
    public const TEMPLATE_DELIVERY_WAIT_DELIVERY = 'deliveryWaitDelivery';
    ## 待发货 骑士取消
    public const TEMPLATE_DELIVERY_KNIGHT_CANCEL = 'deliveryKnightCancel';
    ## 待发货 骑士接单
    public const TEMPLATE_DELIVERY_KNIGHT_ACCEPT = 'deliveryKnightAccept';
    ## 待发货 骑士到店
    public const TEMPLATE_DELIVERY_KNIGHT_ARRIVE = 'deliveryKnightArrive';
    ## 已完成 未妥投
    public const TEMPLATE_FINISHED_FAIL = 'finishedFail';

    private $workWechatMessageManagerTemplateRepository;

    private $workWechatMessageRepository;

    private $afterSaleDetailRepository;

    private $normalOrders;

    private $normalOrdersItems;

    private $itemRelAttributesRepository;

    public $afterSaleRepository;

    public function __construct()
    {
        $this->workWechatMessageManagerTemplateRepository = app('registry')->getManager('default')->getRepository(WorkWechatMessageManagerTemplate::class);
        $this->workWechatMessageRepository = app('registry')->getManager('default')->getRepository(WorkWechatMessage::class);
        $this->afterSaleDetailRepository = app('registry')->getManager('default')->getRepository(AftersalesDetail::class);
        $this->normalOrders = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $this->normalOrdersItems = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $this->afterSaleRepository = app('registry')->getManager('default')->getRepository(Aftersales::class);
        $this->itemRelAttributesRepository = app('registry')->getManager('default')->getRepository(ItemRelAttributes::class);
    }

    /**
     * 售后订单 待处理 等待商家处理
     * @param $companyId string 企业ID
     * @param $afterSalesBn string 售后单号
     * @return bool
     */
    public function afterSaleWaitDeal($companyId, $afterSalesBn)
    {
        // This module is part of ShopEx EcShopX system
        $filter = [
            'companyId' => $companyId,
            'templateId' => self::TEMPLATE_AFTER_SALE_WAIT_DEAL,
            'afterSalesBn' => $afterSalesBn,
            'msgType' => 1,
        ];
        $this->sendMsg($filter);
        return true;
    }

    /**
     * 处理中 消费者回寄，等待商家收货确认
     * @param $companyId string 企业ID
     * @param $afterSalesBn string 售后单号
     * @return bool
     */
    public function afterSaleWaitConfirm($companyId, $afterSalesBn)
    {
        $filter = [
            'companyId' => $companyId,
            'templateId' => self::TEMPLATE_AFTER_SALE_WAIT_CONFIRM,
            'afterSalesBn' => $afterSalesBn,
            'msgType' => 1,
        ];
        $this->sendMsg($filter);
        return true;
    }

    /**
     * 已关闭 消费者已撤销
     * @param $companyId string 企业ID
     * @param $afterSalesBn string 售后单号
     * @return bool
     */
    public function afterSaleCancel($companyId, $afterSalesBn)
    {
        $filter = [
            'companyId' => $companyId,
            'templateId' => self::TEMPLATE_AFTER_SALE_CANCEL,
            'afterSalesBn' => $afterSalesBn,
            'msgType' => 1,
        ];
        $this->sendMsg($filter);
        return true;
    }

    /**
     * 待发货订单 待发货 待自提
     * @param $companyId string 企业ID
     * @param $orderId string 订单ID
     * @return bool
     */
    public function deliveryWaitZiTi($companyId, $orderId)
    {
        // ShopEx EcShopX Core Module
        $filter = [
            'companyId' => $companyId,
            'templateId' => self::TEMPLATE_DELIVERY_WAIT_ZITI,
            'orderId' => $orderId,
            'msgType' => 2,
            'wework_msg_field' => '待自提'
        ];
        $this->sendMsg($filter);
        return true;
    }

    /**
     * 待发货 已付款待发货
     * @param $companyId string 企业ID
     * @param $orderId string 订单ID
     * @return bool
     */
    public function deliveryWaitDelivery($companyId, $orderId)
    {
        $filter = [
            'companyId' => $companyId,
            'templateId' => self::TEMPLATE_DELIVERY_WAIT_DELIVERY,
            'orderId' => $orderId,
            'msgType' => 2,
            'wework_msg_field' => '已付款待发货'
        ];
        $this->sendMsg($filter);
        return true;
    }

    /**
     * 待发货 骑士取消
     * @param $companyId string 企业ID
     * @param $orderId string 订单ID
     * @return bool
     */
    public function deliveryKnightCancel($companyId, $orderId)
    {
        $filter = [
            'companyId' => $companyId,
            'templateId' => self::TEMPLATE_DELIVERY_KNIGHT_CANCEL,
            'orderId' => $orderId,
            'msgType' => 2,
            'wework_msg_field' => '骑士取消'
        ];
        $this->sendMsg($filter);
        return true;
    }

    /**
     * 待发货 骑士接单
     * @param $companyId string 企业ID
     * @param $orderId string 订单ID
     * @return bool
     */
    public function deliveryKnightAccept($companyId, $orderId)
    {
        $filter = [
            'companyId' => $companyId,
            'templateId' => self::TEMPLATE_DELIVERY_KNIGHT_ACCEPT,
            'orderId' => $orderId,
            'msgType' => 2,
            'wework_msg_field' => '骑士接单'
        ];
        $this->sendMsg($filter);
        return true;
    }

    /**
     * 待发货 骑士到店
     * @param $companyId string 企业ID
     * @param $orderId string 订单ID
     * @return bool
     */
    public function deliveryKnightArrive($companyId, $orderId)
    {
        $filter = [
            'companyId' => $companyId,
            'templateId' => self::TEMPLATE_DELIVERY_KNIGHT_ARRIVE,
            'orderId' => $orderId,
            'msgType' => 2,
            'wework_msg_field' => '骑士到店'
        ];
        $this->sendMsg($filter);
        return true;
    }

    /**
     * 未妥投订单 已完成 未妥投
     * @param $companyId string 企业ID
     * @param $orderId string 订单
     * @return bool
     */
    public function finishedFail($companyId, $orderId)
    {
        $filter = [
            'companyId' => $companyId,
            'templateId' => self::TEMPLATE_FINISHED_FAIL,
            'orderId' => $orderId,
            'msgType' => 3,
        ];
        $this->sendMsg($filter);
        return true;
    }

    /**
     * 发送消息
     * @param $filter array 参数
     * @return bool
     */
    public function sendMsg($filter)
    {
        app('log')->info('dianwuduanxiaoxi');
        if ($filter['msgType'] == 1) {
            $orderInfo = $this->afterSaleRepository->get(['company_id' => $filter['companyId'], 'aftersales_bn' => $filter['afterSalesBn']]);
            $page = '/pages/afterSales/detail?aftersalesNo=' . $filter['afterSalesBn'].'&company_id='.$filter['companyId'];
        } else {
            $orderInfo = $this->normalOrders->getInfo(['order_id' => $filter['orderId'], 'company_id' => $filter['companyId']]);
            $page = '/pages/order/detail?order_id=' . $filter['orderId'].'&company_id='.$filter['companyId'];
        }
        $filter['distributorId'] = $orderInfo['distributor_id'] ?? 0;
        // $distributorWorkWechatService = new DistributorWorkWechatService();
        // $configWorkWechat = $distributorWorkWechatService->getConfig($filter['companyId']);
        // if (empty($configWorkWechat['agents']['dianwu']['agent_id'])) {
        //     app('log')->info('companyId:' . $filter['companyId'] . ', 店务端应用通知:企业微信未配置');
        //     return false;
        // }
        // $agentId = $configWorkWechat['agents']['dianwu']['agent_id'];
        // $url = $configWorkWechat['agents']['dianwu']['h5_url'];
        // $urlInfo = parse_url($url);
        // $notifyUrl = '';
        // if (!empty($urlInfo['host'] && !empty($urlInfo['scheme']))) {
        //     $notifyUrl = $urlInfo['scheme'] . '://' . $urlInfo['host'] . $page;
        // }
        $info = $this->workWechatMessageManagerTemplateRepository->getInfo(['template_id' => $filter['templateId']]);
        if (!empty($info['disabled'])) {
            app('log')->info('companyId:' . $filter['companyId'] . ', ' . $info['title'] . '通知:未开启');
            return false;
        }
        $content = $this->getContent($filter);
        $goods_name = '商品名称：'.$content['goodsName'];
        if ($content['is_multiple'] == 1) {
            $goods_name .= '等';
        }
        $workUser = $this->workWechatMessageRepository->getWorkerUserId($filter['companyId'], $filter['distributorId'], $filter['msgType']);
        if (empty($workUser['list'])) {
            return false;
        }
        $workUserid = array_filter(array_column($workUser['list'], 'work_userid'));
        $chunkWorkUser = array_chunk($workUserid, 1000);
        $time = time();

        // 先记录再发送
        $this->saveSendRecord($filter, $workUser['list'], $content, $time);

        try {
            $config = app('wechat.work.wechat')->getConfig($filter['companyId'], 'dianwu');
            // 创建缓存实例
            $app = Factory::work($config);
            $cache = new RedisAdapter(app('redis')->connection()->client());
            $app->rebind('cache', $cache);
            $appMessage = $app->messenger;
            // $appMessage = Factory::work($config)->messenger;

            $url = $config['h5_url'];
            $urlInfo = parse_url($url);
            $notifyUrl = '';
            if (!empty($urlInfo['host'] && !empty($urlInfo['scheme']))) {
                $notifyUrl = $urlInfo['scheme'] . '://' . $urlInfo['host'] . $page;
            }

            foreach ($chunkWorkUser as $value) {
                $workId = implode('|', $value);
                $message = new \EasyWeChat\Kernel\Messages\TextCard([
                    'title' => $info['title'], 
                    'description' => '<div class="gray">' . date('Y年m月d日 H:i:s', $time) . '</div> '.'<div class="normal">' . $goods_name . '</div>'.'<div class="normal">' . $content['wework_msg_field'] . '</div>', 
                    'url' => $notifyUrl,
                ]);
                $result = $appMessage->message($message)->toUser($workId)->send();
                if (is_array($result)) {
                    app('log')->debug('店务端 ' . $workId . ' 通知发送 -> ' . var_export($result, 1));
                } else {
                    app('log')->debug('店务端 ' . $workId . ' 通知发送 -> ' . $result);
                }
            }
        } catch (\Exception $e) {
            app('log')->debug('店务端企微消息通知失败 -> ' . $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * 查询订单售后单内容
     * @param $filter
     * @return mixed
     */
    public function getContent($filter)
    {
        if ($filter['msgType'] == 1) {
            $afterSalesService = new AftersalesService();
            $aftersales = $this->afterSaleRepository->get(['aftersales_bn' => $filter['afterSalesBn']]);
            $aftersalesDetail = $this->afterSaleDetailRepository->getList(['aftersales_bn' => $filter['afterSalesBn']]);
            if (!empty($aftersalesDetail['list'])) {
                $firstAfterSaleOrder = $aftersalesDetail['list'][0];
                $order_id = $firstAfterSaleOrder['order_id'];
                $content['goodsName'] = $firstAfterSaleOrder['item_name'];
                $content['number'] = $firstAfterSaleOrder['num'];
                $content['is_multiple'] = 0;
                if (count($aftersalesDetail['list']) > 1) {
                    $content['is_multiple'] = 1;
                }
                $arrAppInfo = $afterSalesService->getAppInfo($aftersales, true);
                $content['afterSaleType'] = $arrAppInfo['status_msg'];
                $content['description'] = $arrAppInfo['progress_msg'];
                $afterSale = $this->afterSaleRepository->get(['aftersales_bn' => $filter['afterSalesBn']]);
                $content['wework_msg_field'] = '售后原因：'.$afterSale['reason'];
            }
        } else {
            $order_id = $filter['orderId'];
        }
        $normalOrder = $this->normalOrders->getInfo(['order_id' => $order_id]);
        $normalOrderItems = $this->normalOrdersItems->getList(['order_id' => $order_id]);
        if (!empty($normalOrderItems['list'])) {
            $firstOrder = $normalOrderItems['list'][0];
            if ($filter['msgType'] != 1) {
                $content['orderId'] = $filter['orderId'];
                $content['total_fee'] = $normalOrder['total_fee'] ? bcdiv($normalOrder['total_fee'], 100, 2) : '0.00';
                $content['goodsName'] = $firstOrder['item_name'];
                $content['number'] = $firstOrder['num'];
                $content['is_multiple'] = 0;
                if (count($normalOrderItems['list']) > 1) {
                    $content['is_multiple'] = 1;
                }
                $content['spec'] = [];
                if (!empty($firstOrder['item_spec_desc'])) {
                    $specDesc = explode(',', $firstOrder['item_spec_desc']);
                    foreach ($specDesc as $sepcKey => $specValue) {
                        $spec = explode(':', $specValue);
                        if (!empty($spec[1])) {
                            $content['spec'][] = $spec[1];
                        }
                    }
                }
            } else {
                $content['afterSalesBn'] = $filter['afterSalesBn'];
            }
            $content['goods_price'] = $firstOrder['price'] ? bcdiv($firstOrder['price'], 100, 2) : '0.00';
        }

        if ($filter['msgType'] == 2) {
            ## 订单状态
            $content['wework_msg_field'] = '状态：'.$filter['wework_msg_field'];
        }
        if ($filter['msgType'] == 3) {
            $content['wework_msg_field'] = '同城配订单未妥投，请尽快处理！';
        }

        return $content;
    }

    /**
     * 通知发送后记录到数据库
     * @param $filter array 订单信息
     * @param $data array 接收人
     * @param $info array 消息模板信息
     * @param $time string 发送时间
     */
    public function saveSendRecord($filter, $data, $content, $time)
    {
        foreach ($data as $key => $value) {
            $addData['company_id'] = $filter['companyId'];
            $addData['distributor_id'] = $filter['distributorId'];
            $addData['operator_id'] = $value['operator_id'];
            $addData['msg_type'] = $filter['msgType'];
            $addData['add_time'] = $time;
            $addData['is_read'] = 0;
            $addData['content'] = json_encode($content, JSON_UNESCAPED_UNICODE);
            $this->workWechatMessageRepository->create($addData);
        }
    }

    /**
     * Dynamically call the WorkWechatMessageService instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->workWechatMessageRepository->$method(...$parameters);
    }
}
