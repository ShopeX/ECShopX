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

use AftersalesBundle\Services\AftersalesRefundService;
use CompanysBundle\Services\CompanysService;
use CompanysBundle\Services\Shops\WxShopsService;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Traits\GetDefaultCur;
use CrossBorderBundle\Entities\CrossBorderSet;
use CrossBorderBundle\Entities\OriginCountry;
use CrossBorderBundle\Services\Taxstrategy as Strategy;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Entities\Distributor;
use DistributionBundle\Services\DistributorItemsService;
use DistributionBundle\Services\DistributorService;
use DistributionBundle\Services\DistributorUserService;
use DistributionBundle\Services\selfDeliveryService;
use GoodsBundle\Entities\ItemsCategory;
use GoodsBundle\Services\ItemsService;
use HfPayBundle\Services\HfpayLedgerConfigService;
use KaquanBundle\Services\UserDiscountService;
use MembersBundle\Services\MemberService;
use MembersBundle\Services\UserService;
use MembersBundle\Services\MembersWhitelistService;
use MembersBundle\Entities\MembersAssociations;
use OrdersBundle\Constants\OrderReceiptTypeConstant;
use OrdersBundle\Entities\CancelOrders;
use OrdersBundle\Entities\OrderAssociations;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\Trade;
use OrdersBundle\Events\NormalOrderAddEvent;
use OrdersBundle\Interfaces\OrderInterface;
use OrdersBundle\Repositories\TradeRepository;
use OrdersBundle\Traits\CountPreferentialFee;
use OrdersBundle\Traits\GetOrderIdTrait;
use OrdersBundle\Traits\GetUserIdByMobileTrait;
use OrdersBundle\Traits\OrderSettingTrait;
use PaymentBundle\Services\Payments\OfflinePayService;
use PointBundle\Exception\PointResourceException;
use PointBundle\Services\PointMemberRuleService;
use PopularizeBundle\Services\PromoterService;
use PromotionsBundle\Services\SmsManagerService;
use PromotionsBundle\Services\SpecificCrowdDiscountService;
use PointBundle\Services\PointMemberService;

use DepositBundle\Services\DepositTrade;
use CrossBorderBundle\Services\Identity;
use CompanysBundle\Services\SettingService;
use PromotionsBundle\Services\PointupvaluationActivityService as PointupvaluationService;

use OrdersBundle\Events\OrderProcessLogEvent;
// 商品库存
use PromotionsBundle\Services\TurntableService;
use SupplierBundle\Services\ItemsCommissionService;
use ThirdPartyBundle\Services\MarketingCenter\Request as MarketingCenterRequest;
use SalespersonBundle\Services\SalespersonService;
use OrdersBundle\Services\LocalDeliveryService;
use MerchantBundle\Services\MerchantService;
use ThirdPartyBundle\Events\TradeUpdateEvent as SaasErpUpdateEvent;
use CompanysBundle\Ego\CompanysActivationEgo;
use PromotionsBundle\Traits\CheckPromotionsValid;
use DistributionBundle\Services\PickupLocationService;
use WechatBundle\Events\WxOrderShippingEvent;
use OrdersBundle\Jobs\SendPayOrdersRemindJob;

class OrderService
{
    use GetOrderIdTrait;
    use GetUserIdByMobileTrait;
    use CountPreferentialFee;
    use GetDefaultCur;
    use OrderSettingTrait;
    use CheckPromotionsValid;

    /**
     * @var orderInterface
     */
    public $orderInterface;

    /**
     * 创建订单数据
     */
    public $orderData = [];

    // 当前的shopId
    public $shopInfo = [];

    /**
     * 订单单个商品数据
     */
    public $orderItemData = [];

    public $orderItemList = [];

    public $orderItemListNostores = [];

    public $orderAssociationsRepository;
    public $distributorRepository;

    /**
     * KaquanService
     */
    public function __construct(OrderInterface $orderInterface)
    {
        $this->orderInterface = $orderInterface;
    }

    /**
     * 创建订单
     *
     * @param array $params 创建订单参数
     */
    public function create($params)
    {
        // 检查创建订单的必填参数
        $this->checkCreateOrderNeedParams($params);

        if (property_exists($this->orderInterface, 'isSupportCart') && $this->orderInterface->isSupportCart) {
            $params = $this->orderInterface->checkoutCartItems($params);
        }

        $this->_check($params);

        $orderData = $this->_formatOrderData($params);
        if(isset($params['invoice_content'])){
            app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':params:invoice_content:'.json_encode($params['invoice_content']));
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        // 判断是否跨境
        if (isset($params['iscrossborder']) and $params['iscrossborder'] == 1) {
//            $orderData = $this->crossBorderHandle($params, $orderData);
            $preg_card = '/^[1-9]\d{5}(19|20)\d{2}[01]\d[0123]\d\d{3}[xX\d]$/';
            if (!preg_match($preg_card, $params['identity_id'])) {
                throw new ResourceException(trans('OrdersBundle/Order.id_card_format_error'));
            }
            $orderData['identity_id'] = $params['identity_id'];         // 身份证号
            $orderData['identity_name'] = $params['identity_name'];     // 身份证名称
            $orderData['type'] = 1;     // 订单类型,跨境订单
        }

        // 积分抵扣数据
        if (property_exists($this->orderInterface, 'isSupportPointDiscount') &&
            $this->orderInterface->isSupportPointDiscount) {
            $orderData = $this->_formatOrderPointDeduct($params, $orderData);
        }

        // 订单改价
        if (property_exists($this->orderInterface, 'isSupportMarkDown') &&
            $this->orderInterface->isSupportMarkDown) {
            if (isset($params['markdown'])) {
                $orderData = $this->orderInterface->markDown($orderData, $params['markdown']);
            }
        }

        //订单可以获取到的积分 放到积分抵扣之后去计算, 来排除抵扣部分
        // 数云模式,商城不处理积分，数云处理 !config('common.oem-shuyun')
        if (property_exists($this->orderInterface, 'isSupportGetPoint') && $this->orderInterface->isSupportGetPoint && $orderData['user_id'] > 0 && !config('common.oem-shuyun')) {
            $pointMemberService = new PointMemberService();
            $orderData = $pointMemberService->memberGetPoints($params['company_id'], $orderData);
        }

        try {
            $this->orderAssociationsRepository = app('registry')->getManager('default')->getRepository(OrderAssociations::class);

            $ordersResult = $this->orderInterface->create($orderData, $params);
            app('log')->info(__FUNCTION__.':'.__LINE__.':ordersResult:'.json_encode($ordersResult));
            // 判断是否跨境-更新身份证信息
            if (isset($params['iscrossborder']) and $params['iscrossborder'] == 1) {
                $Identity_data['user_id'] = $orderData['user_id'];
                $Identity_data['company_id'] = $orderData['company_id'];
                $Identity_data['identity_id'] = $orderData['identity_id'];
                $Identity_data['identity_name'] = $orderData['identity_name'];
                $Identity = new Identity();
                $Identity->saveUpdate($Identity_data);
            }

            if ($this->orderInterface->orderType == 'normal') {
                // 达达同城配，查询运费后发单接口，成功后存储数据
                if ($params['receipt_type'] == 'dada') {
                    $localDeliveryService = new LocalDeliveryService();
                    // 存储达达关联数据
                    $dada_data = [
                        'order_id' => $orderData['order_id'],
                        'company_id' => $orderData['company_id'],
                        'dada_status' => 0,
                        'dada_delivery_no' => $orderData['dada_delivery_no'],
                    ];
                    $localDeliveryService->getOrderService()->saveOrderRelDada($dada_data);
                }

                // 自提订单，保存自提信息
                if ($params['receipt_type'] == 'ziti' && !in_array($this->orderInterface->orderClass, ['shopadmin', 'community'])) {
                    $zitiData = [
                        'order_id' => $orderData['order_id'],
                        'company_id' => $orderData['company_id'],
                        'pickup_location' => $params['pickup_location'],
                        'pickup_date' => $params['pickup_date'],
                        'pickup_time' => $params['pickup_time'],
                    ];
                    $this->orderInterface->saveOrderRelZiti($zitiData);
                }
            }

            if (method_exists($this->orderInterface, 'createExtend')) {
                $ordersExtendResult = $this->orderInterface->createExtend($orderData, $params);
                if (isset($ordersExtendResult['marge']) && $ordersExtendResult['marge'] && isset($ordersExtendResult['ordersResult'])) {
                    $ordersResult = array_merge($ordersResult, $ordersExtendResult['ordersResult']);
                }
            }

            if (isset($ordersResult['discount_info']) && $ordersResult['discount_info']) {
                $userDiscountService = new UserDiscountService();
                foreach ($ordersResult['discount_info'] as $row) {
                    if ($row && isset($row['coupon_code'])) {
                        $params['consume_outer_str'] = '商城下单使用优惠券';
                        $params['trans_id'] = $ordersResult['order_id'];
                        $params['fee'] = $ordersResult['total_fee'];
                        $userDiscountService->userConsumeCard($ordersResult['company_id'], $row['coupon_code'], $params);
                    }
                    //记录定向促销会员日志和最高优惠金额
                    if (($row['type'] ?? '') == 'member_tag_targeted_promotion') {
                        $specificCrowdDiscountService = new SpecificCrowdDiscountService();
                        $specificCrowdDiscountService->setUserTotalDiscount($orderData['company_id'], $orderData['user_id'], $orderData, 'plus');
                    }
                }
            }
            // 记录升值积分
            if (method_exists($this->orderInterface, 'addOrderUppoints')) {
                $this->orderInterface->addOrderUppoints($orderData);
            }
            $this->orderInterface->minusItemStore($orderData);
            //扣减积分
            $pointMemberService = new PointMemberService();
            if ($orderData['point_use'] && $orderData['pay_type'] != 'point') {
                app('log')->debug('订单使用了积分 order_id:'.$orderData['order_id']);
                $otherParams = ['point_type' => 'points_off_cash'];
                $pointMemberService = new PointMemberService();
                $pointMemberService->addPoint($orderData['user_id'], $orderData['company_id'], $orderData['point_use'], 6, false, '购物扣减积分', $orderData['order_id'], $otherParams);
            }

            if (property_exists($this->orderInterface, 'isSupportCart') && $this->orderInterface->isSupportCart) {
                if (method_exists($this->orderInterface, 'emptyCart')) {
                    $this->orderInterface->emptyCart($params);
                } else {
                    $this->emptyCart($params);
                }
            }

            // $this->sendPayOrdersRemind($orderData);
            $job = (new SendPayOrdersRemindJob($orderData))->delay(300);
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);

            if(isset($params['invoice_content'])){
                //createInvoiceOrderForOrder
                $orderInvoiceService = new OrderInvoiceService();
                $orderInvoiceService->createInvoiceOrderForOrder($params['invoice_content'], $orderData);
            }

            $remarks = '订单创建';
            $msg = '订单创建';
            if (isset($orderData['order_class']) && $orderData['order_class'] == 'excard') {
                $remarks = '订单核销';
                $msg = '订单核销成功';
            }
            $orderProcessLog = [
                'order_id' => $ordersResult['order_id'],
                'company_id' => $orderData['company_id'],
                'operator_type' => 'user',
                'is_show' => true,
                'operator_id' => $orderData['user_id'],
                'remarks' => $remarks,
                'detail' => '订单号：' . $ordersResult['order_id'] . '，'.$msg,
                'params' => $params,
            ];
            event(new OrderProcessLogEvent($orderProcessLog));
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        $ordersResult['discount_fee'] = isset($orderData['discount_fee']) ? $orderData['discount_fee'] : 0;
        $ordersResult['discount_info'] = isset($orderData['discount_info']) ? $orderData['discount_info'] : null;
        $ordersResult['items'] = $orderData['items'] ?? [];

        // 创建订单后的后置操作
        if (method_exists($this->orderInterface, "afterCreateOrder")) {
            $this->orderInterface->afterCreateOrder($orderData);
        }

        //触发事件
        $eventData = [
            'pay_type' => $ordersResult['pay_type'],
            'order_id' => $ordersResult['order_id'],
            'company_id' => $ordersResult['company_id']
        ];
        event(new NormalOrderAddEvent($eventData));

        return $ordersResult;
    }

    /**
     * 发送小程序 订单待支付提醒
     */
    public function sendPayOrdersRemind($orderData)
    {
        $openid = 0;
        if ($orderData['wxa_appid']) {
            $openid = app('wxaTemplateMsg')->getOpenIdBy($orderData['user_id'], $orderData['wxa_appid']);
        }

        if ($openid) {
            //发送小程序模版
            $rate = isset($orderData['fee_symbol']) ? round(floatval($orderData['fee_rate']), 4) : 1;
            $payMoney = round($orderData['total_fee'] * $rate);
            $payMoney = bcdiv($payMoney, 100, 2);
            if (isset($orderData['fee_symbol'])) {
                $payMoney = $orderData['fee_symbol'].$payMoney;
            }
            $limitSec = round(($orderData['auto_cancel_time'] - time()) / 60);
            $remark = $limitSec >= 60 ? round($limitSec / 60)."小时" : $limitSec."分钟";
            $wxaTemplateMsgData = [
                'order_id' => $orderData['order_id'],
                'pay_money' => $payMoney,
                'item_name' => $orderData['title'],
                'created' => date('Y-m-d H:i:s'),
                'remarks' => '您的未支付订单,将在约'.$remark.'后失效',
            ];
            $sendData['scenes_name'] = 'payOrdersRemind';
            $sendData['company_id'] = $orderData['company_id'];
            $sendData['appid'] = $orderData['wxa_appid'];
            $sendData['openid'] = $openid;
            $sendData['data'] = $wxaTemplateMsgData;
            app('wxaTemplateMsg')->send($sendData);
        }
    }

    /**
     * 检查创建订单必填参数
     *
     * @param array params 创建订单参数
     * @param boolean  isCreate 是否为创建订单
     */
    public function checkCreateOrderNeedParams(&$params, $isCreate = true)
    {
        // 创建订单类型自身服务是否检查必填参数
        // 如果服务本身自己检查必填参数
        // 则有由自身校验
        if (method_exists($this->orderInterface, 'checkCreateOrderNeedParams')) {
            $this->orderInterface->checkCreateOrderNeedParams($params, $isCreate);
        } else {
            $rules = [
                'items.*.item_id' => ['required', '缺少商品参数'],
                'items.*.num' => ['required', '商品数量最少为1'],
                'company_id' => ['required', '企业id必填'],
                'user_id' => ['required', '用户id必填'],
            ];
            $errorMessage = validator_params($params, $rules);
            if ($errorMessage) {
                throw new ResourceException($errorMessage);
            }
        }

        if (method_exists($this->orderInterface, 'checkCreateOrderParams')) {
            $this->orderInterface->checkCreateOrderParams($params, $isCreate);
        }

        // 是否需要检查收货人信息
        // 创建订单需要验证收货人信息
        if ($isCreate && $this->orderInterface->orderClass != 'excard') {
            if (isset($params['receipt_type']) && in_array($params['receipt_type'], ['logistics', 'dada', 'merchant'])) {
                $rules = [
                    'receiver_name' => ['required|zhstring', '请填写正确的收货人姓名'],
                    'receiver_mobile' => ['required', '请填写联系方式'],
                    'receiver_zip' => ['required|postcode', '请填写正确的邮编'],
                    'receiver_state' => ['required|zhstring', '请填写正确的省份'],
                    'receiver_city' => ['required|zhstring', '请填写正确的城市'],
                    'receiver_district' => ['required|zhstring', '请填写正确的地区'],
                    'receiver_address' => ['required', '请填写正确的详细地址'],
                ];
                if (!isset($params['receiver_zip']) || !preg_match("/^\d{6}$/", $params['receiver_zip'])) {
                    $params['receiver_zip'] = '000000';
                }

                $errorMessage = validator_params($params, $rules);
                if ($errorMessage) {
                    throw new ResourceException($errorMessage);
                }
            } elseif (isset($params['receipt_type']) && $params['receipt_type'] == 'ziti') {
                if (!in_array($this->orderInterface->orderClass, ['shopadmin', 'community'])) {
                    $rules = [
                        'receiver_name' => ['required|zhstring', '请填写正确的提货人姓名'],
                        'receiver_mobile' => ['required|mobile', '请填写提货人手机号'],
                        'pickup_location' => ['required', '请选择提货地址'],
                        'pickup_date' => ['required', '自提日期必填'],
                        'pickup_time' => ['required', '自提时间必填']
                    ];

                    $errorMessage = validator_params($params, $rules);
                    if ($errorMessage) {
                        throw new ResourceException($errorMessage);
                    }
                }
            } else {
                throw new ResourceException(trans('OrdersBundle/Order.select_correct_delivery_method'));
            }

        }

        return true;
    }

    protected function _check($params)
    {
        // 各个类型订单自己检查
        if (method_exists($this->orderInterface, 'check')) {
            $this->orderInterface->check($params);
        }
        // 检查购买商品是否有效
        $this->checkItemValid($params);

        // 校验当前购买门店是否有效 目前只能一次结算同门店商品
        if (property_exists($this->orderInterface, 'isCheckShopValid') && $this->orderInterface->isCheckShopValid) {
            if (isset($params['shop_id']) && $params['shop_id']) {
                $this->checkShopValid($params['shop_id']);
            }
        }

        // 校验店铺，并且校验当前购买的商品是否关联当前店铺
        if (property_exists($this->orderInterface, 'isCheckDistributorValid') && $this->orderInterface->isCheckDistributorValid) {
            if (isset($params['distributor_id'])) {
                $this->checkDistributorValid($params);
            }
        }

        // 检查是否开启白名单，如果开启，是否在白名单中，不在则不能下单
        if (property_exists($this->orderInterface, 'isCheckWhitelistValid') && $this->orderInterface->isCheckWhitelistValid) {
            if (isset($params['mobile']) && $params['mobile']) {
                $result = (new MembersWhitelistService())->checkWhitelistValid($params['company_id'], $params['mobile'], $tips);
                if (!$result) {
                    throw new ResourceException($tips);
                }
            }
        }

        return true;
    }

    /**
     * 检查达达同城配，是否可用
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function checkDadaValid($distributorInfo, $params)
    {
        // 检查店铺是否开启达达
        if (!$distributorInfo['is_dada'] && $params['receipt_type'] == 'dada') {
            throw new ResourceException(trans('OrdersBundle/Order.store_not_support_local_delivery'), null, null, [], 201);
        }

        // 检查配送地址和店铺是否为同一个城市
        if (isset($params['receiver_city']) && mb_trim($distributorInfo['city'], '市') != mb_trim($params['receiver_city'], '市')) {
            $msg = sprintf('该门店仅支持%s地区', $distributorInfo['city']);
            throw new ResourceException($msg, null, null, [], 201);
        }
        return true;
    }

    /**
     * 校验购买的商品是否有效
     */
    public function checkItemValid($params)
    {
        $itemIds = $this->formatOrderItemIds($params);
        // 如果不需要检查商品则返回空
        // 商品ID是否存储，在检查参数必填的时候已经过滤，所以此处不需要考虑没有商品id是否合法
        if (!$itemIds) {
            return true;
        }

        if (count($itemIds) > 100) {
            // 目前getItemsList一次获取最多100条数据
            // 如果有需求一次购买超过100中商品则再优化
            throw new ResourceException(trans('OrdersBundle/Order.cannot_buy_more_than_100_items'));
        }

        // 获取商品数据
        $filter['item_id|in'] = $itemIds;
        $filter['company_id'] = $params['company_id'];
        if (property_exists($this->orderInterface, 'getSkuItems') && $this->orderInterface->getSkuItems) {
            $itemList = $this->getSkuItems($filter, 1, 100);
        } else {
            $itemService = new ItemsService();
            $itemList = $itemService->getSkuItemsList($filter, 1, 100);
        }

        // 未查询到购买商品
        if ($itemList['total_count'] == 0) {
            throw new ResourceException(trans('OrdersBundle/Order.invalid_items_please_recalculate'));
        }

        // 查询出来的商品和购买提交的商品数量不一致
        if ($itemList['total_count'] != count(array_unique($itemIds))) {
            throw new ResourceException(trans('OrdersBundle/Order.some_items_invalid_please_recalculate'));
        }

        $params['order_source'] = isset($params['order_source']) ? $params['order_source'] : 'member';
        $allPoint = 0;
        // 校验商品是否可销售
        $company = (new CompanysActivationEgo())->check($params['company_id']);
        if ($company['product_model'] == 'standard' && isset($params['distributor_id']) && $params['distributor_id'] > 0) {
            $distributorItemsService = new DistributorItemsService();
            $distributorItemList = $distributorItemsService->lists(['item_id' => $itemIds, 'distributor_id' => $params['distributor_id']], [], -1, 1);
            $distributorItemList = array_column($distributorItemList['list'], null, 'item_id');
        }

        //汇付合单支付限制
        $supplierIds = array_unique(array_column($itemList['list'], 'supplier_id'));
        if (count($supplierIds) > 10) {
            throw new ResourceException(trans('OrdersBundle/Order.too_many_items'));
        }

        foreach ($itemList['list'] as $itemInfo) {
            if ($company['product_model'] == 'standard' && isset($params['distributor_id']) && $params['distributor_id'] > 0) {
                // 店铺没有同步总部商品
                if (!isset($distributorItemList[$itemInfo['item_id']])) {
                    $itemInfo['approve_status'] = 'instock';
                } else {
                    // 店铺下架
                    if (!$distributorItemList[$itemInfo['item_id']]['is_can_sale']) {
                        $itemInfo['approve_status'] = 'instock';
                    } else {
                        // 店铺上架且非总部发货，根据店铺的上架状态
                        if (!$distributorItemList[$itemInfo['item_id']]['is_total_store']) {
                            $itemInfo['approve_status'] = 'onsale';
                        }
                    }
                }
            }

            switch ($params['order_source']) {
                case 'shop': // 商品购买为代客下单来源
                    if (!in_array($itemInfo['approve_status'], ['onsale', 'offline_sale'])) {
                        throw new ResourceException(trans('OrdersBundle/Order.item_offline', ['{0}' => $itemInfo['itemName']]));
                    }
                    break;
                case 'member': // 如果订单来源为微信小程序，用户自己购买
                    if (!in_array($itemInfo['approve_status'], ['onsale', 'offline_sale'])) {
                        throw new ResourceException(trans('OrdersBundle/Order.item_offline', ['{0}' => $itemInfo['itemName']]));
                    }
                    // if (property_exists($this->orderInterface, 'isCheckPoint') &&
                    //     !$this->orderInterface->isCheckPoint &&
                    //     $params['pay_type'] == 'point') {
                    //     throw new ResourceException('没有开启积分换购');
                    // }
            }
        }
        if (isset($params['items'])) {
            $itemNewList = array_bind_key($itemList['list'], 'itemId');
            $orderItemList = [];
            foreach ($params['items'] as $v) {
                $v['activity_id'] = $v['activity_id'] ?? 0;
                $v['activity_type'] = $v['activity_type'] ?? 'normal';
                $v['sale_price'] = $itemNewList[$v['item_id']]['price'];
                $orderItemList[] = array_merge($itemNewList[$v['item_id']], $v);
                if (isset($v['items_id']) && is_array($v['items_id'])) {
                    foreach ($v['items_id'] as $v1) {
                        $v['activity_id'] = $v['activity_id'] ?? 0;
                        $v['activity_type'] = $v['activity_type'] ?? 'normal';
                        $orderItemList[] = array_merge($itemNewList[$v1], $v);
                    }
                }
            }

            $this->orderItemList = $orderItemList;
        } else {
            $this->orderItemList = array_column($itemList['list'], null, 'itemId');
        }

        return true;
    }

    /**
     * 格式化订单商品ID function
     *
     * @return array
     */
    protected function formatOrderItemIds($params)
    {
        if (method_exists($this->orderInterface, 'getOrderItemIds')) {
            return $this->orderInterface->getOrderItemIds($params);
        }
        if (isset($params['items'])) {
            foreach ($params['items'] as $v) {
                $itemIds[] = $v['item_id'];
                if (isset($v['items_id']) && $v['items_id']) {
                    $itemIds = array_merge($v['items_id'], $itemIds);
                }
            }
            $itemIds = array_flip(array_flip($itemIds));
        } elseif (isset($params['item_id'])) {
            $itemIds = [$params['item_id']];
        } else {
            $itemIds = [];
        }
        return $itemIds;
    }

    /**
     * 校验购买门店是否有效
     */
    public function checkShopValid($shopId)
    {
        $shopsService = new ShopsService(new WxShopsService());
        $shopInfo = $shopsService->getShopsDetail($shopId);

        if (!$shopInfo) {
            throw new ResourceException(trans('OrdersBundle/Order.current_store_not_exist'));
        } elseif ((isset($shopInfo['expired_at']) && $shopInfo['expired_at'] < time()) || !isset($shopInfo['expired_at'])) {
            throw new ResourceException(trans('OrdersBundle/Order.store_expired'));
        }

        if (isset($shopInfo['wx_shop_id']) && $shopInfo['wx_shop_id']) {
            $shopInfo['shop_id'] = $shopInfo['wx_shop_id'];
        }

        $this->shopInfo = $shopInfo;

        return true;
    }

    /**
     * 校验店铺有效性
     *
     * 如果创建订单提交了 distributor_id 参数则进行校验
     * 如果没有提交则不校验
     *
     * 如果 distributor_id 是必填参数，请在 checkCreateOrderNeedParams 方法中进行必填校验
     */
    public function checkDistributorValid($params)
    {
        $this->distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
        if ($params['distributor_id'] == 0) {
            $filter = [
                'company_id' => $params['company_id'],
                'distributor_self' => 1,
            ];
        } else {
            $filter = [
                'company_id' => $params['company_id'],
                'distributor_id' => $params['distributor_id'],
            ];
        }
        $distributorInfo = $this->distributorRepository->getInfo($filter);
        if (!$distributorInfo) {
            if ($params['distributor_id'] == 0) {
                return true;
            }
            throw new ResourceException(trans('OrdersBundle/Order.shop_not_exist'));
        }

        if ($params['distributor_id'] != 0) { //总部自提点is_valid=false，不知道为什么，只能这么判断了
            if ($distributorInfo['is_valid'] != 'true') {
                throw new ResourceException(trans('OrdersBundle/Order.shop_invalid'));
            }
        }
        // 检查店铺的商户是否开启
        if ($distributorInfo['merchant_id'] != 0) {
            $merchantService = new MerchantService();
            $merchantFilter = [
                'company_id' => $params['company_id'],
                'id' => $distributorInfo['merchant_id'],
                'disabled' => false,
            ];
            $merchantInfo = $merchantService->getInfo($merchantFilter);
            if (!$merchantInfo) {
                throw new ResourceException(trans('OrdersBundle/Order.item_offline_check_others'));
            }
        }

        // 社区团购订单不需要判断店铺支持的配送方式
        if ($this->orderInterface->orderClass != 'community') {
            if (!$distributorInfo['is_ziti'] && $params['receipt_type'] == 'ziti') {
                if ($params['distributor_id'] == 0) {
                    return true;
                }
                throw new ResourceException(trans('OrdersBundle/Order.shop_not_support_pickup'));
            }

            if ($params['receipt_type'] == 'dada') {
                $this->checkDadaValid($distributorInfo, $params);
            }
        }

        if ($params['distributor_id'] == 0) {
            return true;
        }
        $distributorItemsService = new DistributorItemsService();
        $itemIds = $this->formatOrderItemIds($params);
        foreach ($itemIds as $itemId) {
            $distributorItem = $distributorItemsService->getValidDistributorItemSkuInfo($params['company_id'], $itemId, $params['distributor_id']);
            if (!$distributorItem) {
                throw new ResourceException(trans('OrdersBundle/Order.item_invalid_please_recalculate'));
            }

            // 如果有定义店铺商品价格，则使用店铺商品价格
            if (isset($distributorItem['price']) && $this->orderInterface->orderClass != 'employee_purchase') {
                foreach ($this->orderItemList as &$v) {
                    $activityId = $v['activity_id'] ?? 0;
                    $activityType = $v['activity_type'] ?? 'normal';
                    if (0 == $activityId && 'normal' == $activityType && $itemId == $v['item_id']) {
                        $v['price'] = $distributorItem['price'];
                        break;
                    }
                }
            }

            if (isset($distributorItem['is_total_store'])) {
                foreach ($this->orderItemList as &$v) {
                    if ($itemId == $v['item_id']) {
                        $v['is_total_store'] = $distributorItem['is_total_store'];
                    }
                }
                unset($v);
            }
        }

        return true;
    }

    protected function _formatOrderData($params, $isCheck = true)
    {
        $promoterService = new PromoterService();
        if ($this->orderInterface->orderClass != 'employee_purchase') {
            // 获取当前会员的上级即推广员
            $promoterUserId = $promoterService->getPromoter($params['company_id'], $params['user_id']);
            app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.':params:' . var_export($params, true));
            app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.':promoterUserId:' . var_export($promoterUserId, true));

            $promoterShopId = 0;
            if (isset($params['promoter_shop_id']) && $params['promoter_shop_id']) {
                $promoterInfo = $promoterService->getInfo(['company_id' => $params['company_id'], 'user_id' => intval($params['promoter_shop_id'])]);
                if ($promoterInfo && $promoterInfo['shop_status'] == 1) {
                    $promoterShopId = intval($params['promoter_shop_id']);
                }
                app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.':promoterInfo:' . var_export($promoterInfo, true));
                app('log')->info(':export brokerage:'.__FUNCTION__.__LINE__.':promoterShopId:' . var_export($promoterShopId, true));

            }
        }

        //业务员 导购分享逻辑
        if (isset($params['distributor_id']) && $params['distributor_id']) {
            $distributorService = new DistributorService();
            $distributorInfo = $distributorService->getInfoSimple(['distributor_id' => $params['distributor_id'] ]);
            app('log')->debug("\n".__FUNCTION__."-".__LINE__.":distributorInfo:". json_encode($distributorInfo));
            if(isset($distributorInfo['is_open_salesman']) && $distributorInfo['is_open_salesman'] ){
                $promoterUserId = $promoterService->getPromoter($params['company_id'], $params['user_id']);
            }
        }
        //分享扫码的导购ID
        $key = "promoter_user_info_dayset:" . $params['user_id'] ;
        $promoterinfo_json = app('redis')->get($key);
        app('log')->info(':brokerage:'.__FUNCTION__.__LINE__.':filter_salesperson:' . json_encode($promoterinfo_json));

        $promoterinfoData = json_decode($promoterinfo_json,true);
        if(isset($promoterinfoData['promoter_user_id'])
        && $promoterinfoData['promoter_user_id'] ){
            $promoterUserId = $promoterinfoData['promoter_user_id'];
        }

        //业务员 代课下单
        if(isset($params['promoter_user_id']) && $params['promoter_user_id']){
            $promoterUserId = $params['promoter_user_id'];
        }

        //导购ID信息处理
        if(isset($promoterUserId) && isset($distributorInfo['is_open_salesman']) && $distributorInfo['is_open_salesman'] ){
            // check 业务员 导购店铺
            $salespersonService = new SalespersonService();

            $filter_salesperson = array(
                'company_id'=> $params['company_id'],
                'user_id'=> $promoterUserId,//$promoterinfoData['promoter_user_id'],
                'shop_id'=> $params['distributor_id'],
                'is_valid'=> 'true',
            );
            app('log')->info(':brokerage:'.__FUNCTION__.__LINE__.':filter_salesperson:' . json_encode($filter_salesperson));

            $salespersonInfo = $salespersonService->getInfo($filter_salesperson);
            app('log')->info(':brokerage:'.__FUNCTION__.__LINE__.':salespersonInfo:' . json_encode($salespersonInfo));


            if(isset($salespersonInfo['user_id']) && $salespersonInfo['user_id']  ){
                $promoterUserId = $promoterUserId;
                $params['salesman_id'] = $promoterUserId;
                app('log')->info(':brokerage:'.__FUNCTION__.__LINE__.':promoterUserId:' . json_encode($promoterUserId));
            }else{
                $promoterUserId = 0;
                $salespersonInfo = array('user_id'=>'','name'=>'','mobile'=>'');
            }

        }else{
            $promoterUserId = 0;
            $salespersonInfo = array('user_id'=>'','name'=>'','mobile'=>'');
        }

        //创建订单时，导购员id获取前端传参，不予取会员原有绑定的导购员
        //$distributorUserService = new DistributorUserService();
        //$salesman = $distributorUserService->getInfo(['user_id' => $params['user_id'], 'company_id' => $params['company_id']]);
        $companyService = new CompanysService();
        if (isset($params['order_third_params']) && $params['order_third_params']) {
            $thirdParams = $params['order_third_params'];
        } else {
            $companyInfo = $companyService->getInfo(['company_id' => $params['company_id']]);
            $thirdParams = $companyInfo['third_params'];
        }

        //订单超时未支付取消的时间，分钟
        $cancelTime = $this->getOrdersSetting($params['company_id'], 'order_cancel_time');
        if (isset($params['pay_type']) && $params['pay_type'] == 'offline') {
            //线下支付的订单，不做自动取消
            $cancelTime = 24 * 60 * 365; //分钟
        }
        if (isset($params['pay_type']) && $params['pay_type'] == 'offline_pay') {
            $errMsg = '';
            $offlinePayService = new OfflinePayService();
            $cancelTime = $offlinePayService->getAutoCancelTime($params['company_id'], $errMsg);
            if ($errMsg) {
                throw new ResourceException($errMsg);
            }
        }

        //获取商户是否开启分账设置
        $is_profitsharing = 1; //1订单不进行分账 2订单需分账
        $profitsharing_rate = '';//分账手续费率
        //社区团购订单、拼团订单、秒杀订单、积分商城 无需分账
        if (!in_array($this->orderInterface->orderClass, ['community', 'groups', 'seckill', 'pointsmall', 'employee_purchase'])) {
            // if ($this->orderInterface->orderClass != 'community' && $this->orderInterface->orderClass != 'groups' && $this->orderInterface->orderClass != 'seckill') {
            $hfpayLedgerConfigService = new HfpayLedgerConfigService();
            $hfpayLedgerConfig = $hfpayLedgerConfigService->getLedgerConfig(['company_id' => $params['company_id']]);
            if (!empty($hfpayLedgerConfig) && $hfpayLedgerConfig['is_open'] == 'true') {
                //判断门店是否开启分账
                $distributor_id = (isset($params['distributor_id']) && $params['distributor_id']) ? intval($params['distributor_id']) : 0;
                if ($distributor_id != 0) {
                    $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
                    $distributor_info = $distributorRepository->getInfoById($distributor_id);
                    if ($distributor_info['is_open'] == 'true') {
                        $is_profitsharing = 2;
                        $profitsharing_rate = !empty($distributor_info['rate']) ? $distributor_info['rate'] : bcmul($hfpayLedgerConfig['rate'], 100); //店铺设置费率使用店铺，否则使用平台
                    }
                }
            }
        }
        // 查询会员的绑定导购信息
        if ($this->orderInterface->orderClass != 'employee_purchase') {
            app('log')->info(':getBindSalesmanData:'.__FUNCTION__.__LINE__.':params:' . json_encode($params));
            $bindSalesmanData = $this->getBindSalesmanData($params['company_id'], $params['user_id']);
            app('log')->info(':getBindSalesmanData:'.__FUNCTION__.__LINE__.':bindSalesmanData:' . json_encode($bindSalesmanData));
            if (!empty($params['distributor_id'])) {
                $distributorService = new DistributorService();
                $distributorInfo = $distributorService->getInfoSimple(['distributor_id' => $params['distributor_id']]);
            }

            
        }

        $distributor_id = $params['distributor_id'] ?? 0;
        // if ($distributor_id) {
        //     $orderHolder = 'distributor';
        // } else {
        //     $orderHolder = 'self';
        // }
        $orderHolder = 'self';//只需要区分供应商和平台

        $orderData = [
            'order_id' => $this->genId($params['user_id']),
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
            'shop_id' => isset($this->shopInfo['shop_id']) ? $this->shopInfo['shop_id'] : 0,
            'store_name' => isset($this->shopInfo['store_name']) ? $this->shopInfo['store_name'] : '',
            'distributor_id' => $distributor_id ? intval($params['distributor_id']) : 0,
            'is_distribution' => $distributor_id ? true : false, // 是否是分销订单
            'order_holder' => $orderHolder, // 订单归属
            'remark' => isset($params['remark']) ? $params['remark'] : '',
            'mobile' => isset($params['mobile']) ? $params['mobile'] : '',
            'order_source' => isset($params['order_source']) ? $params['order_source'] : 'member',
            'operator_desc' => isset($params['operator_desc']) ? $params['operator_desc'] : '',
            'authorizer_appid' => isset($params['authorizer_appid']) ? $params['authorizer_appid'] : '',
            'wxa_appid' => isset($params['wxa_appid']) ? $params['wxa_appid'] : '',
            'order_class' => $this->orderInterface->orderClass,
            'order_type' => $this->orderInterface->orderType,
            'order_status' => 'NOTPAY',
            'auto_cancel_time' => time() + $cancelTime * 60, // 获取默认未支付自动取消时间
            'discount_fee' => 0,
            'discount_info' => null,
            'promoter_user_id' => $promoterUserId ?? 0,
            'promoter_shop_id' => $promoterShopId ?? 0,
            'pay_type' => $params['pay_type'] ?? '',
            'salesman_id' => $params['salesman_id'] ?? 0,
            'sale_salesman_distributor_id' => $params['distributor_id'] ?? 0,// 销售导购店铺id
            'bind_salesman_id' => $bindSalesmanData['bind_salesman_id'] ?? 0,// 绑定导购id
            'bind_salesman_distributor_id' => $bindSalesmanData['bind_salesman_distributor_id'] ?? 0,// 绑定导购店铺id
            'chat_id' => $params['chat_id'] ?? 0,// 客户群ID
            'third_params' => $thirdParams,
            'point_use' => $params['point_use'] ?? 0,
            'point_fee' => 0,
            'get_point_type' => 1,
            'is_profitsharing' => $is_profitsharing,
            'profitsharing_rate' => $profitsharing_rate,
            'pack' => (isset($params['pack']) && $params['pack']) ? json_encode($params['pack']) : '',
            'max_point' => 0,
            'limit_point' => 0,
            'is_open_deduct_point' => false,
            'freight_type' => 'cash',// 运费类型-用于积分商城订单 cash:现金 point:积分
            'pay_channel' => $params['pay_channel'] ?? null,//adapay的支付渠道
            'app_pay_type' => get_app_pay_type($params['pay_type'] ?? '', $params['user_device'] ?? ''),
            'merchant_id' => $distributorInfo['merchant_id'] ?? 0,
            'self_delivery_time' => $params['self_delivery_time'] ?? 0,
            'source_from' => $params['source_from'] ?? null,
            'salespersonInfo' => $salespersonInfo ?? array('user_id'=>'','name'=>'','mobile'=>''),
            'invoice_status' => '',
        ];

        //查询积分规则
        $pointRuleService = new PointMemberRuleService();
        $orderData['point_rule'] = $pointRuleService->getPointRule($params['company_id']);

        // 包装选项拼接到备注上
        if (isset($params['pack']) && $params['pack']) {
            $orderData['remark'] = $orderData['remark'] . '【包装选项】—需要' . $params['pack']['packName'];
        }

        //获取系统默认货币
        if ($this->orderInterface->orderType == 'normal' && (!isset($params['order_source']) || $params['order_source'] != 'shop')) {
            $cur = $this->getCur($params['company_id']);
            $orderData['fee_rate'] = isset($cur['rate']) ? $cur['rate'] : '';
            $orderData['fee_type'] = isset($cur['currency']) ? $cur['currency'] : '';
            $orderData['fee_symbol'] = isset($cur['symbol']) ? $cur['symbol'] : '';
        }

        // 设置和获取千人千码的跟踪参数
        if ($this->orderInterface->orderClass != 'employee_purchase') {
            $trackIds = $this->getTrackIds($params['company_id'], $params);
            $orderData['source_id'] = $trackIds['source_id'];
            $orderData['monitor_id'] = $trackIds['monitor_id'];
        }

        if ($orderData['order_type'] == 'normal') {
            //获取满赠
            $orderData = $this->__formatNormalOrder($orderData, $params, $isCheck);
            //计算优惠券折扣
            //是否支持优惠券折扣
            if (property_exists($this->orderInterface, 'isSupportCouponDiscount') && $this->orderInterface->isSupportCouponDiscount) {
                if (!($params['not_use_coupon'] ?? 0)) {
                    if (!$isCheck && (!isset($params['coupon_discount']) || !$params['coupon_discount'])) {
                        //前端没有选择优惠券默认使用折扣最大的优惠券
                        $params['coupon_discount'] = $this->getOptimalCoupon($params['user_id'], $params['company_id'], $orderData);
                    }

                    if (isset($params['coupon_discount']) && $params['coupon_discount']) {
                        try {
                            $orderData = $this->getCouponDeduction($params['user_id'], $params['coupon_discount'], $params['company_id'], $orderData);
                        } catch (\Exception $e) {
                            app('log')->debug('优惠券使用报错:'.$e->getMessage());
                        }
                    }
                }
            }

            // 计算会员标签价格（员工折扣）
            if ($this->orderInterface->orderClass != 'employee_purchase') {
                $specificCrowdDiscountService = new SpecificCrowdDiscountService();
                $orderData = $specificCrowdDiscountService->getUserOrientationDiscount($params['company_id'], $params['user_id'], $orderData);
            }

            // 赠品金额分摊
            if ($orderData['order_type'] == 'normal' && isset($orderData['items_promotion'])) {
                $giftActivitys = [];
                foreach ($orderData['items_promotion'] as $itemPromotion) {
                    if ($itemPromotion['activity_type'] == 'full_gift') {
                        if (!isset($giftActivitys[$itemPromotion['activity_id']])) {
                            $giftActivitys[$itemPromotion['activity_id']] = $itemPromotion['activity_desc'];
                        }
                    }
                }
                foreach ($giftActivitys as $activityId => $giftActivity) {
                    $itemsTotalFee = [];
                    $totalGiftFee = 0;
                    foreach ($orderData['items'] as $key => $item) {
                        if ($item['order_item_type'] == 'normal' && in_array($item['item_id'], $giftActivity['activity_item_ids'])) {
                            $itemsTotalFee[$key] = $item['total_fee'];
                        }
                        if ($item['order_item_type'] == 'gift' && $item['activity_id'] == $activityId) {
                            $itemsTotalFee[$key] = $item['item_fee'];
                            $totalGiftFee += $item['item_fee'];
                        }
                    }
                    asort($itemsTotalFee);

                    $percent = bcdiv($totalGiftFee, array_sum($itemsTotalFee), 5);
                    $i = 1;
                    foreach ($itemsTotalFee as $key => $itemTotalFee) {
                        if ($i++ == count($itemsTotalFee)) {
                            $discountFee = $totalGiftFee;
                        } else {
                            $discountFee = bcmul($itemTotalFee, $percent);
                            $totalGiftFee -= $discountFee;
                        }

                        if ($orderData['items'][$key]['order_item_type'] == 'normal') {
                            $orderData['items'][$key]['discount_fee'] += $discountFee;
                            $orderData['items'][$key]['total_fee'] -= $discountFee;
                        }

                        if ($orderData['items'][$key]['order_item_type'] == 'gift') {
                            $orderData['items'][$key]['discount_fee'] = $discountFee;
                            $orderData['items'][$key]['total_fee'] = $orderData['items'][$key]['item_fee'] - $discountFee;
                        }

                        $itemDiscountInfo = $giftActivity['discount_desc'];
                        $itemDiscountInfo['discount_fee'] = $discountFee;
                        if (!isset($orderData['items'][$key]['discount_info'])) {
                            $orderData['items'][$key]['discount_info'] = [];
                        }
                        array_push($orderData['items'][$key]['discount_info'], $itemDiscountInfo);
                    }
                }
            }

            //余额支付
            if ('deposit' == $params['pay_type']) {
                $depositTrade = new DepositTrade();
                $remainingDepositTotal = $depositTrade->getUserDepositTotal($params['company_id'], $params['user_id']);
                if (!$remainingDepositTotal || $remainingDepositTotal < $orderData['total_fee']) {
                    throw new ResourceException(trans('OrdersBundle/Order.insufficient_balance'));
                }
            }

            if (property_exists($this->orderInterface, 'isSupportGetPoint') && $this->orderInterface->isSupportGetPoint) {
                $pointMemberRuleService = new PointMemberRuleService();
                $orderData['bonus_points'] = $pointMemberRuleService->shoppingGivePoint($params['company_id'], $orderData['total_fee']);
            }

            if (!in_array($this->orderInterface->orderClass, ['community', 'employee_purchase']) && $params['receipt_type'] == 'dada') {
                // 店铺是否打烊
                $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
                if (empty($params['distributor_id'])) {
                    $distributorInfo = $distributorRepository->getInfo(['company_id' => $params['company_id'],'distributor_self' => 1]);
                } else {
                    $distributorInfo = $distributorRepository->getInfoById($params['distributor_id']);
                }
                $hour = explode('-', $distributorInfo['hour']);
                $start_time = date('Y-m-d').' '.$hour[0] ?? '00:00';
                $end_time = date('Y-m-d').' '.$hour[1] ?? '00:00';
                if (time() < strtotime($start_time) || time() > strtotime($end_time)) {
                    $orderData['extraTips'] = '该门店已打烊，预计明天配送';
                }
            }
        } elseif ($orderData['order_type'] == 'service') {
            $orderData = $this->__formatServiceOrder($orderData, $params);
        }
        if (method_exists($this->orderInterface, 'formatOrderData')) {
            $orderData = $this->orderInterface->formatOrderData($orderData, $params, $isCheck);
        }

        return $orderData;
    }

    // 设置和返回千人千码的最新参数
    private function getTrackIds($companyId, $params)
    {
        $userService = new UserService();
        $userInfo = $userService->getUserById($params['user_id'], $companyId);
        $userInfo['latest_source_id'] = isset($userInfo['latest_source_id']) ? $userInfo['latest_source_id'] : 0;
        $userInfo['latest_monitor_id'] = isset($userInfo['latest_monitor_id']) ? $userInfo['latest_monitor_id'] : 0;
        if (isset($params['source_id']) && isset($params['monitor_id'])) {
            if (
                ($userInfo['latest_source_id'] != $params['source_id']) ||
                ($userInfo['latest_monitor_id'] != $params['monitor_id'])
            ) {
                $memberService = new MemberService();
                $userInfo = $memberService->updateMemberInfo(
                    ['latest_source_id' => $params['source_id'], 'latest_monitor_id' => $params['monitor_id']],
                    ['company_id' => $params['company_id'], 'user_id' => $params['user_id']]
                );
            }
        }
        $data['source_id'] = $userInfo['latest_source_id'] ? $userInfo['latest_source_id'] : 0;
        $data['monitor_id'] = $userInfo['latest_monitor_id'] ? $userInfo['latest_monitor_id'] : 0;

        return $data;
    }

    /**
     * 格式化实体订单类型的订单数据
     */
    private function __formatNormalOrder($orderData, $params, $isCheck = true)
    {
        // 订单商品结构
        $orderData = $this->__formatNormalOrderItem($orderData, $params, $isCheck);
        //获取订单优惠信息及优惠金额, 运费之前计算商品促销
        //并且增加赠品数据
        if (method_exists($this->orderInterface, 'getOrderItemPromotion')) {
            $orderData = $this->orderInterface->getOrderItemPromotion($orderData, $params['isShopScreen'] ?? 0);
        }

        //判断是否跨境
        if (isset($orderData['items'][0]['type']) and $orderData['items'][0]['type'] == 1) {
            $orderData['taxable_fee'] = intval($orderData['total_fee']);    // 跨境计税价格
            $orderData = $this->crossBorderHandle($params, $orderData);
        }

        // 计算会员标签价格（员工折扣）
        //$specificCrowdDiscountService = new SpecificCrowdDiscountService();
        //$orderData = $specificCrowdDiscountService->getUserOrientationDiscount($params['company_id'], $params['user_id'], $orderData);
        $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
        if (empty($params['distributor_id'])) {
            $distributorInfo = $distributorRepository->getInfo(['company_id' => $params['company_id'],'distributor_self' => 1]);
        } else {
            $distributorInfo = $distributorRepository->getInfoById($params['distributor_id']);
        }

        $orderData['freight_fee'] = 0;
        if ($params['receipt_type'] == OrderReceiptTypeConstant::LOGISTICS) {
            //是否需要计算运费
            $orderData['receiver_name'] = $params['receiver_name'] ?? '';
            $orderData['receiver_mobile'] = $params['receiver_mobile'] ?? '';
            $orderData['receiver_zip'] = isset($params['receiver_zip']) ? $params['receiver_zip'] : '';
            $orderData['receiver_state'] = $params['receiver_state'] ?? '';
            $orderData['receiver_city'] = $params['receiver_city'] ?? '';
            $orderData['receiver_district'] = $params['receiver_district'] ?? '';
            $orderData['receiver_address'] = $params['receiver_address'] ?? '';
            $shippingTemplatesService = new ShippingTemplatesService();
            if (property_exists($this->orderInterface, 'isNotHaveFreight') && $this->orderInterface->isNotHaveFreight) {
                $orderData['freight_fee'] = 0;
            } else {
                $orderData['freight_fee'] = 0;
                if (isset($orderData['receiver_state'], $orderData['receiver_city'], $orderData['receiver_district']) && $orderData['receiver_state'] && $orderData['receiver_city'] && $orderData['receiver_district']) {
                    $orderData['freight_fee'] = $shippingTemplatesService->countFreightFee($orderData['items'], $orderData['company_id'], [$orderData['receiver_state'], $orderData['receiver_city'], $orderData['receiver_district']], $isCheck, $orderData['supplier_freight_fee']);
                }
            }
            if ($this->orderInterface->orderClass == 'pointsmall') {
                $pointsmallFee = $this->orderInterface->getOrderTotalFee($orderData['company_id'], $orderData['total_fee'], $orderData['freight_fee'], $orderData['point']);
                $orderData['total_fee'] = $pointsmallFee['total_fee'];
                $orderData['point'] = $pointsmallFee['point'];
                $orderData['freight_fee'] = $pointsmallFee['freight_fee'];
                $orderData['freight_type'] = $pointsmallFee['freight_type'];
            } else {
                $orderData['total_fee'] = $orderData['total_fee'] > 0 ? $orderData['total_fee'] + $orderData['freight_fee'] : 0; // 订单总金额
            }
        } elseif ($params['receipt_type'] == OrderReceiptTypeConstant::ZITI) {
            $orderData['receiver_name'] = $params['receiver_name'] ?? '';
            $orderData['receiver_mobile'] = $params['receiver_mobile'] ?? '';

            // 部分调货
            $logisticsItems = [];
            if ($orderData['is_logistics'] ?? false) {
                foreach ($orderData['items'] as $item) {
                    if ($item['is_logistics'] ?? false) {
                        $logisticsItems[] = $item;
                    }
                }
                if ($logisticsItems) {
                    $orderData['receiver_zip'] = isset($params['receiver_zip']) ? $params['receiver_zip'] : '';
                    $orderData['receiver_state'] = $params['receiver_state'] ?? '';
                    $orderData['receiver_city'] = $params['receiver_city'] ?? '';
                    $orderData['receiver_district'] = $params['receiver_district'] ?? '';
                    $orderData['receiver_address'] = $params['receiver_address'] ?? '';
                    $shippingTemplatesService = new ShippingTemplatesService();
                    if (property_exists($this->orderInterface, 'isNotHaveFreight') && $this->orderInterface->isNotHaveFreight) {
                        $orderData['freight_fee'] = 0;
                    } else {
                        $orderData['freight_fee'] = 0;
                        if (isset($orderData['receiver_state'], $orderData['receiver_city'], $orderData['receiver_district']) && $orderData['receiver_state'] && $orderData['receiver_city'] && $orderData['receiver_district']) {
                            $orderData['freight_fee'] = $shippingTemplatesService->countFreightFee($logisticsItems, $orderData['company_id'], [$orderData['receiver_state'], $orderData['receiver_city'], $orderData['receiver_district']], $isCheck, $orderData['supplier_freight_fee']);
                        } else {
                            if ($isCheck) {
                                throw new ResourceException(trans('OrdersBundle/Order.login_required_for_shipping_address'));
                            }
                        }
                    }
                    $orderData['total_fee'] = $orderData['total_fee'] > 0 ? $orderData['total_fee'] + $orderData['freight_fee'] : 0; // 订单总金额
                }
            }

            if (count($logisticsItems) == count($orderData['items'])) {
                $orderData['receipt_type'] = 'logistics';
            } else {
                if ($isCheck && !in_array($this->orderInterface->orderClass, ['shopadmin', 'community'])) {
                    // 检查自提时间
                    $pickupLocationService = new PickupLocationService();
                    $pickupLocationService->checkPickupTime($params['company_id'], $params['pickup_location'], $params['pickup_date'], $params['pickup_time']);
                }

                $orderData['ziti_code'] = $this->orderInterface->getCode(6);
                $orderData['receipt_type'] = 'ziti';
                $orderData['ziti_status'] = 'PENDING';
            }
        } elseif ($params['receipt_type'] == OrderReceiptTypeConstant::DADA || $params['receipt_type'] == OrderReceiptTypeConstant::MERCHANT) {
            $orderData['receiver_name'] = $params['receiver_name'] ?? '';
            $orderData['receiver_mobile'] = $params['receiver_mobile'] ?? '';
            $orderData['receiver_zip'] = isset($params['receiver_zip']) ? $params['receiver_zip'] : '';
            $orderData['receiver_state'] = $params['receiver_state'] ?? '';
            $orderData['receiver_city'] = $params['receiver_city'] ?? '';
            $orderData['receiver_district'] = $params['receiver_district'] ?? '';
            $orderData['receiver_address'] = $params['receiver_address'] ?? '';
            if($distributorInfo['is_self_delivery']){
                $selfDeliveryService = new selfDeliveryService();
                $setting = $selfDeliveryService->getSelfDeliverySetting($params['company_id'],$params['distributor_id'], $distributorInfo['distributor_self']);
                if (!$setting || $setting['is_open'] != 'true') {
                    throw new ResourceException(trans('OrdersBundle/Order.merchant_self_delivery_not_enabled'));
                }

                $orderData['receipt_type'] = 'merchant';

                if($orderData['total_fee'] < bcmul($setting['min_amount'], 100)){
                    throw new ResourceException(trans('OrdersBundle/Order.order_amount_requirement_for_self_delivery', ['{0}' => $setting['min_amount']]));
                }
                $rules = $setting['rule'] ?? [];
                $freight_fee = $setting['freight_fee'];
                foreach ($rules as $rule){
                    if($orderData['total_fee'] >= bcmul($rule['full'], 100)){
                        $freight_fee = $rule['freight_fee'];
                    }
                }
                $orderData['self_delivery_status'] = 'CONFIRMING';
                $orderData['freight_fee'] = bcmul($freight_fee, 100);
                $orderData['total_fee'] = $orderData['total_fee'] > 0 ? $orderData['total_fee'] + $orderData['freight_fee'] : 0; // 订单总金额

            }else{
                // ToDo 去达达查询运费
                $orderData['receipt_type'] = 'dada';

                if ($orderData['receiver_name'] && $orderData['receiver_mobile'] && $orderData['receiver_city']) {

                    // 先判断是否支持商家自配，如果不行，则使用达达配送
                    $merchantStatus = (new CompanyRelDeliveryService())->getFreightFee($orderData);
                    if (!$merchantStatus) {
                        $localDeliveryService = new LocalDeliveryService();
                        $orderData = $localDeliveryService->getOrderService()->getFreightFee($orderData);
                    }
                }
            }

        }
        //记录发票信息
        if (isset($params['invoice_type'], $params['invoice_content'])) {
            $orderData['invoice'] = $params['invoice_content'];
            $orderData['invoice']['type'] = $params['invoice_type'];
        }

        $orderData['pay_type'] = $params['pay_type'] ?? '';
        $orderData['is_shopscreen'] = $params['isShopScreen'] ?? 0;

        if (!$orderData['user_id']) {
            $orderData['bind_auth_code'] = (string)rand(100000, 999999);
        }

        if ($this->orderInterface->orderType == 'normal' && $this->orderInterface->orderClass == 'normal') {
            //获取同城配送的送达时间
            if(isset($orderData['receipt_type']) && $orderData['receipt_type'] == 'merchant' && $distributorInfo['is_self_delivery'] && $distributorInfo['freight_time'] > 0){
                $orderData['deliveryTimeList'] = $this->getDeliveryTimeList($distributorInfo['hour'],$distributorInfo['freight_time']);
            }
            $orderData['is_require_subdistrict'] = false;
            if ($distributorInfo['is_require_subdistrict'] ?? false) {
                $orderData['is_require_subdistrict'] = true;
                if ($isCheck) {
                    if (!isset($params['subdistrict_parent_id']) || !$params['subdistrict_parent_id'] || !isset($params['subdistrict_id']) || !$params['subdistrict_id']) {
                        throw new ResourceException(trans('OrdersBundle/Order.select_street_and_committee'));
                    }
                    $orderData['subdistrict_parent_id'] = $params['subdistrict_parent_id'];
                    $orderData['subdistrict_id'] = $params['subdistrict_id'];
                } else {
                    $recent = $this->orderInterface->getList(['user_id' => $orderData['user_id'], 'distributor_id' => ($params['distributor_id'] ?: 0), 'subdistrict_parent_id|gt' => 0, 'subdistrict_id|gt' => 0], 0, 1, ['create_time' => 'DESC'], 'subdistrict_parent_id,subdistrict_id');
                    if ($recent) {
                        $orderData['subdistrict_parent_id'] = $recent[0]['subdistrict_parent_id'];
                        $orderData['subdistrict_id'] = $recent[0]['subdistrict_id'];
                    }
                }
            }
            $orderData['is_require_building'] = false;
            if ($distributorInfo['is_require_building'] ?? false) {
                $orderData['is_require_building'] = true;
                if ($isCheck) {
                    // if (!isset($params['building_number']) || !$params['building_number'] || !isset($params['house_number']) || !$params['house_number']) {
                    //     throw new ResourceException("请填写楼号和房号");
                    // }
                    $orderData['building_number'] = $params['building_number'] ?? '';
                    $orderData['house_number'] = $params['house_number'] ?? '';
                } else {
                    $recent = $this->orderInterface->getList(['user_id' => $orderData['user_id'], 'distributor_id' => ($params['distributor_id'] ?: 0), 'building_number|neq' => '', 'house_number|neq' => ''], 0, 1, ['create_time' => 'DESC'], 'building_number,house_number');
                    if ($recent) {
                        $orderData['building_number'] = $recent[0]['building_number'];
                        $orderData['house_number'] = $recent[0]['house_number'];
                    }
                }
            }
        }

        return $orderData;
    }

    /**
     * 购买实体商品结构 function
     *
     * @return array
     */
    private function __formatNormalOrderItem($orderData, $params, $isCheck = true)
    {
        $order_holder = [];
        $marketFee = 0;
        $itemFee = 0;
        $totalFee = 0;
        $discountFee = 0;
        $discountInfo = [];
        $totalRebate = 0;
        $costFee = 0;
        $item_point = 0;
        $point = 0;
        $price = 0;
        $totalItemNum = 0;
        $totalCommissionFee = 0;//订单总佣金
        $supplierFreightFee = [];
        $itemsCommissionService = new ItemsCommissionService();
        foreach ($this->orderItemList as $itemInfo) {
            if (!in_array($this->orderInterface->orderClass, ['pointsmall', 'employee_purchase'])) {
                $memberpreference = $this->checkCurrentMemberpreferenceByItemId($orderData['company_id'], $orderData['user_id'], $itemInfo['itemId'], $orderData['distributor_id'], false, $msg);
                if (!$memberpreference) {
                    $orderData['extraTips'] = $msg;
                    if ($isCheck) {
                        throw new ResourceException($msg);
                    }
                }
            }

            //获取商品原价
            if (method_exists($this->orderInterface, 'getOrderItemOriginalPrice')) {
                $price = $this->orderInterface->getOrderItemOriginalPrice($itemInfo['itemId'], $itemInfo['activity_id'], $itemInfo['activity_type']);
            } else {
                $price = $itemInfo['price'];
            }

            //获取最终销售价格
            if (method_exists($this->orderInterface, 'getOrderItemPrice')) {
                $sale_price = $this->orderInterface->getOrderItemPrice($itemInfo['itemId'], $itemInfo['activity_id'] ?? 0, $itemInfo['activity_type'] ?? '');
            } else {
                $sale_price = $itemInfo['price'];
            }

            //获取积分价格
            if (method_exists($this->orderInterface, 'getOrderItemPoint')) {
                $pointsmall_point = $this->orderInterface->getOrderItemPoint($itemInfo['itemId']);
            } else {
                $pointsmall_point = 0;
            }

            if (isset($itemInfo['num'])) {
                $buyNum = $itemInfo['num'];
            } else {
                $buyNum = 1;
            }

            if (method_exists($this->orderInterface, 'getOrderItemDiscountData')) {
                $result = $this->orderInterface->getOrderItemDiscountData($itemInfo['itemId'], $price, $buyNum);
                $item_fee = $result['item_fee'];
                $total_fee = $result['total_fee'];
                $discount_fee = $result['discount_fee'];
                $discount_info = [$result['discount_info']];
                $activity_price = $result['activity_price'];
            } else {
                $item_fee = $price * $buyNum;
                $total_fee = $sale_price * $buyNum;
                $discount_fee = 0;
                $discount_info = [];
                $activity_price = 0;
            }

            $commissionRatio = $itemsCommissionService->getCommissionRatio($itemInfo['goods_id']);

            $itemTmp = [
                'order_id' => $orderData['order_id'],
                'goods_id' => $itemInfo['goods_id'],
                'item_id' => $itemInfo['itemId'],
                'goods_id' => $itemInfo['goods_id'],
                'item_bn' => $itemInfo['itemBn'],
                'goods_bn' => $itemInfo['goods_bn'] ?? '',
                'company_id' => $params['company_id'],
                'user_id' => $params['user_id'],
                'item_name' => $itemInfo['itemName'],
                'templates_id' => $itemInfo['templates_id'] ?: 0,
                'pic' => isset($itemInfo['pics'][0]) ? $itemInfo['pics'][0] : '',
                'num' => $buyNum, // 购买数量
                'sale_price' => $itemInfo['sale_price'],// 商品销售价，现在只用来显示
                'price' => intval($price), // 商品原价
                'item_fee' => $item_fee, // 商品原价总金额
                'cost_fee' => $itemInfo['cost_price'] * $buyNum, // 商品总金额
                'item_unit' => $itemInfo['item_unit'],
                'total_fee' => $total_fee, // 商品支付总金额
                'commission_fee' => bcdiv($total_fee * $commissionRatio, '10000', 0), //佣金
                'discount_fee' => $discount_fee, //商品优惠金额
                'discount_info' => $discount_info, // 商品优惠明细
                'rebate' => 0, // 单个商品店奖金金额
                'total_rebate' => 0, // 商品总店铺奖金金额
                'distributor_id' => (isset($params['distributor_id']) && $params['distributor_id']) ? intval($params['distributor_id']) : 0,
                'mobile' => isset($params['mobile']) ? $params['mobile'] : '',
                'is_total_store' => isset($itemInfo['is_total_store']) ? $itemInfo['is_total_store'] : true,
                'shop_id' => isset($this->shopInfo['shop_id']) ? $this->shopInfo['shop_id'] : 0,
                'fee_rate' => isset($orderData['fee_rate']) ? $orderData['fee_rate'] : '',
                'fee_type' => isset($orderData['fee_type']) ? $orderData['fee_type'] : '',
                'fee_symbol' => isset($orderData['fee_symbol']) ? $orderData['fee_symbol'] : '',
                'item_point' => $pointsmall_point,
                'point' => $pointsmall_point * $buyNum,
                'item_spec_desc' => $itemInfo['item_spec_desc'] ?? '',
                'default_item_id' => $itemInfo['default_item_id'] ?? $itemInfo['item_id'],
                'volume' => $buyNum * $itemInfo['volume'],
                'weight' => $buyNum * $itemInfo['weight'],
                'order_item_type' => 'normal',
                'activity_id' => $itemInfo['activity_id'] ?? 0,
                'activity_type' => $itemInfo['activity_type'] ?? 'normal',
                'item_category' => $itemInfo['item_category'] ?? '',
                'type' => $itemInfo['type'] ?? '0',
                'crossborder_tax_rate' => $itemInfo['crossborder_tax_rate'] ?? '0',
                'taxstrategy_id' => $itemInfo['taxstrategy_id'] ?? '0',
                'taxation_num' => $itemInfo['taxation_num'] ?? '0',
                'origincountry_id' => $itemInfo['origincountry_id'] ?? '0',
                'is_profit' => $itemInfo['is_profit'] ?? 0,
                'is_logistics' => isset($itemInfo['is_logistics']) && ($itemInfo['is_logistics'] === true || $itemInfo['is_logistics'] === 'true'),
                'market_price' => $itemInfo['market_price'] ?? 0,
                'cost_price' => $itemInfo['cost_price'] ?? 0,
                'is_epidemic' => $itemInfo['is_epidemic'] ?? 0,
                'supplier_id' => $itemInfo['supplier_id'] ?? 0,
                'aftersales_end_date' => $itemInfo['aftersales_end_date'] ?? 0,
                'tax_rate' => $itemInfo['tax_rate'] ?? 0,
                'is_medicine' => $itemInfo['is_medicine'] ?? 0,
                'is_prescription' => $itemInfo['is_prescription'] ?? 0,
            ];

            if ($itemTmp['supplier_id'] && !isset($supplierFreightFee[$itemInfo['supplier_id']])) {
                $supplierFreightFee[$itemTmp['supplier_id']] = 0;
            }

            if ($itemTmp['supplier_id'])  $order_holder[] = 'supplier';
            if (!$itemTmp['supplier_id'])  $order_holder[] = 'self';

            if ($activity_price > 0) {
                $itemTmp['activity_price'] = $activity_price;
            }

            if ($itemTmp['is_logistics']) {
                $itemTmp['is_total_store'] = true;
            }
            $orderData['is_logistics'] = ($orderData['is_logistics'] ?? false) || $itemTmp['is_logistics'];

            // 存在处方药商品，设置订单开方状态为未开单
            if ($itemTmp['is_prescription'] == 1) {
                $orderData['prescription_status'] = 1;
            }

            $totalItemNum += $buyNum;

            // 积分支付方式为point的时候商品金额，订单金额全部置0
            if ($params['pay_type'] == 'point' && property_exists($this->orderInterface, 'isCheckPoint') && $this->orderInterface->isCheckPoint) {
                $pointMemberRuleService = new PointMemberRuleService();
                $itemTmp['point'] = $pointMemberRuleService->moneyToPoint($params['company_id'], $itemTmp['total_fee']);
            }

            $item_point += $itemTmp['item_point'] * $buyNum;
            $point += $itemTmp['point'];
            $marketFee += (intval($itemTmp['market_price']) ?: $itemTmp['price']) * $buyNum;
            $itemFee += $itemTmp['item_fee'];
            $totalFee += $itemTmp['total_fee'];
            $totalCommissionFee += $itemTmp['commission_fee'];
            $discountFee += $itemTmp['discount_fee'];
            $costFee += $itemTmp['cost_fee'];
            $totalRebate += $itemTmp['rebate'];
            $orderItems[] = $itemTmp;
            if ($itemTmp['discount_info']) {
                $discountInfo = array_merge($discountInfo, $itemTmp['discount_info']);
            }
        }
        if ($orderData['order_holder'] == 'self' && in_array('supplier', $order_holder)) {
            if (in_array('self', $order_holder)) {
                $orderData['order_holder'] = 'self_supplier';
            } else {
                $orderData['order_holder'] = 'supplier';
            }
        }
        $orderData['item_point'] = $item_point > 0 ? $item_point : 0; // 商品积分总数量
        $orderData['point'] = $point > 0 ? $point : 0; // 积分总数量
        $orderData['market_fee'] = $marketFee > 0 ? $marketFee : 0; // 商品总金额
        $orderData['item_fee'] = $itemFee > 0 ? $itemFee : 0; // 商品总金额
        $orderData['cost_fee'] = $costFee > 0 ? $costFee : 0; // 商品总金额
        $orderData['total_fee'] = $totalFee > 0 ? $totalFee : 0; // 订单总金额
        $orderData['discount_fee'] = $discountFee; // 订单优惠总金额
        $orderData['discount_info'] = $discountInfo ;
        $orderData['commission_fee'] = $totalCommissionFee ;
        $orderData['total_rebate'] = $totalRebate > 0 ? $totalRebate : 0; // 总分销金额
        $orderData['items'] = $orderItems;
        $orderData['title'] = esub($orderData['items'][0]['item_name'], 41); // 取第一个商品名称
        $orderData['totalItemNum'] = $totalItemNum; // 取第一个商品名称
        $orderData['supplier_freight_fee'] = $supplierFreightFee;
        return $orderData;
    }

    private function __formatServiceOrder($orderData, $params)
    {
        // 服务类商品只能购买一个商品，如果服务类商品需要一次购买多个服务类商品，需要修改服务类订单结构
        $itemInfo = current($this->orderItemList);

        $price = $itemInfo['price'];
        if (method_exists($this->orderInterface, 'getOrderItemPrice')) {
            $sale_price = $this->orderInterface->getOrderItemPrice($itemInfo['itemId']);
        } else {
            $sale_price = $itemInfo['price'];
        }

        $buyNum = isset($params['item_num']) ? $params['item_num'] : 1;
        if (method_exists($this->orderInterface, 'getOrderItemDiscountData')) {
            $result = $this->orderInterface->getOrderItemDiscountData($itemInfo['itemId'], $price, $buyNum);
            $itemFee = $result['item_fee'];
            $totalFee = $result['total_fee'];
            $discount_fee = $result['discount_fee'];
            $discount_info = [$result['discount_info']];
        } else {
            $itemFee = $price * $buyNum;
            $totalFee = $sale_price * $buyNum;
            $discount_fee = 0;
            $discount_info = [];
        }

        $orderData['price'] = $price;
        $orderData['item_id'] = $itemInfo['itemId'];
        $orderData['title'] = $itemInfo['itemName'];
        $orderData['item_brief'] = $itemInfo['brief'];
        $orderData['item_pics'] = $itemInfo['pics'][0];
        $orderData['item_num'] = $buyNum;
        $orderData['consume_type'] = $itemInfo['consume_type'];
        $orderData['date_type'] = $itemInfo['date_type'];
        $orderData['begin_date'] = $itemInfo['begin_date'];
        $orderData['end_date'] = $itemInfo['end_date'];
        $orderData['fixed_term'] = $itemInfo['fixed_term'];
        $orderData['total_fee'] = $totalFee;
        $orderData['type_labels'] = $itemInfo['type_labels'];
        $orderData['cost_fee'] = $itemInfo['cost_price'] * $buyNum;
        $orderData['item_fee'] = $itemFee;
        $orderData['discount_fee'] = $discount_fee;
        $orderData['discount_info'] = $discount_info;
        return $orderData;
    }

    // 获取订单页面临时运费价格 不存入数据库

    public function getOrderTempInfo($params)
    {
        // 检查创建订单的必填参数
        $this->checkCreateOrderNeedParams($params, false);

        if (property_exists($this->orderInterface, 'isSupportCart') && $this->orderInterface->isSupportCart) {
            $params = $this->orderInterface->checkoutCartItems($params);
        }
        if(isset($params['invoice_content'])){
            app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':params:invoice_content:'.json_encode($params['invoice_content']));
        }

        $this->_check($params);
        $orderData = $this->_formatOrderData($params, false);
        // 判断是否跨境
        if (isset($params['iscrossborder']) and $params['iscrossborder'] == 1) {
//            $orderData = $this->crossBorderHandle($params, $orderData);
            $Identity_data['user_id'] = $orderData['user_id'];
            $Identity_data['company_id'] = $orderData['company_id'];
            $Identity = new Identity();
            $Identity_Info = $Identity->getInfo($Identity_data);
            if (!empty($Identity_Info)) {
                $orderData['identity_id'] = $Identity_Info['identity_id'];
                $orderData['identity_name'] = $Identity_Info['identity_name'];
            } else {
                $orderData['identity_id'] = '';
                $orderData['identity_name'] = '';
            }
        }
        // 积分抵扣数据
        if (property_exists($this->orderInterface, 'isSupportPointDiscount') &&
            $this->orderInterface->isSupportPointDiscount) {
            $orderData = $this->_formatOrderPointDeduct($params, $orderData);
        }

        // 订单改价
        if (property_exists($this->orderInterface, 'isSupportMarkDown') &&
            $this->orderInterface->isSupportMarkDown) {
            if (isset($params['markdown'])) {
                $orderData = $this->orderInterface->markDown($orderData, $params['markdown']);
                if ($orderData['total_fee'] <= 0) {
                    throw new ResourceException(trans('OrdersBundle/Order.order_payment_must_be_positive'));
                }
            }
        }

        // 计算促销优惠
        $orderData['promotion_discount'] = 0;
        foreach ($orderData['discount_info'] as $discountInfo) {
            if (in_array($discountInfo['type'], ['full_minus', 'full_discount', 'member_tag_targeted_promotion'])) {
                $orderData['promotion_discount'] += $discountInfo['discount_fee'];
            }
        }

        // 重新计算商品总价，不含价格立减活动及会员价优惠
        $orderData['item_fee_new'] = $orderData['total_fee']                  //实付金额
                                   - ($orderData['freight_fee'] ?? 0)         //减去运费
                                   + ($orderData['point_fee_item'] ?? 0)      //加上积分抵扣point_fee to   point_fee_item                                 + ($orderData['point_fee_item'] ?? 0)           //加上积分抵扣point_fee to point_feeto 
                                   + ($orderData['coupon_discount'] ?? 0)     //加上优惠券抵扣
                                   + ($orderData['promotion_discount'] ?? 0); //加上促销优惠


        return $orderData;
    }

    // 获取产地国信息
    private function getorigincountry($company_id)
    {
        $filter['company_id'] = $company_id;
        // 查询内容
        $find = [
            'origincountry_id',
            'origincountry_name',
            'origincountry_img_url',
        ];
        $origincountry = app('registry')->getManager('default')->getRepository(OriginCountry::class)->lists($filter, $find);
        return $origincountry['list'];
    }

    //跨境处理
    private function crossBorderHandle($params, $orderData)
    {
        if (empty($orderData['items'])) {
            return $orderData;
        }
        $items = $orderData['items'];

        // 产地国信息
        $origincountry = $this->getorigincountry($params['company_id']);
        $origincountry_data = array_column($origincountry, null, 'origincountry_id');
        $origincountry_idall = array_column($origincountry, 'origincountry_id');
        // 跨境设置
        $CrossBorderSet = app('registry')->getManager('default')->getRepository(CrossBorderSet::class)->getInfo(['company_id' => $params['company_id']]);
        // 判断是否设置全局税率
        if (!empty($CrossBorderSet)) {
            $default_tax_rate = $CrossBorderSet['tax_rate'];        // 全局税率
            $quota_tip = $CrossBorderSet['quota_tip'];             // 额度提示文案
        } else {
            $default_tax_rate = 0;        // 全局税率
            $quota_tip = '';             // 额度提示文案
        }

        $totalTax = 0;     // 总税费
        foreach ($items as $k => $v) {

            //忽略赠品
            if (!isset($v['type'])) {
                continue;
            }

            // 产地国信息- 是跨境商品-产地国id不为空，产地国信息存在
            if ($v['type'] == 1 and !empty($v['origincountry_id']) and in_array($v['origincountry_id'], $origincountry_idall)) {
                $items[$k]['origincountry_name'] = $origincountry_data[$v['origincountry_id']]['origincountry_name'];
                $items[$k]['origincountry_img_url'] = $origincountry_data[$v['origincountry_id']]['origincountry_img_url'];
            } else {
                $items[$k]['origincountry_name'] = '';
                $items[$k]['origincountry_img_url'] = '';
            }

            // 判断商品是否有税率
            if (!empty($v['crossborder_tax_rate']) and $v['crossborder_tax_rate'] > 0) {
                $items[$k]['tax_rate'] = $v['crossborder_tax_rate'];
            } else {
                // 判断主类目
                $filter['company_id'] = $params['company_id'];
                $filter['category_id'] = $v['item_category'];
                $item_category_tax_rate = app('registry')->getManager('default')->getRepository(ItemsCategory::class)->getInfo($filter)['crossborder_tax_rate'];
                if (!empty($item_category_tax_rate) and $item_category_tax_rate > 0) {
                    $items[$k]['tax_rate'] = $item_category_tax_rate;
                } else {
                    // 使用全局税率
                    $items[$k]['tax_rate'] = $default_tax_rate ? $default_tax_rate : 0;
                }
            }

            // 判断是否有会员折扣金额
//            if (isset($items[$k]['member_discount'])) {
//                $items[$k]['taxable_fee'] = $items[$k]['item_fee'] - $items[$k]['member_discount'];
//            } else {
//                $items[$k]['taxable_fee'] = $items[$k]['item_fee'];
//            }
            $items[$k]['taxable_fee'] = $v['total_fee'];

            // 判断是否有计税规则
            if ($items[$k]['taxstrategy_id'] != 0) {
                $Taxstrategy_tax_rate = $this->getTaxstrategy_tax_rate($items[$k]['taxstrategy_id'], $items[$k]['taxation_num'], $items[$k]['taxable_fee'], $items[$k]['company_id'], $items[$k]['num']);
                if ($Taxstrategy_tax_rate > 0) {
                    $items[$k]['tax_rate'] = $Taxstrategy_tax_rate;
                }
            }
            //计算跨境税费
            if ($items[$k]['tax_rate'] == 0) {
                $items[$k]['cross_border_tax'] = 0;
            } else {
                // 这里的total_fee 还未计算优惠卷，可以当做计税价格使用（计税价格 =  商品价格 -  活动价格 - 公司折扣）
                $bc_unit_price = bcdiv($v['total_fee'], $v['num']);  // 计算单价
                $cross_border_tax_unit = bcdiv(bcdiv(bcmul($bc_unit_price, bcmul($items[$k]['tax_rate'], 100)), 100), 100);  // 计算出来的单个税费
                $cross_border_tax = bcmul($cross_border_tax_unit, $v['num']);
                $items[$k]['cross_border_tax'] = $cross_border_tax;
            }
            $items[$k]['total_fee'] = $items[$k]['total_fee'] + $items[$k]['cross_border_tax'];

            $totalTax += $items[$k]['cross_border_tax'];
        }

        $orderData['items'] = $items;
        $orderData['total_tax'] = $totalTax > 0 ? $totalTax : 0;
        $orderData['total_fee'] = $orderData['total_fee'] + $orderData['total_tax'];
        $orderData['quota_tip'] = $quota_tip;

        return $orderData;
    }

    // 获取跨境税费规则中的税费
    public function getTaxstrategy_tax_rate($taxstrategy_id, $taxation_num, $taxable_fee, $company_id, $num)
    {
        // 单价
        $Price = bcdiv($taxable_fee, $num, 0);
        // 单份计税价格
        $OnePrice = bcdiv(bcdiv($Price, $taxation_num, 2), 100, 2);

        $taxstrategy_tax_rate = 0;
        $filter['id'] = $taxstrategy_id;
        $filter['company_id'] = $company_id;
//        $filter['state'] = 1;    // 不考虑策略当前状态是否删除
        $Strategy = new Strategy();
        $data = $Strategy->getInfo($filter);
        // 判断是否有规则
        if (!empty($data)) {
            $taxstrategy_content = $data['taxstrategy_content'];
            foreach ($taxstrategy_content as $k => $v) {
                // 判断是否符合当前规则
                if ($v['start'] < $OnePrice and $OnePrice <= $v['end']) {
                    $taxstrategy_tax_rate = $v['tax_rate'];
                    break;
                }
            }
        }
        return $taxstrategy_tax_rate;
    }

    public function tradeSuccUpdateOrderPayType($orderData, $payType)
    {
    }

    /**
     * 订单支付成功回调
     */
    public function tradeSuccUpdateOrderStatus($orderData, $payType)
    {
        if ($orderData['order_class'] == 'pointsmall') {
            // return true;
            $orderStatus = 'PAYED';
            // 检测支付单是否全部支付
            // $orderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
            // $orderInfo = $orderRepository->getInfo(['order_id' => $orderData['order_id']]);
            // if (! empty($orderInfo)) {
            //     /** @var TradeRepository $tradeRepository */
            //     $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
            //     $tradeList = $tradeRepository->lists([
            //         'order_id' => $orderData['order_id'],
            //         'trade_state' => 'SUCCESS',
            //     ]);
            //     $last_fee = $orderInfo['total_fee'];
            //     $last_point = $orderInfo['point'];
            //     foreach ($tradeList['list'] as $value) {
            //         if ($value['pay_type'] == 'point') {
            //             $last_point -= $value['pay_fee'];
            //         } else {
            //             $last_fee -= $value['pay_fee'];
            //         }
            //     }
            //     if ($last_fee == 0 && $last_point == 0) {
            //         $orderStatus = 'PAYED';
            //     }
            //}
        } else {
            if (method_exists($this->orderInterface, 'getTradeSuccOrderStatus')) {
                $orderStatus = $this->orderInterface->getTradeSuccOrderStatus($orderData, $payType);
            } elseif ($orderData['order_type'] == 'normal') {
                // 实体类商品订单，那么支付后则会已支付，需要进行后续发货等操作
                $orderStatus = 'PAYED';
            } elseif ($orderData['order_type'] == 'service') {
                // 服务类订单则直接完成，后续自动生成订单权益
                $orderStatus = 'DONE';
            } elseif ($orderData['order_type'] == 'bargain') {
                $orderStatus = 'PAYED';
            } else {
                $orderStatus = 'DONE';
            }
            if ($orderData['order_class'] == 'groups') {
                $orderStatus = 'WAIT_GROUPS_SUCCESS';
            }
            if ($orderData['order_class'] == 'shopadmin') {
                $orderStatus = 'DONE';
            }
        }

        $filter = [
            'user_id' => $orderData['user_id'],
            'company_id' => $orderData['company_id'],
            'order_id' => $orderData['order_id'],
        ];

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            // 更改订单状态
            $this->orderStatusUpdate($filter, $orderStatus, $payType);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            app('log')->debug($e->getMessage());
            throw $e;
        }

        return true;
    }

    public function emptyCart($params)
    {
        $cartService = new CartService();
        $cartType = $params['cart_type'] ?? 'cart';
        if ($cartType == 'fastbuy') {
            $cartService->setFastBuyCart($params['company_id'], $params['user_id'], []);
            return true;
        } else {
            if (isset($params['items']) && $params['items']) {
                $filter['item_id'] = array_column($params['items'], 'item_id');
                $filter['company_id'] = $params['company_id'];
                $filter['user_id'] = $params['user_id'];
                $filter['shop_id'] = $params['distributor_id'];

                return $cartService->deleteBy($filter);
            }
        }
    }

    public function getOrderInfo($companyId, $orderId, $checkaftersales = false, $from = 'api')
    {
        $result = $this->orderInterface->getOrderInfo($companyId, $orderId, $checkaftersales, $from);

        if ($this->orderInterface->orderClass == 'community') {
            $result['community_activity'] = $this->orderInterface->getActivityInfo($companyId, $result['orderInfo']['act_id']);
        }

        return $result;
    }

    /**
    * 获取会员 线上和线下的分别已支付订单总数
    * @param $user_id:用户ID
    * @param $mobile:会员手机号
    * @param $salespersonId: 导购id
    */
    public function getOrdersCount($user_id, $mobile, $salespersonId = null)
    {
        // 已支付的线上订单总数
        //$orderAssociationsRepository = app('registry')->getManager('default')->getRepository(OrderAssociations::class);
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        // $rediskey = 'member:order:' . $user_id . ':' . $mobile;
        // $result = app('redis')->connection('members')->get($rediskey);
        // if (!$result) {
        $filter = [
            'user_id' => $user_id,
            'order_status|in' => ['PAYED','WAIT_BUYER_CONFIRM','DONE'],
            'order_source' => ['shop_online', 'member'],
        ];
        if ($salespersonId) {
            $filter['salesman_id'] = $salespersonId;
        }
        //$result['online'] = (int)$orderAssociationsRepository->count($filter);
        $result['online'] = (int)$normalOrdersRepository->count($filter);
        $filter['order_source'] = 'shop_offline';
        //$result['offline'] = (int)$orderAssociationsRepository->count($filter);
        $result['offline'] = (int)$normalOrdersRepository->count($filter);
        unset($filter['order_source']);
        $result['total_fee'] = (int)$normalOrdersRepository->sum($filter, 'total_fee');

        // if ($result['online'] && $result['offline']) {
        //     // 已支付的线上订单总数
        //     app('redis')->connection('members')->set($rediskey, json_encode($result));
        //     app('redis')->connection('members')->expire($rediskey, 3600);
        // }
        // } else {
        //     $result = json_decode($result, true);
        // }
        return $result;
    }

    // 已支付订单取消
    public function groupOrderCancel($orderService, $orderInfo)
    {
        if ($orderInfo['order_type'] == 'service' || $orderInfo['order_class'] != 'groups') {
            return true;
        }
        $params = [
            'company_id' => $orderInfo['company_id'],
            'order_id' => $orderInfo['order_id'],
            'cancel_reason' => '拼团失败，取消退款',
            'cancel_from' => 'shop',
        ];
        $cancelData = $this->__preCancelData($orderInfo, $params);

        $cancelData['refund_status'] = 'SUCCESS';//退款状态 等待审核
        $cancelData['progress'] = 3;

        $cancelOrderRepository = app('registry')->getManager('default')->getRepository(CancelOrders::class);
        $cancelFilter = [
            'order_id' => $orderInfo['order_id'],
            'user_id' => $orderInfo['user_id'],
            'company_id' => $orderInfo['company_id'],
        ];
        $cancelOrder = $cancelOrderRepository->getInfo($cancelFilter);
        if ($cancelOrder) {
            throw new ResourceException(trans('OrdersBundle/Order.cannot_cancel_order_repeatedly_with_exclamation'));
        }
        app('log')->debug('直接创建取消订单：'. var_export($cancelData, 1));

        $cancelOrder = $cancelOrderRepository->create($cancelData);

        // 生成退款单，不实际退款
        $aftersalesRefundService = new AftersalesRefundService();
        $orderData = $orderService->getOrderInfo($orderInfo['company_id'], $orderInfo['order_id']);
        $aftersalesparams = [
            'company_id' => $orderInfo['company_id'],
            'refund_type' => 1,
            'refund_channel' => 'original',
        ];
        $res = $aftersalesRefundService->createRefundSuccess($orderData['orderInfo'], $orderData['tradeInfo'], $aftersalesparams);
        return $res;
    }

    /**
    * 积分抵扣，组织订单数据
    */
    public function _formatOrderPointDeduct($params, $orderData)
    {
        //用户剩余积分
        $pointMemberService = new PointMemberService();

        $memberPointInfo = $pointMemberService->getInfo(['user_id' => $orderData['user_id'],'company_id' => $orderData['company_id']]);
        $memberPoint = $memberPointInfo['point'] ?? 0;
        //获取本单可用的积分数
        $pointRuleService = new PointMemberRuleService($orderData['company_id']);

        $pointRulesConfig = $pointRuleService->getPointRule($orderData['company_id']);
        $orderData['user_point'] = $memberPoint;
        $upvaluation = false;
        if ($pointRulesConfig['isOpenMemberPoint'] == 'true' && $pointRulesConfig['isOpenDeductPoint'] == 'true') {
            // 查找积分翻倍活动
            $pointupvaluationService = new PointupvaluationService();
            $pointupvaluation = $pointupvaluationService->getEligibleActivity($orderData['company_id'], $orderData['user_id'], '1');
            $upvaluation = $pointupvaluation['upvaluation'] ?? false;
            if ($upvaluation) {
                $orderData['pointupvaluation'] = [
                    'upvaluation' => $pointupvaluation['upvaluation'],
                    'uppoints' => $pointupvaluation['uppoints'],
                    'max_up_point' => $pointupvaluation['max_up_point'],
                ];
                // 积分使用翻倍
                $usePoint = $this->getUpTotalMaxPointDeduction($params['company_id'], $orderData, $memberPoint, $pointupvaluation);
                $orderData['max_point'] = $usePoint['max_point'];// 本单会员最大可抵扣积分
                $orderData['limit_point'] = $usePoint['limit_point'];// 本单最大可抵扣积分
                $orderData['max_uppoint'] = $usePoint['max_uppoint'];// 本单最大升值积分
            } else {
                $usePoint = $pointRuleService->orderMaxPoint($params['company_id'], $memberPoint, $orderData['total_fee'], $orderData['freight_fee'], $orderData);
                // 使用计算的最大积分数尝试，返回的实际抵扣积分为最大可使用积分
                $tempOrderData = $orderData;
                $tempOrderData['point_use'] = $usePoint['max_point'];
                try {
                    $tryMaxPoint = $this->getPointDeduction($params['company_id'], $tempOrderData, $memberPoint, $upvaluation, $upvaluation['uppoints'] ?? 0, true);
                } catch (\Exception $e) {
                    $tryMaxPoint = [];
                }
                if (!empty($tryMaxPoint)) {
                    $orderData['max_point'] = $tryMaxPoint['real_use_point'];// 本单会员最大可抵扣积分
                    $orderData['max_point_ziti'] = $tryMaxPoint['real_use_point_ziti'];// 本单会员最大可抵扣积分(自提)
                } else {
                    $orderData['max_point'] = $usePoint['max_point'];// 本单会员最大可抵扣积分
                    $orderData['max_point_ziti'] = $usePoint['max_point_ziti'];// 本单会员最大可抵扣积分(自提)
                }
                $orderData['limit_point'] = $usePoint['limit_point'];// 本单最大可抵扣积分
            }

            $orderData['is_open_deduct_point'] = true;
            $orderData['deduct_point_rule'] = [
                'deduct_proportion_limit' => $pointRulesConfig['deduct_proportion_limit'],//每单积分抵扣金额上限
                'deduct_point' => $pointRulesConfig['deduct_point'],//积分抵扣比例
                'full_amount' => $orderData['total_fee'] == $usePoint['max_money'] ? true : false,// 本单订单总额 == 本单会员最大可抵扣总额 时，可以选择全额支付
            ];
        } else {
            $orderData['deduct_point_rule'] = [
                'deduct_proportion_limit' => 0,
                'deduct_point' => 0,
                'full_amount' => false,
            ];
            $orderData['max_point'] = 0;
            $orderData['limit_point'] = 0;
            $orderData['is_open_deduct_point'] = false;
        }
        //是否使用积分抵扣
        if (property_exists($this->orderInterface, 'isSupportPointDiscount') && $this->orderInterface->isSupportPointDiscount) {
            if ('point' == $params['pay_type']) {
                if ($orderData['deduct_point_rule']['full_amount']) {
                    $params['point_use'] = $orderData['point_use'] = $orderData['max_point'];
                } else {
                    throw new PointResourceException("当前{point}不足以支付本次订单费用!");
                }
            }
            if (isset($params['point_use']) && intval($params['point_use']) > 0) {
                if ($orderData['total_fee'] > 0) {
                    if ($upvaluation) {
                        // 处理翻倍抵扣积分
                        $orderData = $this->getUpTotalUsePointDeduction($params['company_id'], $orderData, $memberPoint, $pointupvaluation);
                    } else {
                        $orderData = $this->getPointDeduction($params['company_id'], $orderData, $memberPoint, $upvaluation, $upvaluation['uppoints'] ?? 0);
                    }
                    // if($orderData['total_fee'] == 0){
                    //     $orderData['pay_type'] = 'point';
                    //     $orderData['total_fee'] = $orderData['point_fee'];
                    // }
                    $orderData['point'] = $orderData['real_use_point'];
                } else {
                    throw new PointResourceException("本单不能使用{point}!");
                }
                if ($orderData['point'] <= 0) {
                    throw new PointResourceException("订单使用{point}不能低于一{point}!");
                }
            }
        }

        return $orderData;
    }

    /**
     * 自提订单 核销
     * @param $companyId:企业ID
     * @param $orderId:订单号
     * @param $pickupcode_status:是否开启提货码  开启后，需要验证提货码
     * @param $pickupcode:提货码
     * @param string $operatorType 操作类型
     * @param int $operatorId 操作id
     * @return mixed
     */
    public function orderZitiWriteoff($companyId, $orderId, $pickupcode_status, $pickupcode = '', $operatorType = "", int $operatorId = 0)
    {
        //TODU 验证提货码是否正确
        $detail = $this->getOrderInfo($companyId, $orderId);
        if ($pickupcode_status) {
            $orderInfo = $detail['orderInfo'];
            $phone = $orderInfo['mobile'] ?? '';
            if (!$phone) {
                throw new ResourceException(trans('OrdersBundle/Order.pickup_contact_mobile_not_found'));
            }
            $type = 'pickupcode';
            $check = $this->checkSmsPickupCode($orderId, $phone, $pickupcode, $type);
            if (!$check) {
                throw new ResourceException(trans('OrdersBundle/Order.pickup_code_verification_error'));
            }
        }

        //更新售后时效时间
        $aftersalesTime = intval($this->getOrdersSetting($companyId, 'latest_aftersale_time'));
        $auto_close_aftersales_time = strtotime("+$aftersalesTime day", time());

        $filter = [
            'order_id' => $orderId,
            'company_id' => $companyId,
        ];
        $updateInfo = [
            'ziti_status' => 'DONE',
            'order_status' => 'DONE',
            'delivery_status' => 'DONE',
            'cancel_status' => 'NO_APPLY_CANCEL',
            'delivery_time' => time(),
            'end_time' => time(),
            'order_auto_close_aftersales_time' => $auto_close_aftersales_time,
        ];
        if ($this->orderInterface->orderClass = 'shopadmin') {
            $updateInfo['left_aftersales_num'] = array_sum(array_column($detail['orderInfo']['items'], 'num'));
        }
        $result = $this->update($filter, $updateInfo);
        $this->finishOrderItemsZiti($companyId, $orderId);
        $this->orderFinishBrokerage($companyId, $orderId);
        $this->orderUpdateMemberGrade($companyId, $detail);
        $this->addOrderZitiWriteoffLog((int)$companyId, (string)$orderId, (bool)$pickupcode_status, (string)$pickupcode, (string)($operatorType ?: "system"), $operatorId);

        // 创建银联商务支付，分账订单关联表
        if ($detail['orderInfo']['pay_type'] == 'chinaums') {
            if ($detail['orderInfo']['distributor_id'] > 0) {
                $relDivisionService = new OrdersRelChinaumspayDivisionService();
                $relDivisionService->addRelChinaumsPayDivision((int)$companyId, (string)$orderId);
            }
        }

        // 自提订单赠送大转盘
//        $turntableService = new TurntableService();
//        $turntableService->payGetTurntableTimes($detail['orderInfo']['user_id'], $detail['orderInfo']['company_id'], $detail['orderInfo']['total_fee']);

        //小程序发货信息管理
        event(new WxOrderShippingEvent($companyId, $orderId, 'ziti', 'batch', true));

        //自提订单核销触发 TradeFinishEvent
        if (isset($detail['tradeInfo']['tradeId'])) {
            //触发订单oms更新的事件
            event(new SaasErpUpdateEvent($detail['orderInfo']));
        } else {
            app('log')->debug('订单不存在tradeId: '.$orderId);
        }

        return $result;
    }

    /**
     * 添加订单的自提核销日志
     * @param int $companyId 企业id
     * @param string $orderId 订单id
     * @param bool $pickupCodeStatus 核销码的状态，如果为true则需要有核销码
     * @param string $pickupCode 核销码
     * @param string $operatorType 操作类型
     * @param int $operatorId 操作id
     */
    public function addOrderZitiWriteoffLog(int $companyId, string $orderId, bool $pickupCodeStatus, string $pickupCode, string $operatorType, int $operatorId)
    {
        app('log')->debug(sprintf("addOrderZitiWriteoffLog: %s", json_encode(compact("companyId", "orderId", "pickupCode", "pickupCodeStatus", "operatorType", "operatorId"), JSON_UNESCAPED_UNICODE)));

        $detail = sprintf("订单号: %s, 已被核销. ", $orderId);
        if ($pickupCodeStatus) {
            $detail .= sprintf("核销号: %s", $pickupCode);
        }
        event(new OrderProcessLogEvent([
            'order_id' => $orderId,
            'company_id' => $companyId,
            'operator_type' => $operatorType ?: "system",
            'operator_id' => $operatorId,
            'remarks' => '订单核销',
            'detail' => $detail,
            'params' => [
                'order_id' => $orderId,
                'company_id' => $companyId,
                'pickupcode_status' => $pickupCodeStatus,
                'pickupcode' => $pickupCode,
                'operator_type' => $operatorType,
                'operator_id' => $operatorId,
            ],
        ]));
    }

    /**
    * 发送提货码
    * @param $companyId:企业ID
    * @param $orderId:订单号
    */
    public function orderPickupCode($companyId, $orderId)
    {
        // 获取提货码的状态  是否开启
        $settingService = new SettingService();
        $pickupCodeSetting = $settingService->presalePickupcodeGet($companyId);
        if (!$pickupCodeSetting['pickupcode_status']) {
            throw new ResourceException(trans('OrdersBundle/Order.pickup_code_not_enabled'));
        }
        $detail = $this->getOrderInfo($companyId, $orderId);
        $orderInfo = $detail['orderInfo'];
        if ($orderInfo['order_status'] != 'PAYED' || $orderInfo['ziti_status'] != 'PENDING') {
            throw new ResourceException(trans('OrdersBundle/Order.order_not_in_pending_verification_status'));
        }
        $phone = $orderInfo['mobile'] ?? '';
        if (!$phone) {
            throw new ResourceException('未查询到提货人联系手机！');
        }
        $type = 'pickupcode';
        return $this->generateSmsPickupCode($companyId, $orderId, $phone, $type);
    }

    //生成短信提货码
    public function generateSmsPickupCode($companyId, $orderId, $phone, $type, $send = true)
    {
        $key = $this->generateReidsKey($orderId.'|'.$phone, $type);
        $time = intval(app('redis')->connection('companys')->ttl($key));
        if ($time - 240 > 0) {
            $time = $time - 240;
            throw new ResourceException(trans('OrdersBundle/Order.retry_send_code_after_seconds', ['{0}' => $time]));
        }
        $vcode = (string)rand(100000, 999999);
        app('log')->info("订单发送提货码:" . json_encode(['order_id' => $orderId,'phone' => $phone, 'vcode' => $vcode]));
        //保存验证码
        $expire = 1800;
        $this->redisSmsPickupCode($key, $vcode, $expire);
        if ($send) {
            //发送短信
            $result = $this->sendSmsVcode($companyId, $orderId, $phone, $vcode);
            app('log')->info('订单发送提货码 发送短信 order_id:'.$orderId.',phone:'.$phone.', result=====>'.json_encode($result));
            return true;
        } else {
            return $vcode;
        }
    }

    // 获取短信提货码
    public function showSmsPickupCode($companyId, $orderId)
    {
        $settingService = new SettingService();
        $pickupCodeSetting = $settingService->presalePickupcodeGet($companyId);
        if (!$pickupCodeSetting['pickupcode_status']) {
            return null;
        }
        $detail = $this->getOrderInfo($companyId, $orderId);
        $orderInfo = $detail['orderInfo'];
        $phone = $orderInfo['mobile'] ?? '';
        if (!$phone) {
            return null;
        }
        $type = 'pickupcode';

        $key = $this->generateReidsKey($orderId.'|'.$phone, $type);
        $pickupcode = $this->redisFetch($key);

        if (!$pickupcode) {
            $pickupcode = $this->generateSmsPickupCode($companyId, $orderId, $phone, $type, false);
        }
        return $pickupcode;
    }

    //生成验证码的redis key
    private function generateReidsKey($token, $type = "pickupcode")
    {
        return "admin-" . $type . ":" . $token;
    }

    //redis读取
    private function redisFetch($key)
    {
        app('log')->info("订单提货码获取redis:" . json_encode(['key' => $key]));
        return app('redis')->connection('companys')->get($key);
    }

    //redis存储
    private function redisSmsPickupCode($key, $value, $expire = 300)
    {
        app('log')->info("订单发送提货码 redis:" . json_encode(['key' => $key, 'value' => $value, 'expire' => $expire]));
        app('redis')->connection('companys')->set($key, $value);
        app('redis')->connection('companys')->expire($key, $expire);
        return true;
    }

    //短信验证码的发送动作
    private function sendSmsVcode($companyId, $orderId, $phone, $code)
    {
        $data = ['order_id' => $orderId, 'pickup_code' => $code];
        $smsManagerService = new SmsManagerService($companyId);
        $smsManagerService->send($phone, $companyId, 'order_pickup', $data);
        return true;
    }

    //验证短信验证码
    public function checkSmsPickupCode($orderId, $phone, $vcode, $type)
    {
        if (empty($orderId)) {
            throw new ResourceException(trans('OrdersBundle/Order.order_number_missing'));
        }
        if (empty($phone)) {
            throw new ResourceException(trans('OrdersBundle/Order.mobile_number_missing'));
        }
        $key = $this->generateReidsKey($orderId.'|'.$phone, $type);
        $pickupcode = $this->redisFetch($key);
        if ($pickupcode == $vcode) {
            app('redis')->connection('companys')->del($key);
            return true;
        }
        return false;
    }

    /**
     * 无门店，获取购物车中的商品数据
     * @param $params
     * @return bool
     */
    public function getCartItemsByNostores($params)
    {
        $params['pay_type'] = 'wxpay';
        if (property_exists($this->orderInterface, 'isSupportCart') && $this->orderInterface->isSupportCart) {
            if ($params['order_type'] == 'normal') {
                $params = $this->orderInterface->checkoutCartItemsNostores($params);
            } else {
                $params = $this->orderInterface->checkoutCartItems($params);
            }
        }
        if (!$params) {
            return false;
        }
        if (!$result = $this->__checkNostores($params)) {
            return false;
        }
        return $params['items'];
    }

    /**
     * 无门店，检查购物车数据
     * @param $params
     * @return bool
     */
    protected function __checkNostores($params)
    {
        // 各个类型订单自己检查
        if (method_exists($this->orderInterface, 'check')) {
            $this->orderInterface->check($params);
        }

        // 检查购买商品是否有效
        $result = $this->__checkItemValidNostores($params, $msg);
        if (!$result) {
            return false;
        }
        // 校验当前购买门店是否有效 目前只能一次结算同门店商品
        if (property_exists($this->orderInterface, 'isCheckShopValid') && $this->orderInterface->isCheckShopValid) {
            if (isset($params['shop_id']) && $params['shop_id']) {
                $this->checkShopValid($params['shop_id']);
            }
        }

        // 校验店铺，并且校验当前购买的商品是否关联当前店铺
        if (property_exists($this->orderInterface, 'isCheckDistributorValid') && $this->orderInterface->isCheckDistributorValid) {
            if (isset($params['distributor_id']) && $params['distributor_id']) {
                $result = $this->__checkDistributorValidNostores($params, $msg);
                if (!$result) {
                    app('log')->info('check distributor distributor_id:' . $params['distributor_id'] . ' msg===>' . $msg);
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * 无门店，校验购买的商品是否有效
     * @param $params
     * @param $msg
     * @return bool
     */
    public function __checkItemValidNostores($params, &$msg)
    {
        $itemIds = $this->formatOrderItemIds($params);
        // 如果不需要检查商品则返回空
        // 商品ID是否存储，在检查参数必填的时候已经过滤，所以此处不需要考虑没有商品id是否合法
        if (!$itemIds) {
            return true;
        }

        $itemService = new ItemsService();

        $filter['item_id|in'] = $itemIds;
        $filter['company_id'] = $params['company_id'];
        $itemList = $itemService->getSkuItemsList($filter, 1, 100);

        // 未查询到购买商品
        if ($itemList['total_count'] == 0) {
            $msg = '购买商品无效，请重新结算';
            return false;
            // throw new ResourceException('购买商品无效，请重新结算');
        }

        // 查询出来的商品和购买提交的商品数量不一致
        if ($itemList['total_count'] != count(array_unique($itemIds))) {
            $msg = '部分商品无效，请重新结算';
            return false;
            // throw new ResourceException('部分商品无效，请重新结算');
        }

        $params['order_source'] = isset($params['order_source']) ? $params['order_source'] : 'member';
        $allPoint = 0;
        // 校验商品是否可销售
        foreach ($itemList['list'] as $itemInfo) {
            switch ($params['order_source']) {
                case 'shop': // 商品购买为代客下单来源
                    if (!in_array($itemInfo['approve_status'], ['onsale', 'offline_sale'])) {
                        return false;
                        // throw new ResourceException("商品{$itemInfo['itemName']}已下架");
                    }
                    break;
                case 'member': // 如果订单来源为微信小程序，用户自己购买
                    if (!in_array($itemInfo['approve_status'], ['onsale', 'offline_sale'])) {
                        return false;
                        // throw new ResourceException("商品{$itemInfo['itemName']}已下架");
                    }
            }
        }
        if (isset($params['items'])) {
            $itemNewList = array_bind_key($itemList['list'], 'itemId');
            $orderItemList = [];
            foreach ($params['items'] as $v) {
                $v['activity_id'] = $v['activity_id'] ?? 0;
                $v['activity_type'] = $v['activity_type'] ?? 'normal';
                $orderItemList[] = array_merge($itemNewList[$v['item_id']], $v);
                if (isset($v['items_id']) && is_array($v['items_id'])) {
                    foreach ($v['items_id'] as $v1) {
                        $v['activity_id'] = $v['activity_id'] ?? 0;
                        $v['activity_type'] = $v['activity_type'] ?? 'normal';
                        $orderItemList[] = array_merge($itemNewList[$v1], $v);
                    }
                }
            }

            $this->orderItemListNostores = $orderItemList;
        } else {
            $this->orderItemListNostores = array_column($itemList['list'], null, 'itemId');
        }

        return true;
    }

    /**
     * 无门店，校验店铺有效性 库存不足等
     *
     * 如果创建订单提交了 distributor_id 参数则进行校验
     * 如果没有提交则不校验
     *
     * 如果 distributor_id 是必填参数，请在 checkCreateOrderNeedParams 方法中进行必填校验
     */
    public function __checkDistributorValidNostores($params, &$msg)
    {
        $this->distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
        $createDistributorInfo = $this->distributorRepository->getInfoById($params['distributor_id']);
        if (!$createDistributorInfo) {
            $msg = '当前店铺不存在';
            return false;
            // throw new ResourceException('当前店铺不存在');
        }

        if ($createDistributorInfo['is_valid'] != 'true') {
            $msg = '当前店铺已失效';
            return false;
            // throw new ResourceException('当前店铺已失效');
        }

        if (!$createDistributorInfo['is_ziti'] && $params['receipt_type'] == 'ziti') {
            $msg = '当前店铺不支持自提';
            return false;
            // throw new ResourceException('当前店铺不支持自提');
        }

        if ($createDistributorInfo['distributor_self'] == 1) {
            return true;
        }

        $distributorItemsService = new DistributorItemsService();
        $itemIds = $this->formatOrderItemIds($params);
        $itemNums = array_column($params['items'], null, 'item_id');
        foreach ($itemIds as $itemId) {
            $distributorItem = $distributorItemsService->getValidDistributorItemSkuInfo($params['company_id'], $itemId, $params['distributor_id']);
            if (!$distributorItem) {
                $msg = '购买商品已失效，请重新结算';
                return false;
                // throw new ResourceException("购买商品已失效，请重新结算");
            }

            if (isset($distributorItem['is_total_store'])) {
                foreach ($this->orderItemListNostores as &$v) {
                    if ($itemId == $v['item_id']) {
                        $v['is_total_store'] = $distributorItem['is_total_store'];
                        if ($distributorItem['is_total_store'] && isset($distributorItem['logistics_store'])) {
                            $store = $distributorItem['logistics_store'];
                        } else {
                            $store = $distributorItem['store'];
                        }

                        $num = $itemNums[$itemId]['num'];
                        if ($store < $num) {
                            $msg = 'distributor_id:' . $params['distributor_id'] . ',item_id:' . $itemId . ' 库存不足';
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * 去营销中心，获取会员绑定导购数据
     * @param  [type] $companyId [description]
     * @param  [type] $userId    [description]
     * @return [type]            [description]
     */
    public function getBindSalesmanData($companyId, $userId)
    {
        $membersAssociationsRepository = app('registry')->getManager('default')->getRepository(MembersAssociations::class);
        $membersAssociations = $membersAssociationsRepository->get(['user_id' => $userId, 'company_id' => $companyId]);
        $unionid = $membersAssociations['unionid'] ?? '';
        app('log')->info(':debug:getBindSalesmanData:'.__LINE__.' unionid===>' . $unionid);
        if (!$unionid) {
            return false;
        }
        $request = new MarketingCenterRequest();
        $result = $request->call($companyId, 'basics.member.getBindSalesperson', ['unionid' => $unionid]);
        // [2025-08-01 17:09:24] production.DEBUG: MarketingCenter:result===>{"status":"success","code":"200","message":"success","data":{"salesperson_id":8,"work_userid":"18964058319","member_id":"8","external_member_type":1,"external_userid":"wmZ_c_cQAAZ8RAXTS1EZbpCv0TASdw1A","suite_external_userid":"","unionid":"oFvDQ69HXH8Wcbw19tE9h0RM4MvY","member_status":1,"bind_status":1,"bind_time":1753949254,"bind_cancel_time":1754063999,"friend_status":1,"become_friend_time":1753949070,"employee_number":"18964058319","store_bn":"09876555","store_name":"桂林路店"}}

        $employee_number = $result['data']['employee_number'] ?? '';
        if (!$employee_number) {
            return false;
        }
        // 获取门店信息
        $distributorService = new DistributorService();
        $filter = [
            'company_id' => $companyId,
            'shop_code' => $result['data']['store_bn'],
        ];  
        app('log')->info(':debug:getBindSalesmanData:'.__LINE__.' filter===>' . json_encode($filter));
        $distributorInfo = $distributorService->getInfo($filter);
        if (!$distributorInfo) {
            return false;
        }

        $salespersonService = new SalespersonService();
        $filter = [
            'company_id' => $companyId,
            'is_valid' => 'true',
            'salesperson_type' => 'shopping_guide',
            'work_userid' => $employee_number,
        ];
        $salesperson = $salespersonService->getSalespersonDetail($filter, true);
        app('log')->info(':debug:getBindSalesmanData:'.__LINE__.' salesperson===>' . json_encode($salesperson));
        $data = [
            'bind_salesman_id' => $salesperson['salesperson_id'] ?? 0,
            'bind_salesman_distributor_id' => $distributorInfo['distributorList'][0]['distributor_id'] ?? 0,
        ];
        app('log')->info(':debug:getBindSalesmanData:'.__LINE__.' data===>' . json_encode($data));
        return $data;

        // $data = [
        //     'bind_salesman_id' => $salesperson['salesperson_id'] ?? 0,
        //     'bind_salesman_distributor_id' => $salesperson['distributorList'][0]['distributor_id'] ?? 0,
        // ];
        // return $data;
    }

    public function updatePayType($orderId, $payType, $payChannel = null) {
        if (method_exists($this->orderInterface, 'updatePayType')) {
            return $this->orderInterface->updatePayType($orderId, $payType, $payChannel);
        }
        return true;
    }

    public function getDeliveryTimeList($hour,$freight_time = 2)
    {
        $interval = 30; // 半小时间隔
        $hour = ($hour != '' && $hour!= '-') ? $hour : "08:20-21:00";
        list($startHour,$endHour) = explode('-',$hour);
        $timeRanges = [
            'today'=>[
                'name'=>'今天',
                'date'=>date('Y-m-d'),
                'list'=>[]
            ],
            'tomorrow'=>[
                'name'=>'明天 周',
                'list'=>[]
            ],
        ];

        $time = strtotime(date("H:i", time()));
        $currentTime = strtotime($startHour); // 转换开始时间为时间戳
        $endTime = strtotime($endHour); // 转换结束时间为时间戳
        $today = \Carbon\Carbon::tomorrow();
        $dayOfWeek = $today->dayOfWeek;
        $chineseDays = ['日', '一', '二', '三', '四', '五', '六', '日'];
        $chineseDayOfWeek = $chineseDays[$dayOfWeek];
        $timeRanges['tomorrow']['name'] .= $chineseDayOfWeek;
        $timeRanges['tomorrow']['date'] = $today->toDateString();
        $timeRanges['today']['list'][] =  date("H:i", strtotime("+{$freight_time} hour"));
        while ($currentTime < $endTime) {
            $nextTime = $currentTime + ($interval * 60) ; // 计算下一个时间点
            // 确保下次循环不会超过结束时间
            if ($nextTime >= $endTime) {
                $nextTime = $endTime;
            }
            if($currentTime > $time){
                $tempCurrent = $currentTime + ($freight_time * 60 * 60);
                $tempNext = $nextTime + ($freight_time * 60 * 60);
                if($tempNext >= $endTime){
                    $tempNext = $endTime;
                }
                if($tempCurrent < $endTime){
                    $timeRanges['today']['list'][] = date("H:i", $tempCurrent) . '-' . date("H:i", $tempNext);
                }

            }
            // 生成当前时间和下一个时间点之间的时间区间字符串，并添加到数组中
            $timeRanges['tomorrow']['list'][] = date("H:i", $currentTime) . '-' . date("H:i", $nextTime);
            $currentTime = $nextTime; // 更新当前时间为下一个时间点
        }

        return $timeRanges;
    }

    public function getSalesmanOfflinePayData($params, $result)
    {
        //获取店铺信息
        $distributorInfo = [];
        if ($result['distributor_id'] ?? '') {
            $distributorService = new DistributorService();
            $filter = [
                'company_id' => $params['company_id'],
                'distributor_id' => $result['distributor_id'],
            ];
            $distributorInfo = $distributorService->getInfo($filter);
        }
        $memberService = new MemberService();
        $filter = [
            'company_id' => $params['company_id'],
            'user_id' => $params['buy_user_id'],
        ];
        $memberInfo = $memberService->getMemberInfo($filter, false);
        $data = [
            'company_id' => $params['company_id'],
            'user_id' => $params['buy_user_id'] ?? 0,
            'total_fee' => $result['total_fee'],
            'detail' => $result['title'],
            'order_id' => $result['order_id'],
            'body' => $result['title'],
            'open_id' => '',
            'wxa_appid' => $memberInfo['wxapp_appid'] ?? '',
            'mobile' => $memberInfo['mobile'],
            'pay_type' => $params['pay_type'],
            'pay_fee' => 'point' == $params['pay_type'] ? $result['point'] : $result['total_fee'],
            'discount_fee' => $result['discount_fee'],
            'discount_info' => $result['discount_info'],
            'fee_rate' => $result['fee_rate'],
            'fee_type' => $result['fee_type'],
            'fee_symbol' => $result['fee_symbol'],
            'shop_id' => $result['shop_id'] ?? 0,
            'distributor_id' => isset($result['distributor_id']) ? $result['distributor_id'] : '',
            'trade_source_type' => $params['order_type'],
            'return_url' => $params['return_url'] ?? '',
            'distributor_info' => $distributorInfo,
        ];
        return $data;
    }

    /**
     * Dynamically call the KaquanService instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->orderInterface->$method(...$parameters);
    }
}
