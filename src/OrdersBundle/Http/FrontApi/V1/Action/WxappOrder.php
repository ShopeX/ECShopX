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

namespace OrdersBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use DistributionBundle\Services\DistributorWhiteListService;
use EspierBundle\Services\OfflineBankAccountService;
use DepositBundle\Services\DepositTrade;
use OrdersBundle\Entities\Trade;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Services\OrderDeliveryService;
use OrdersBundle\Services\DeliveryProcessLogServices;
use OrdersBundle\Services\OrderEpidemicService;
use OrdersBundle\Services\OrderInvoiceService;
use OrdersBundle\Services\Orders\ExcardNormalOrderService;
use OrdersBundle\Services\TradeService;
use OrdersBundle\Traits\GetPaymentServiceTrait;
use PromotionsBundle\Services\PromotionGroupsTeamService;
use SuperAdminBundle\Services\LogisticsService;
use SupplierBundle\Services\SupplierOrderService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use KaquanBundle\Services\UserDiscountService;
use OrdersBundle\Services\RightsService;
use OrdersBundle\Services\Rights\TimesCardService;
use OrdersBundle\Services\OrderAssociationService;
use PaymentBundle\Services\Payments\WechatPayService;
use PaymentBundle\Services\Payments\OfflinePayService;
use OrdersBundle\Services\LogisticTracker;
use OrdersBundle\Services\OfflinePaymentService;

use AftersalesBundle\Services\AftersalesService;

use DistributionBundle\Services\DistributorService;

use CompanysBundle\Services\SettingService;

use OrdersBundle\Traits\GetOrderServiceTrait;
use CompanysBundle\Traits\GetDefaultCur;
use SalespersonBundle\Services\SalespersonService;
use OrdersBundle\Traits\OrderSettingTrait;
use OrdersBundle\Traits\GetOrderSourceFrom;

class WxappOrder extends Controller
{
    use GetOrderServiceTrait;
    use GetPaymentServiceTrait;
    use GetDefaultCur;
    use OrderSettingTrait;
    use GetOrderSourceFrom;

    /**
     * @SWG\Post(
     *     path="/wxapp/order/jspayconfig",
     *     summary="获取JsPayConfig",
     *     tags={"订单"},
     *     description="获取JsPayConfig",
     *     operationId="getJsPayConfig",
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getJsPayConfig(Request $request)
    {
        $authInfo = $request->get('auth');

        $companyId = $authInfo['company_id'];
        $url = $request->get('url');

        $service = new WechatPayService();
        $result = $service->getJsConfig($companyId, $url);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/order",
     *     summary="创建订单",
     *     tags={"订单"},
     *     description="创建订单",
     *     operationId="createOrder",
     *     @SWG\Parameter( name="shop_id", in="query", description="门店ID", required=false, type="integer"),
     *     @SWG\Parameter( name="item_id", in="query", description="交易商品ID", required=false, type="integer"),
     *     @SWG\Parameter( name="item_num", in="query", description="交易商品数量", required=false, type="number"),
     *     @SWG\Parameter( name="items[][item_id]", in="query", description="交易商品ID", required=false, type="integer"),
     *     @SWG\Parameter( name="items[][num]", in="query", description="交易商品数量", required=false, type="number"),
     *     @SWG\Parameter( name="bargain_id", in="query", description="砍价活动id", required=false, type="integer"),
     *     @SWG\Parameter( name="source_id", in="query", description="订单来源ID", required=false, type="integer"),
     *     @SWG\Parameter( name="monitor_id", in="query", description="监控页面ID", required=false, type="integer"),
     *     @SWG\Parameter( name="salesman_id", in="query", description="导购ID", required=false, type="integer"),
     *     @SWG\Parameter( name="receipt_type", in="query", description="收货方式：ziti 自提 logistics 快递", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_name", in="query", description="收货人姓名", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_mobile", in="query", description="收货人手机号", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_zip", in="query", description="收货人邮编", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_state", in="query", description="收货人所在省份", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_city", in="query", description="收货人所在城市", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_district", in="query", description="收货人所在地区", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_address", in="query", description="收货人详细地址", required=false, type="string"),
     *     @SWG\Parameter( name="epidemic_register_info", in="query", description="疫情防控登记 json{name,cert_id,mobile,temperature,job,symptom,symptom_des,is_risk_area}", required=false, type="string"),
     *     @SWG\Parameter( name="cart_type", in="query", description="购物车类型 cart 购物车 fastbuy 立即购买 offline 离线购物车", required=true, type="string"),
     *     @SWG\Parameter( name="order_type", in="query", description="订单类型，service服务业订单 bargain 砍价订单 normal 实物订单 normal_community社区团购订单", required=true, type="string"),
     *     @SWG\Parameter( name="pay_type", in="query", description="支付类型 wxpay 微信支付 point 积分支付 deposit 储值支付", required=false, type="string"),
     *     @SWG\Parameter( name="invoice_content", in="query", description="开票信息", required=false, type="string"),
     *     @SWG\Parameter( name="invoice_type", in="query", description="开票类型", required=false, type="string"),
     *     @SWG\Parameter( name="coupon_discount", in="query", description="优惠券优惠码", required=false, type="string"),
     *     @SWG\Parameter( name="not_use_coupon", in="query", description="不使用优惠券", required=false, type="boolean"),
     *     @SWG\Parameter( name="iscrossborder", in="query", description="是否海外购", required=false, type="boolean"),
     *     @SWG\Parameter( name="isShopScreen", in="query", description="是否大屏", required=false, type="boolean"),
     *     @SWG\Parameter( name="isNostores", in="query", description="是否支持无门店下单门店自提", required=false, type="boolean"),
     *     @SWG\Parameter( name="point_use", in="query", description="是否使用积分", required=false, type="boolean"),
     *     @SWG\Parameter( name="pack", in="query", description="是否需要包装", required=false, type="boolean"),
     *     @SWG\Parameter( name="remark", in="query", description="订单备注", required=false, type="string"),
     *     @SWG\Parameter( name="community_activity_id", in="query", description="社区团购活动ID，order_type=normal_community时必填", required=false, type="string"),
     *     @SWG\Parameter( name="community_ziti_id", in="query", description="社区团购自提点ID，order_type=normal_community时必填", required=false, type="string"),
     *     @SWG\Parameter( name="community_extra_data", in="query", description="社区团购扩展字段，json键值对", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="appId", type="string", description="应用ID"),
     *                 @SWG\Property(property="timeStamp", type="string", description="时间戳"),
     *                 @SWG\Property(property="nonceStr", type="string", description="随机字符串"),
     *                 @SWG\Property(property="package", type="string", description="订单详情扩展字符串"),
     *                 @SWG\Property(property="signType", type="string", description="签名方式"),
     *                 @SWG\Property(property="paySign", type="string", description="签名"),
     *                 @SWG\Property(property="team_id", type="string", description=""),
     *                 @SWG\Property(
     *                     property="trade_info",
     *                     type="object",
     *                         @SWG\Property(property="order_id", type="string", description="订单号"),
     *                         @SWG\Property(property="trade_id", type="string", description="交易单号"),
     *                 ),
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function createOrder(Request $request)
    {
        $authInfo = $request->get('auth');
        $params = $request->all();
        $params['company_id'] = $authInfo['company_id'];
        $params['mobile'] = $authInfo['mobile'];
        $params['nickname'] = $authInfo['nickname'] ?? '';
        $params['user_id'] = $authInfo['user_id'];
        $params['authorizer_appid'] = $authInfo['woa_appid'] ?? '';
        $params['wxa_appid'] = $authInfo['wxapp_appid'] ?? '';
        $params['order_type'] = ($params['order_type'] ?? '') ? $params['order_type'] : 'service';
        $params['pay_type'] = ($params['pay_type'] ?? '') ? $params['pay_type'] : 'wxpay';
        $params['point_use'] = $request->input('point_use', 0);
        $params['iscrossborder'] = $request->input('iscrossborder', 0);
        $params['isShopScreen'] = $request->input('isShopScreen', 0);
        $params['isNostores'] = $request->input('isNostores', 0);
        $params['user_device'] = $request->get('user_device');
        $params['receipt_type'] = $request->input('receipt_type', 'logistics');
        $params['source_from'] = $this->getOrderSourceFrom($request);
        $realIp = explode(',', $request->server('HTTP_X_FORWARDED_FOR'))[0];
        $params['client_ip'] = $realIp ?: $request->getClientIp();
        if ($params['epidemic_register_info'] ?? []) {
            $epidemicRegisterInfo = json_decode($params['epidemic_register_info'], true);
            $orderEpidemicService = new OrderEpidemicService();
            $orderEpidemicService->validator($epidemicRegisterInfo);
        }
        if ($params['work_userid'] ?? '') {
            $salesPersonService = new SalespersonService();
            $salespersonInfo = $salesPersonService->salesperson->getInfo(['work_userid' => $params['work_userid'], 'company_id' => $authInfo['company_id']]);
            $params['salesman_id'] = $salespersonInfo['salesperson_id'] ?? 0;
        }
        $orderService = $this->getOrderService($params['order_type']);
        $result = $orderService->create($params);
        if ($params['epidemic_register_info'] ?? []) {
            $orderEpidemicService->epidemicRegisterCreate($epidemicRegisterInfo, $result);
        }

        if ($params['order_type'] == 'normal_drug') {
            return $this->response->array($result);
        } else {
            //获取店铺信息
            $distributorInfo = [];
            $distributorService = new DistributorService();
            if ($result['distributor_id'] ?? '') {
                $filter = [
                    'company_id' => $params['company_id'],
                    'distributor_id' => $result['distributor_id'],
                ];
                $distributorInfo = $distributorService->getInfo($filter);
            }
            $data = [
                'company_id' => $authInfo['company_id'],
                'user_id' => $authInfo['user_id'],
                'total_fee' => $result['total_fee'],
                'detail' => $result['title'],
                'order_id' => $result['order_id'],
                'body' => $result['title'],
                'open_id' => $authInfo['open_id'] ?? '',
                'wxa_appid' => $authInfo['wxapp_appid'] ?? '',
                'mobile' => $authInfo['mobile'],
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
                'point' => $result['point'] ?? 0,
                'is_create_prescription_order' => ($result['prescription_status'] ?? 0) > 0 ? 1 : 0,
                'client_ip' => $params['client_ip'] ?? 0,
            ];
            if ('deposit' == $params['pay_type']) {
                $data['member_card_code'] = $authInfo['user_card_code'];
            }
            if ('alipaymini' == $params['pay_type']) {
                $data['alipay_user_id'] = $authInfo['alipay_user_id'];
            }
            $authorizerAppId = $authInfo['woa_appid'] ?? '';
            $wxaAppId = $authInfo['wxapp_appid'] ?? '';
            // 处理积分支付的部分
//            if ($result['order_class'] == 'pointsmall' && $params['pay_type'] != 'point') {
//                $_data = $data;
//                $_data['total_fee'] = 0;
//                $_data['pay_fee'] = $result['point'];
//                $_data['pay_type'] = 'point';
//                $service = $this->getPaymentService('point');
//                $pointPayResult = $service->doPayment($authorizerAppId, $wxaAppId, $_data, false);
//                // 查询一次订单状态
//                $order = $orderService->getOrderInfo($authInfo['company_id'], $result['order_id'], false);
//                if ($order['orderInfo']['pay_status'] == 'PAYED') {
//                    return $this->response->array($pointPayResult);
//                }
//            }
            if ($params['pay_type'] == 'adapay') {
                if (!isset($params['pay_channel']) || !$params['pay_channel']) {
                    throw new BadRequestHttpException('adapay支付方式  pay_channel必传');
                }
                $data['pay_channel'] = $params['pay_channel'];
            }
            $service = $this->getPaymentService($params['pay_type'], $data['distributor_id']);
            $payResult = $service->doPayment($authorizerAppId, $wxaAppId, $data, false);
            $payResult['team_id'] = isset($result['team_id']) ? $result['team_id'] : null;
            $payResult['order_created'] = $result['create_time'];
        }

        return $this->response->array($payResult);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/epidemic/info",
     *     summary="疫情登记信息",
     *     tags={"订单"},
     *     description="疫情登记信息",
     *     operationId="epidemicRegisterInfo",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *    @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="1", description="id"),
     *                          @SWG\Property( property="order_id", type="string", example="1", description="订单ID"),
     *                          @SWG\Property( property="user_id", type="string", example="1", description="用户ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司ID"),
     *                          @SWG\Property( property="distributor_id", type="string", example="1", description="店铺ID"),
     *                          @SWG\Property( property="name", type="string", example="1", description="登记姓名"),
     *                          @SWG\Property( property="mobile", type="string", example="1", description="登记手机号"),
     *                          @SWG\Property( property="cert_id", type="string", example="1", description="身份证号"),
     *                          @SWG\Property( property="temperature", type="string", example="1", description="温度"),
     *                          @SWG\Property( property="job", type="string", example="1", description="职业"),
     *                          @SWG\Property( property="symptom", type="string", example="1", description="症状"),
     *                          @SWG\Property( property="symptom_des", type="string", example="1", description="症状描述"),
     *                          @SWG\Property( property="is_risk_area", type="string", example="1", description="是否去过中高风险地区"),
     *                          @SWG\Property( property="order_time", type="string", example="1", description="下单时间"),
     *                          @SWG\Property( property="created", type="string", example="1", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="null", description="修改时间"),
     *                          @SWG\Property( property="distributor_name", type="string", example="普天信息产业园测试1", description="店铺名称"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     * )
     */
    public function epidemicRegisterInfo(Request $request)
    {
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];
        $filter['is_use'] = 1;
        $orderEpidemicService = new OrderEpidemicService();
        $result = $orderEpidemicService->lists($filter, '*', 1, 5, ['id' => 'DESC']);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/epidemic/mixed/cat",
     *     summary="疫情登记所需信息",
     *     tags={"订单"},
     *     description="疫情登记所需信息",
     *     operationId="epidemicRegisterMixedCats",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="temperature", type="array", @SWG\Items( type="string",description=""),),
     *                  @SWG\Property( property="job", type="array", @SWG\Items( type="string",description=""),),
     *          ),
     *     )),
     * )
     */
    public function epidemicRegisterMixedCats()
    {

        for ($i = 36.1; $i < 38; $i = $i + 0.1) {
            $temperature[] = bcdiv($i, 1, 1).'℃';
        }
        array_unshift($temperature, '36.0℃及以下');
        array_push($temperature, '38.0℃及以上');

        $job = [
            '非高危职业',
            '进口冷链',
            '口岸检疫',
            '公共交通',
            '生鲜市场',
            '船舶引航',
            '物流运输',
            '航空空勤',
            '家政护理',
            '保安保洁',
            '旅馆酒店',
            '维修装修',
            '个体经营',
            '出国学习工作',
            '教育培训',
            '环卫绿化',
            '养老院',
            '儿童福利院',
            '救助管理机构',
            '集中隔离点和居家隔离服务人员',
        ];
        $result = [
            'temperature' => $temperature,
            'job' => $job,
        ];
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/epidemic/info/del/{id}",
     *     summary="删除疫情登记信息",
     *     tags={"订单"},
     *     description="删除疫情登记信息",
     *     operationId="delEpidemicRegister",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="path", type="string", required=true, name="id", description="id" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     * )
     */
    public function delEpidemicRegister($id)
    {
        $orderEpidemicService = new OrderEpidemicService();
        $result = $orderEpidemicService->updateOneBy(['id' => $id], ['is_use' => 0]);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/order_new",
     *     summary="创建订单",
     *     tags={"订单"},
     *     description="创建订单",
     *     operationId="createNewOrder",
     *     @SWG\Parameter( name="shop_id", in="query", description="门店ID", required=false, type="integer"),
     *     @SWG\Parameter( name="item_id", in="query", description="交易商品ID", required=false, type="integer"),
     *     @SWG\Parameter( name="item_num", in="query", description="交易商品数量", required=false, type="number"),
     *     @SWG\Parameter( name="items[][item_id]", in="query", description="交易商品ID", required=false, type="integer"),
     *     @SWG\Parameter( name="items[][num]", in="query", description="交易商品数量", required=false, type="number"),
     *     @SWG\Parameter( name="bargain_id", in="query", description="砍价活动id", required=false, type="integer"),
     *     @SWG\Parameter( name="source_id", in="query", description="订单来源ID", required=false, type="integer"),
     *     @SWG\Parameter( name="monitor_id", in="query", description="监控页面ID", required=false, type="integer"),
     *     @SWG\Parameter( name="salesman_id", in="query", description="导购ID", required=false, type="integer"),
     *     @SWG\Parameter( name="receipt_type", in="query", description="收货方式：ziti 自提 logistics 快递", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_name", in="query", description="收货人姓名", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_mobile", in="query", description="收货人手机号", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_zip", in="query", description="收货人邮编", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_state", in="query", description="收货人所在省份", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_city", in="query", description="收货人所在城市", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_district", in="query", description="收货人所在地区", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_address", in="query", description="收货人详细地址", required=false, type="string"),
     *     @SWG\Parameter( name="cart_type", in="query", description="购物车类型 cart 购物车 fastbuy 立即购买 offline 离线购物车", required=true, type="string"),
     *     @SWG\Parameter( name="order_type", in="query", description="订单类型，service服务业订单 bargain 砍价订单 normal 实物订单 normal_community社区团购订单", required=true, type="string"),
     *     @SWG\Parameter( name="pay_type", in="query", description="支付类型 wxpay 微信支付 point 积分支付 deposit 储值支付", required=false, type="string"),
     *     @SWG\Parameter( name="invoice_content", in="query", description="开票信息", required=false, type="string"),
     *     @SWG\Parameter( name="invoice_type", in="query", description="开票类型", required=false, type="string"),
     *     @SWG\Parameter( name="coupon_discount", in="query", description="优惠券优惠码", required=false, type="string"),
     *     @SWG\Parameter( name="not_use_coupon", in="query", description="不使用优惠券", required=false, type="boolean"),
     *     @SWG\Parameter( name="iscrossborder", in="query", description="是否海外购", required=false, type="boolean"),
     *     @SWG\Parameter( name="isShopScreen", in="query", description="是否大屏", required=false, type="boolean"),
     *     @SWG\Parameter( name="isNostores", in="query", description="是否支持无门店下单门店自提", required=false, type="boolean"),
     *     @SWG\Parameter( name="point_use", in="query", description="是否使用积分", required=false, type="boolean"),
     *     @SWG\Parameter( name="pack", in="query", description="是否需要包装", required=false, type="boolean"),
     *     @SWG\Parameter( name="remark", in="query", description="订单备注", required=false, type="string"),
     *     @SWG\Parameter( name="community_activity_id", in="query", description="社区团购活动ID，order_type=normal_community时必填", required=false, type="string"),
     *     @SWG\Parameter( name="community_ziti_id", in="query", description="社区团购自提点ID，order_type=normal_community时必填", required=false, type="string"),
     *     @SWG\Parameter( name="community_extra_data", in="query", description="社区团购扩展字段，json键值对", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="order_id", type="string", description="订单号"),
     *                 @SWG\Property(property="pay_type", type="string", description="支付方式"),
     *                 @SWG\Property(property="order_type", type="string", description="订单类型"),
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function createNewOrder(Request $request)
    {
        $authInfo = $request->get('auth');
        $params = $request->all();
        $params['company_id'] = $authInfo['company_id'];
        $params['mobile'] = $authInfo['mobile'] ?? '';
        $params['nickname'] = $authInfo['nickname'] ?? '';
        $params['user_id'] = $authInfo['user_id'] ?? 0;
        $params['authorizer_appid'] = $authInfo['woa_appid'] ?? '';
        $params['wxa_appid'] = $authInfo['wxapp_appid'] ?? '';
        $params['order_type'] = isset($params['order_type']) ? $params['order_type'] : 'service';
        $params['pay_type'] = $request->input('pay_type', '');
        $params['iscrossborder'] = $request->input('iscrossborder', 0);
        $params['point_use'] = $request->input('point_use', 0);
        $params['isShopScreen'] = $request->input('isShopScreen', 0);
        $params['isNostores'] = $request->input('isNostores', 0);
        $params['user_device'] = $request->get('user_device');
        $params['receipt_type'] = $request->input('receipt_type', 'logistics');
        $params['isSalesmanPage'] = $request->input('isSalesmanPage', 0);
        $params['promoter_user_id'] = $request->input('promoter_user_id', 0);
        $params['source_from'] = $this->getOrderSourceFrom($request);

        if(!empty($params['distributor_id'])){
            if((new DistributorWhiteListService())->checkUserValidCommon($params['distributor_id'],$params['user_id'],$params['company_id']) === false){
                throw new ResourceException(trans('OrdersBundle/Order.non_shop_member_cannot_order'));
            }
        }
        app('log')->info(':dkxd:'.__FUNCTION__.__LINE__.':1 createorder:params :' . json_encode($params) ) ;

        if ($params['work_userid'] ?? '') {
            $salesPersonService = new SalespersonService();
            $salespersonInfo = $salesPersonService->salesperson->getInfo(['work_userid' => $params['work_userid'], 'company_id' => $authInfo['company_id']]);
            $params['salesman_id'] = $salespersonInfo['salesperson_id'] ?? 0;
        }        

        app('log')->info(':dkxd:'.__FUNCTION__.__LINE__.':1 createorder:params :' . json_encode($params) ) ;

        if(isset($params['promoter_user_id'])
        && $params['promoter_user_id']
        &&  $authInfo['user_id'] == $params['promoter_user_id'] ){
            $params['user_id'] = $params['buy_user_id'] ;
            $params['order_source'] = 'salesperson' ;
            $params['mobile'] = $params['receiver_mobile'] ?? 0;
            $params['salesman_id'] = $params['promoter_user_id'] ?? 0;
        }
        app('log')->info(':dkxd:'.__FUNCTION__.__LINE__.':2 createorder:params :' . json_encode($params) ) ;

        $orderService = $this->getOrderService($params['order_type']);
        $result = $orderService->create($params);

        // 全额积分抵扣
        if ($result['total_fee'] == 0 && $params['pay_type'] == 'point' && $result['prescription_status'] == 0) { // prescription_status == 0非处方订单，处方订单需要补充开方信息后才能支付
            //获取店铺信息
            $distributorInfo = [];
            $distributorService = new DistributorService();
            if ($result['distributor_id'] ?? '') {
                $filter = [
                    'company_id' => $params['company_id'],
                    'distributor_id' => $result['distributor_id'],
                ];
                $distributorInfo = $distributorService->getInfo($filter);
            }
            $data = [
                'company_id' => $authInfo['company_id'],
                'user_id' => $authInfo['user_id'],
                'total_fee' => $result['total_fee'],
                'detail' => $result['title'],
                'order_id' => $result['order_id'],
                'body' => $result['title'],
                'open_id' => $authInfo['open_id'] ?? '',
                'wxa_appid' => $authInfo['wxapp_appid'] ?? '',
                'mobile' => $authInfo['mobile'],
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
            $authorizerAppId = $authInfo['woa_appid'] ?? '';
            $wxaAppId = $authInfo['wxapp_appid'] ?? '';
            $service = $this->getPaymentService($params['pay_type'], $data['distributor_id']);
            app('log')->info(':dkxd:'.__FUNCTION__.__LINE__.':3 dopayment createorder:params :' . json_encode($params) ) ;

            $pay = $service->doPayment($authorizerAppId, $wxaAppId, $data, false);
            if($data['pay_type'] == 'paypal'){
                return  $pay;
            }
        }

        // 线下转账
        if ($params['isSalesmanPage'] == 1 && $params['promoter_user_id'] > 0 && $params['pay_type'] == 'offline_pay') {
            $offlinePayData = $orderService->getSalesmanOfflinePayData($params, $result);
            $authorizerAppId = $authInfo['woa_appid'] ?? '';
            $wxaAppId = $offlinePayData['wxapp_appid'] ?? '';

            $service = $this->getPaymentService($params['pay_type'], $offlinePayData['distributor_id']);
            $service->doPayment($authorizerAppId, $wxaAppId, $offlinePayData, false);
        }

        $payResult = [
            'order_id' => $result['order_id'],
            'pay_type' => $params['pay_type'],
            'order_type' => $params['order_type'],
            'team_id' => isset($result['team_id']) ? $result['team_id'] : null,
            'total_fee' => $result['total_fee'],
        ];
        return $this->response->array($payResult);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/getFreightFee",
     *     summary="获取订单优惠以及运费信息",
     *     tags={"订单"},
     *     description="获取订单优惠以及运费信息",
     *     operationId="createOrder",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter( name="shop_id", in="query", description="门店ID", required=false, type="integer"),
     *     @SWG\Parameter( name="item_id", in="query", description="交易商品ID", required=false, type="integer"),
     *     @SWG\Parameter( name="item_num", in="query", description="交易商品数量", required=false, type="number"),
     *     @SWG\Parameter( name="items[][item_id]", in="query", description="交易商品ID", required=false, type="integer"),
     *     @SWG\Parameter( name="items[][num]", in="query", description="交易商品数量", required=false, type="number"),
     *     @SWG\Parameter( name="bargain_id", in="query", description="砍价活动id", required=false, type="integer"),
     *     @SWG\Parameter( name="source_id", in="query", description="订单来源ID", required=false, type="integer"),
     *     @SWG\Parameter( name="monitor_id", in="query", description="监控页面ID", required=false, type="integer"),
     *     @SWG\Parameter( name="salesman_id", in="query", description="导购ID", required=false, type="integer"),
     *     @SWG\Parameter( name="receipt_type", in="query", description="收货方式：ziti 自提 logistics 快递", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_name", in="query", description="收货人姓名", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_mobile", in="query", description="收货人手机号", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_zip", in="query", description="收货人邮编", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_state", in="query", description="收货人所在省份", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_city", in="query", description="收货人所在城市", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_district", in="query", description="收货人所在地区", required=false, type="string"),
     *     @SWG\Parameter( name="receiver_address", in="query", description="收货人详细地址", required=false, type="string"),
     *     @SWG\Parameter( name="cart_type", in="query", description="购物车类型 cart 购物车 fastbuy 立即购买 offline 离线购物车", required=true, type="string"),
     *     @SWG\Parameter( name="order_type", in="query", description="订单类型，service服务业订单 bargain 砍价订单 normal 实物订单 normal_community社区团购订单", required=true, type="string"),
     *     @SWG\Parameter( name="pay_type", in="query", description="支付类型 wxpay 微信支付 point 积分支付 deposit 储值支付", required=false, type="string"),
     *     @SWG\Parameter( name="coupon_discount", in="query", description="优惠券优惠码", required=false, type="string"),
     *     @SWG\Parameter( name="not_use_coupon", in="query", description="不使用优惠券", required=false, type="boolean"),
     *     @SWG\Parameter( name="iscrossborder", in="query", description="是否海外购", required=false, type="boolean"),
     *     @SWG\Parameter( name="isShopScreen", in="query", description="是否大屏", required=false, type="boolean"),
     *     @SWG\Parameter( name="isNostores", in="query", description="是否支持无门店下单门店自提", required=false, type="boolean"),
     *     @SWG\Parameter( name="community_activity_id", in="query", description="社区团购活动ID，order_type=normal_community时必填", required=false, type="string"),
     *     @SWG\Parameter( name="community_ziti_id", in="query", description="社区团购自提点ID，order_type=normal_community时必填", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="order_id", type="string", description="订单号"),
     *                 @SWG\Property(property="company_id", type="integer", description="公司ID"),
     *                 @SWG\Property(property="user_id", type="integer", description="用户ID"),
     *                 @SWG\Property(property="distributor_id", type="integer", description="店铺ID"),
     *                 @SWG\Property(property="is_distribution", type="boolean", description="是否店铺订单"),
     *                 @SWG\Property(property="remark", type="string", description="订单备注"),
     *                 @SWG\Property(property="mobile", type="string", description="订单号"),
     *                 @SWG\Property(property="order_source", type="string", description="订单来源"),
     *                 @SWG\Property(property="order_class", type="string", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单"),
     *                 @SWG\Property(property="order_type", type="string", description="订单类型。可选值有 normal:普通实体订单"),
     *                 @SWG\Property(property="order_status", type="string", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *                 @SWG\Property(property="auto_cancel_time", type="number", description="订单自动取消时间"),
     *                 @SWG\Property(property="discount_fee", type="number", description="订单优惠金额，以分为单位"),
     *                 @SWG\Property(
     *                     property="discount_info",
     *                     type="array",
     *                     @SWG\Items(
     *                         type="object",
     *                         @SWG\Property(property="type", type="string", description="优惠类型"),
     *                         @SWG\Property(property="rule", type="string", description="促销规则"),
     *                         @SWG\Property(property="discount_fee", type="integer", description="优惠金额"),
     *                     )
     *                 ),
     *                 @SWG\Property(property="pay_type", type="string", description="支付方式"),
     *                 @SWG\Property(property="salesman_id", type="integer", description="导购员ID"),
     *                 @SWG\Property(property="point_use", type="integer", description="积分抵扣使用的积分数"),
     *                 @SWG\Property(property="point_fee", type="integer", description="积分抵扣金额，以分为单位"),
     *                 @SWG\Property(property="get_point_type", type="integer", description="获取积分类型，0 老订单按订单完成时送,1 新订单按下单时计算送"),
     *                 @SWG\Property(property="is_profitsharing", type="integer", description="是否分账订单 1不分账 2分账"),
     *                 @SWG\Property(property="profitsharing_rate", type="integer", description="分账费率"),
     *                 @SWG\Property(property="pack", type="integer", description="包装"),
     *                 @SWG\Property(property="fee_rate", type="number", description="货币汇率"),
     *                 @SWG\Property(property="fee_type", type="string", description="货币类型"),
     *                 @SWG\Property(property="fee_symbol", type="string", description="货币符号"),
     *                 @SWG\Property(property="source_id", type="integer", description="订单来源id"),
     *                 @SWG\Property(property="monitor_id", type="integer", description="订单监控页面id"),
     *                 @SWG\Property(property="is_logistics", type="boolean", description="门店缺货商品总部快递发货"),
     *                 @SWG\Property(property="point", type="integer", description="消费积分"),
     *                 @SWG\Property(property="item_fee", type="string", description="商品金额，以分为单位"),
     *                 @SWG\Property(property="cost_fee", type="integer", description="商品成本价，以分为单位"),
     *                 @SWG\Property(property="total_fee", type="string", description="订单金额，以分为单位"),
     *                 @SWG\Property(property="total_rebate", type="integer", description="订单总分销金额，以分为单位"),
     *                 @SWG\Property(property="title", type="string", description="订单标题"),
     *                 @SWG\Property(property="totalItemNum", type="integer", description="商品商品数量"),
     *                 @SWG\Property(property="goods_discount", type="integer", description="订单优惠金额"),
     *                 @SWG\Property(property="member_discount", type="integer", description="会员优惠"),
     *                 @SWG\Property(property="freight_fee", type="integer", description="运费价格，以分为单位"),
     *                 @SWG\Property(property="is_shopscreen", type="boolean", description="是否门店大屏订单"),
     *                 @SWG\Property(property="coupon_discount", type="integer", description="优惠券优惠金额"),
     *                 @SWG\Property(
     *                     property="coupon_info",
     *                     type="array",
     *                     @SWG\Items(
     *                         type="object",
     *                         @SWG\Property(property="id", type="integer", description="优惠券ID"),
     *                         @SWG\Property(property="coupon_code", type="string", description="优惠券码"),
     *                         @SWG\Property(property="info", type="string", description="优惠信息"),
     *                         @SWG\Property(property="type", type="string", description="优惠类型"),
     *                         @SWG\Property(property="rule", type="string", description="促销规则"),
     *                         @SWG\Property(property="discount_fee", type="integer", description="优惠金额"),
     *                     )
     *                 ),
     *                 @SWG\Property(property="get_points", type="integer", description="订单获取积分"),
     *                 @SWG\Property(property="bonus_points", type="integer", description="购物赠送积分"),
     *                 @SWG\Property(property="user_point", type="integer", description="用户积分"),
     *                 @SWG\Property(property="max_point", type="integer", description="本单会员最大可抵扣积分"),
     *                 @SWG\Property(property="limit_point", type="integer", description="本单最大可抵扣积分"),
     *                 @SWG\Property(
     *                     property="items",
     *                     type="array",
     *                     @SWG\Items(
     *                         type="object",
     *                         @SWG\Property(property="order_id", type="string", description="订单号"),
     *                         @SWG\Property(property="item_id", type="integer", description="商品id"),
     *                         @SWG\Property(property="item_bn", type="string", description="商品编码"),
     *                         @SWG\Property(property="company_id", type="integer", description="公司id"),
     *                         @SWG\Property(property="user_id", type="integer", description="用户id"),
     *                         @SWG\Property(property="item_name", type="string", description="商品名称"),
     *                         @SWG\Property(property="templates_id", type="integer", description="运费模板id"),
     *                         @SWG\Property(property="num", type="number", description="购买商品数量"),
     *                         @SWG\Property(property="price", type="number", description="单价，以分为单位"),
     *                         @SWG\Property(property="item_fee", type="number", description="商品总金额，以分为单位"),
     *                         @SWG\Property(property="cost_fee", type="number", description="商品成本价，以分为单位"),
     *                         @SWG\Property(property="item_unit", type="string", description="商品计量单位"),
     *                         @SWG\Property(property="total_fee", type="number", description="订单金额，以分为单位"),
     *                         @SWG\Property(property="discount_fee", type="number", description="订单优惠金额，以分为单位"),
     *                         @SWG\Property(
     *                             property="discount_info",
     *                             type="array",
     *                             @SWG\Items(
     *                                 type="object",
     *                                 @SWG\Property(property="type", type="string", description="优惠类型"),
     *                                 @SWG\Property(property="rule", type="string", description="促销规则"),
     *                                 @SWG\Property(property="discount_fee", type="integer", description="优惠金额"),
     *                             )
     *                         ),
     *                         @SWG\Property(property="rebate", type="number", description="单个分销金额，以分为单位"),
     *                         @SWG\Property(property="total_rebate", type="number", description="总分销金额，以分为单位"),
     *                         @SWG\Property(property="distributor_id", type="integer", description="分销商id"),
     *                         @SWG\Property(property="is_total_store", type="boolean", description="是否是总部库存"),
     *                         @SWG\Property(property="fee_rate", type="number", description="货币汇率"),
     *                         @SWG\Property(property="fee_type", type="string", description="货币类型"),
     *                         @SWG\Property(property="fee_symbol", type="string", description="货币符号"),
     *                         @SWG\Property(property="item_point", type="integer", description="商品积分"),
     *                         @SWG\Property(property="point", type="integer", description="商品总积分"),
     *                         @SWG\Property(property="volume", type="number", description="商品体积"),
     *                         @SWG\Property(property="weight", type="number", description="商品重量"),
     *                         @SWG\Property(property="order_item_type", type="string", description="订单商品类型"),
     *                         @SWG\Property(property="type", type="integer", description="订单类型，0普通订单,1跨境订单"),
     *                         @SWG\Property(property="crossborder_tax_rate", type="number", description="商品跨境税费"),
     *                         @SWG\Property(property="is_logistics", type="boolean", description="门店缺货商品总部快递发货"),
     *                         @SWG\Property(property="member_discount", type="number", description="会员折扣金额，以分为单位"),
     *                         @SWG\Property(property="coupon_discount", type="number", description="优惠券抵扣金额，以分为单位"),
     *                     )
     *                 ),
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getOrderFreightFeeInfo(Request $request)
    {
        $authInfo = $request->get('auth');
        $params = $request->all();
        $params['company_id'] = $authInfo['company_id'];
        $params['mobile'] = $authInfo['mobile'] ?? '';
        $params['user_id'] = $authInfo['user_id'] ?? 0;
        $params['authorizer_appid'] = $authInfo['woa_appid'] ?? '';
        $params['wxa_appid'] = $authInfo['wxapp_appid'] ?? '';
        $params['order_type'] = ($params['order_type'] ?? '') ? $params['order_type'] : 'service';
        $params['pay_type'] = ($params['pay_type'] ?? '') ? $params['pay_type'] : 'wxpay';
        $params['not_use_coupon'] = $params['not_use_coupon'] ?? 0;
        $params['iscrossborder'] = $params['iscrossborder'] ?? 0;
        $params['isShopScreen'] = $params['isShopScreen'] ?? 0;
        $params['isNostores'] = $params['isNostores'] ?? 0;
        $params['user_device'] = $request->get('user_device');
        // $promoter_user_id = $params['promoter_user_id'] ?? 0;

        if(isset($params['promoter_user_id'])
        && $params['promoter_user_id']
        &&  $authInfo['user_id'] == $params['promoter_user_id'] ){
            $params['user_id'] = $params['buy_user_id'] ;
        }
        if(!empty($params['distributor_id'])){
            if((new DistributorWhiteListService())->checkUserValidCommon($params['distributor_id'],$params['user_id'],$params['company_id']) === false){
                throw new ResourceException(trans('OrdersBundle/Order.non_shop_member_cannot_order'));
            }
        }

        $orderService = $this->getOrderService($params['order_type']);
        $result = $orderService->getOrderTempInfo($params);
        $settingService = new SettingService();
        // $invoiceSetting = $settingService->getInvoiceSetting($authInfo['company_id']);
        $invoice_status = $settingService->checkoutInvoiceStatus($result);
        app('log')->info(':invoice_status:'.__FUNCTION__.__LINE__.':invoice_status:' . json_encode($invoice_status));
        $result['invoice_status'] = $invoice_status;
        // 总部发货的商品购物车分开显示
        $items = $logisticsItems = [];
        foreach ($result['items'] as $key => $item) {
            $item['is_logistics'] = $item['is_logistics'] ?? false;
            if ($item['is_logistics'] === 'true' || $item['is_logistics'] === true) {
                $item['is_logistics'] = true;
            } else {
                $item['is_logistics'] = false;
            }
            if ($item['is_logistics']) {
                $logisticsItems[] = $item;
            } else {
                $items[] = $item;
            }
            $result['items'] = $items;
            $result['logistics_items'] = $logisticsItems;
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/order/{order_id}",
     *     summary="获取订单详情",
     *     tags={"订单"},
     *     description="获取订单详情",
     *     operationId="getOrderDetail",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *              @SWG\Property(property="orderInfo", type="object", description="",
     *                   @SWG\Property(property="order_id", type="string", example="3307706000520376", description="订单号"),
     *                   @SWG\Property(property="title", type="string", example="测试0119-2...", description="订单标题"),
     *                   @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                   @SWG\Property(property="user_id", type="string", example="20376", description="购买用户"),
     *                   @SWG\Property(property="act_id", type="string", example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
     *                   @SWG\Property(property="mobile", type="string", example="13095920688", description="购买用户手机号"),
     *                   @SWG\Property(property="freight_fee", type="integer", example="1", description="运费价格，以分为单位"),
     *                   @SWG\Property(property="freight_type", type="string", example="cash", description=""),
     *                   @SWG\Property(property="item_fee", type="string", example="1", description="商品总金额，以分为单位"),
     *                   @SWG\Property(property="item_point", type="integer", example="0", description="商品积分"),
     *                   @SWG\Property(property="cost_fee", type="integer", example="10000", description="商品成本价，以分为单位"),
     *                   @SWG\Property(property="total_fee", type="string", example="2", description="应付总金额,以分为单位"),
     *                   @SWG\Property(property="step_paid_fee", type="integer", example="0", description="分阶段付款已支付金额，以分为单位"),
     *                   @SWG\Property(property="total_rebate", type="integer", example="0", description="总分销金额，以分为单位"),
     *                   @SWG\Property(property="distributor_id", type="string", example="104", description="门店ID"),
     *                   @SWG\Property(property="receipt_type", type="string", example="logistics", description="收货方式。可选值有 logistics:物流;ziti:店铺自提"),
     *                   @SWG\Property(property="ziti_code", type="string", example="0", description="店铺自提码"),
     *                   @SWG\Property(property="shop_id", type="string", example="0", description="门店ID"),
     *                   @SWG\Property(property="ziti_status", type="string", example="NOTZITI", description="店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核"),
     *                   @SWG\Property(property="order_status", type="string", example="CANCEL", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *                   @SWG\Property(property="order_source", type="string", example="member", description="订单来源。可选值有 member-用户自主下单;shop-商家代客下单"),
     *                   @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 normal:普通实体订单"),
     *                   @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单"),
     *                   @SWG\Property(property="auto_cancel_time", type="string", example="1611136217", description="订单自动取消时间"),
     *                   @SWG\Property(property="auto_cancel_seconds", type="integer", example="-163305", description=""),
     *                   @SWG\Property(property="auto_finish_time", type="string", example="", description="订单自动完成时间"),
     *                   @SWG\Property(property="is_distribution", type="string", example="1", description="是否分销订单"),
     *                   @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *                   @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *                   @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
     *                   @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *                   @SWG\Property(property="delivery_corp_source", type="string", example="", description="快递代码来源"),
     *                   @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *                   @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
     *                   @SWG\Property(property="delivery_status", type="string", example="PENDING", description="发货状态。可选值有 DONE—已发货;PENDING—待发货"),
     *                   @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *                   @SWG\Property(property="delivery_time", type="string", example="", description="发货时间"),
     *                   @SWG\Property(property="end_time", type="string", example="", description="订单完成时间"),
     *                   @SWG\Property(property="end_date", type="string", example="", description=""),
     *                   @SWG\Property(property="receiver_name", type="string", example="张三", description="收货人姓名"),
     *                   @SWG\Property(property="receiver_mobile", type="string", example="13095920688", description="收货人手机号"),
     *                   @SWG\Property(property="receiver_zip", type="string", example="101001", description="收货人邮编"),
     *                   @SWG\Property(property="receiver_state", type="string", example="北京市", description="收货人所在省份"),
     *                   @SWG\Property(property="receiver_city", type="string", example="北京市", description="收货人所在城市"),
     *                   @SWG\Property(property="receiver_district", type="string", example="东城", description="收货人所在地区"),
     *                   @SWG\Property(property="receiver_address", type="string", example="101", description="收货人详细地址"),
     *                   @SWG\Property(property="member_discount", type="integer", example="0", description="会员折扣金额，以分为单位"),
     *                   @SWG\Property(property="coupon_discount", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *                   @SWG\Property(property="discount_fee", type="integer", example="0", description="订单优惠金额"),
     *                   @SWG\Property(property="create_time", type="integer", example="1611135617", description="订单创建时间"),
     *                   @SWG\Property(property="update_time", type="integer", example="1611136203", description="订单更新时间"),
     *                   @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                   @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *                   @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                   @SWG\Property(property="cny_fee", type="integer", example="2", description=""),
     *                   @SWG\Property(property="point", type="integer", example="0", description="商品总积分"),
     *                   @SWG\Property(property="pay_type", type="string", example="wxpay", description="支付方式。wxpay-微信支付;deposit-预存款支付;pos-刷卡;point-积分"),
     *                   @SWG\Property(property="remark", type="string", example="", description="订单备注"),
     *                  @SWG\Property(property="third_params", type="object", description="",
     *                           @SWG\Property(property="is_liveroom", type="string", example="1", description=""),
     *                  ),
     *                   @SWG\Property(property="invoice", type="string", example="", description="发票信息(DC2Type:json_array)"),
     *                   @SWG\Property(property="send_point", type="integer", example="0", description="是否分发积分0否 1是"),
     *                   @SWG\Property(property="is_rate", type="string", example="", description="是否评价"),
     *                   @SWG\Property(property="is_invoiced", type="string", example="", description="是否已开发票"),
     *                   @SWG\Property(property="invoice_number", type="string", example="", description="发票号"),
     *                   @SWG\Property(property="audit_status", type="string", example="processing", description="跨境订单审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                   @SWG\Property(property="audit_msg", type="string", example="正在审核订单", description="审核意见"),
     *                   @SWG\Property(property="point_fee", type="integer", example="0", description="积分抵扣时分摊的积分的金额，以分为单位"),
     *                   @SWG\Property(property="point_use", type="integer", example="0", description="积分抵扣使用的积分数"),
     *                   @SWG\Property(property="pay_status", type="string", example="NOTPAY", description="支付状态。可选值有 NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中"),
     *                   @SWG\Property(property="get_points", type="integer", example="2", description="订单获取积分"),
     *                   @SWG\Property(property="bonus_points", type="integer", example="0", description="购物赠送积分"),
     *                   @SWG\Property(property="get_point_type", type="integer", example="1", description="获取积分类型，0 老订单按订单完成时送,1 新订单按下单时计算送"),
     *                   @SWG\Property(property="pack", type="string", example="", description="包装"),
     *                   @SWG\Property(property="is_shopscreen", type="string", example="", description="是否门店订单"),
     *                   @SWG\Property(property="is_logistics", type="string", example="", description="门店缺货商品总部快递发货"),
     *                   @SWG\Property(property="is_profitsharing", type="integer", example="1", description="是否分账订单 1不分账 2分账"),
     *                   @SWG\Property(property="profitsharing_status", type="integer", example="1", description="分账状态 1未分账 2已分账"),
     *                   @SWG\Property(property="order_auto_close_aftersales_time", type="string", example="", description="自动关闭售后时间"),
     *                   @SWG\Property(property="profitsharing_rate", type="integer", example="0", description="分账费率"),
     *                   @SWG\Property(property="bind_auth_code", type="string", example="", description=""),
     *                   @SWG\Property(property="extra_points", type="integer", example="0", description=""),
     *                   @SWG\Property(property="type", type="integer", example="0", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                   @SWG\Property(property="taxable_fee", type="integer", example="0", description="计税总价，以分为单位"),
     *                   @SWG\Property(property="identity_id", type="string", example="", description="身份证号码"),
     *                   @SWG\Property(property="identity_name", type="string", example="", description="身份证姓名"),
     *                   @SWG\Property(property="total_tax", type="integer", example="0", description="总税费"),
     *                   @SWG\Property(property="discount_info", type="string", description=""),
     *                   @SWG\Property(property="can_apply_aftersales", type="integer", example="0", description=""),
     *                   @SWG\Property(property="distributor_name", type="string", example="普天信息产业园", description=""),
     *                   @SWG\Property(property="items", type="array", description="",
     *                     @SWG\Items(
     *                                           @SWG\Property(property="id", type="string", example="8855", description="ID"),
     *                                           @SWG\Property(property="order_id", type="string", example="3307706000520376", description="订单号"),
     *                                           @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                                           @SWG\Property(property="user_id", type="string", example="20376", description="购买用户"),
     *                                           @SWG\Property(property="act_id", type="string", example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
     *                                           @SWG\Property(property="item_id", type="string", example="5437", description="商品id"),
     *                                           @SWG\Property(property="item_bn", type="string", example="dsaksak1191", description="商品编码"),
     *                                           @SWG\Property(property="item_name", type="string", example="测试0119-2", description="商品名称"),
     *                                           @SWG\Property(property="pic", type="string", example="https://bbctest.aixue7.com/image/1/2021/01/06/e6d2a893739b6640ebb2c86c15ce29786JByhCPBiTPxzjMr8s9STXD01oSb7zJk", description="商品图片"),
     *                                           @SWG\Property(property="num", type="integer", example="1", description="购买商品数量"),
     *                                           @SWG\Property(property="price", type="integer", example="1", description="单价，以分为单位"),
     *                                           @SWG\Property(property="total_fee", type="integer", example="1", description="应付总金额,以分为单位"),
     *                                           @SWG\Property(property="templates_id", type="integer", example="105", description="运费模板id"),
     *                                           @SWG\Property(property="rebate", type="integer", example="0", description="单个分销金额，以分为单位"),
     *                                           @SWG\Property(property="total_rebate", type="integer", example="0", description="总分销金额，以分为单位"),
     *                                           @SWG\Property(property="item_fee", type="integer", example="1", description="商品总金额，以分为单位"),
     *                                           @SWG\Property(property="cost_fee", type="integer", example="10000", description="商品成本价，以分为单位"),
     *                                           @SWG\Property(property="item_unit", type="string", example="", description="商品计量单位"),
     *                                           @SWG\Property(property="member_discount", type="integer", example="0", description="会员折扣金额，以分为单位"),
     *                                           @SWG\Property(property="coupon_discount", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *                                           @SWG\Property(property="discount_fee", type="integer", example="0", description="订单优惠金额"),
     *                                           @SWG\Property(property="discount_info", type="string", description=""),
     *                                           @SWG\Property(property="shop_id", type="string", example="0", description="门店ID"),
     *                                           @SWG\Property(property="is_total_store", type="string", example="1", description="是否是总部库存(true:总部库存，false:店铺库存)"),
     *                                           @SWG\Property(property="distributor_id", type="string", example="104", description="门店ID"),
     *                                           @SWG\Property(property="create_time", type="integer", example="1611135617", description="订单创建时间"),
     *                                           @SWG\Property(property="update_time", type="integer", example="1611135617", description="订单更新时间"),
     *                                           @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *                                           @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *                                           @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
     *                                           @SWG\Property(property="delivery_time", type="string", example="", description="发货时间"),
     *                                           @SWG\Property(property="delivery_status", type="string", example="PENDING", description="发货状态。可选值有 DONE—已发货;PENDING—待发货"),
     *                                           @SWG\Property(property="aftersales_status", type="string", example="", description="售后状态。可选值有 WAIT_SELLER_AGREE 0 等待商家处理;WAIT_BUYER_RETURN_GOODS 1 商家接受申请，等待消费者回寄;WAIT_SELLER_CONFIRM_GOODS 2 消费者回寄，等待商家收货确认;SELLER_REFUSE_BUYER 3 售后驳回;SELLER_SEND_GOODS 4 卖家重新发货 换货完成;REFUND_SUCCESS 5 退款成功;REFUND_CLOSED 6 退款关闭;CLOSED 7 售后关闭"),
     *                                           @SWG\Property(property="refunded_fee", type="integer", example="0", description="退款金额，以分为单位"),
     *                                           @SWG\Property(property="after_sales_fee", type="integer", example="0", description="退款中的金额，以分为单位"),
     *                                           @SWG\Property(property="remain_fee", type="integer", example="0", description="剩余可退款金额，以分为单位"),
     *                                           @SWG\Property(property="remain_point", type="integer", example="0", description="剩余可退积分"),
     *                                           @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                                           @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *                                           @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                                           @SWG\Property(property="cny_fee", type="integer", example="1", description=""),
     *                                           @SWG\Property(property="item_point", type="integer", example="0", description="商品积分"),
     *                                           @SWG\Property(property="point", type="integer", example="0", description="商品总积分"),
     *                                           @SWG\Property(property="item_spec_desc", type="string", example="", description="商品规格描述"),
     *                                           @SWG\Property(property="order_item_type", type="string", example="normal", description="订单商品类型,normal:正常商品，gift: 赠品, plus_buy: 加价购商品"),
     *                                           @SWG\Property(property="volume", type="integer", example="0", description="商品体积"),
     *                                           @SWG\Property(property="weight", type="integer", example="0", description="商品重量"),
     *                                           @SWG\Property(property="is_rate", type="string", example="", description="是否评价"),
     *                                           @SWG\Property(property="auto_close_aftersales_time", type="string", example="", description="自动关闭售后时间"),
     *                                           @SWG\Property(property="share_points", type="integer", example="0", description="积分抵扣时分摊的积分值"),
     *                                           @SWG\Property(property="point_fee", type="integer", example="0", description="积分抵扣时分摊的积分的金额，以分为单位"),
     *                                           @SWG\Property(property="is_logistics", type="string", example="", description="门店缺货商品总部快递发货"),
     *                                           @SWG\Property(property="delivery_item_num", type="string", example="", description="发货单发货数量"),
     *                                           @SWG\Property(property="get_points", type="integer", example="1", description="订单获取积分"),
     *                     ),
     *                   ),
     *                   @SWG\Property(property="order_status_des", type="string", example="CANCEL", description=""),
     *                   @SWG\Property(property="order_status_msg", type="string", example="已取消", description=""),
     *                   @SWG\Property(property="latest_aftersale_time", type="integer", example="0", description=""),
     *                   @SWG\Property(property="estimate_get_points", type="string", example="2", description=""),
     *                   @SWG\Property(property="delivery_type", type="string", example="new", description=""),
     *                   @SWG\Property(property="is_all_delivery", type="string", example="", description=""),
     *                   @SWG\Property(property="pickupcode_status", type="string", example="", description=""),
     *                   @SWG\Property(property="is_split", type="string", example="", description=""),
     *                   @SWG\Property(property="logistics_items", type="string", description=""),
     *              ),
     *               @SWG\Property(property="tradeInfo", type="string", description=""),
     *              @SWG\Property(property="distributor", type="object", description="",
     *                   @SWG\Property(property="distributor_id", type="string", example="104", description="门店ID"),
     *                   @SWG\Property(property="shop_id", type="string", example="0", description="门店ID"),
     *                   @SWG\Property(property="is_distributor", type="string", example="1", description="是否是主店铺"),
     *                   @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                   @SWG\Property(property="mobile", type="string", example="17621612312", description="购买用户手机号"),
     *                   @SWG\Property(property="address", type="string", example="宜山路700号", description="店铺地址"),
     *                   @SWG\Property(property="name", type="string", example="普天信息产业园", description="店铺名称"),
     *                   @SWG\Property(property="auto_sync_goods", type="string", example="1", description="自动同步总部商品"),
     *                   @SWG\Property(property="logo", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/Hw4SsicubkrdnwoLMY38PLNULch2rPgsGb4NCVCC4EGa8EFs2MPCSbzJolznV64F0L5VetQvyE2ZrCcIb1ZALEA/0?wx_fmt=png", description="店铺logo"),
     *                   @SWG\Property(property="contract_phone", type="string", example="17621612312", description="其他联系方式"),
     *                   @SWG\Property(property="banner", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/Hw4Ssicubkre4SsqeJKcShn3CyCQc3L52zM5jHpUo4hkicCiby1qmz5g5XpAIPg5JMFxgNcHUoCtg9vLT7QbzibP2w/0?wx_fmt=png", description="店铺banner"),
     *                   @SWG\Property(property="contact", type="string", example="张", description="联系人名称"),
     *                   @SWG\Property(property="is_valid", type="string", example="true", description="店铺是否有效"),
     *                   @SWG\Property(property="lng", type="string", example="121.417537", description="腾讯地图纬度"),
     *                   @SWG\Property(property="lat", type="string", example="31.176567", description="腾讯地图经度"),
     *                   @SWG\Property(property="child_count", type="integer", example="0", description=""),
     *                   @SWG\Property(property="is_default", type="integer", example="0", description="门店id"),
     *                   @SWG\Property(property="is_audit_goods", type="string", example="1", description="是否审核店铺商品"),
     *                   @SWG\Property(property="is_ziti", type="string", example="1", description="是否支持自提"),
     *                   @SWG\Property( property="regions_id", type="array",
     *                        @SWG\Items( type="string", example="310000", description="地区编码"),
     *                   ),
     *                   @SWG\Property( property="regions", type="array",
     *                        @SWG\Items( type="string", example="上海市", description="地区名称"),
     *                   ),
     *                   @SWG\Property(property="is_domestic", type="integer", example="1", description="是否是中国国内门店 1:国内(包含港澳台),2:非国内"),
     *                   @SWG\Property(property="is_direct_store", type="integer", example="1", description="是否为直营店 1:直营店,2:非直营店"),
     *                   @SWG\Property(property="province", type="string", example="上海市", description=""),
     *                   @SWG\Property(property="is_delivery", type="string", example="1", description="是否支持配送"),
     *                   @SWG\Property(property="city", type="string", example="上海市", description=""),
     *                   @SWG\Property(property="area", type="string", example="徐汇区", description=""),
     *                   @SWG\Property(property="hour", type="string", example="08:00-21:00", description="营业时间"),
     *                   @SWG\Property(property="created", type="integer", example="1606292438", description=""),
     *                   @SWG\Property(property="updated", type="integer", example="1609999278", description=""),
     *                   @SWG\Property(property="shop_code", type="string", example="", description="店铺号"),
     *                   @SWG\Property(property="wechat_work_department_id", type="integer", example="0", description="企业微信的部门ID"),
     *                   @SWG\Property(property="distributor_self", type="integer", example="0", description="是否是总店配置"),
     *                   @SWG\Property(property="regionauth_id", type="string", example="1", description="区域id"),
     *                   @SWG\Property(property="is_open", type="string", example="true", description="是否开启分账"),
     *                   @SWG\Property(property="rate", type="string", example="10.00", description="平台服务费率"),
     *                   @SWG\Property(property="store_address", type="string", example="上海市徐汇区宜山路700号", description=""),
     *                   @SWG\Property(property="store_name", type="string", example="普天信息产业园", description=""),
     *                   @SWG\Property(property="phone", type="string", example="17621612312", description=""),
     *              ),
     *               @SWG\Property(property="cancelData", type="string", description=""),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones")))
     * )
     */
    public function getOrderDetail(Request $request, $order_id)
    {
        $orderId = $order_id;
        if (!$orderId) {
            throw new BadRequestHttpException('订单号必填');
        }
        $authInfo = $request->get('auth');
        $invoice_list =  $request->input('invoice_list',0);
        //储值订单, 查询订单详情。H5端收银台界面会调用该接口
        $str = strtoupper(mb_substr($orderId, 0, 2));
        if ('CZ' == $str) {
            $deposit_trade_service = new DepositTrade();
            $deposit_trade_info = $deposit_trade_service->getDepositTradeInfo($orderId);
            $trade_status = $deposit_trade_info['trade_status'];
            $order_status = 'NOTPAY';
            if ($trade_status == 'SUCCESS') {
                $order_status = 'DONE';
            }
            $result['orderInfo']['order_id'] = $deposit_trade_info['deposit_trade_id'];
            $result['orderInfo']['order_type'] = '';
            $result['orderInfo']['pay_type'] = $deposit_trade_info['pay_type'];
            $result['orderInfo']['point'] = '';
            $result['orderInfo']['title'] = $deposit_trade_info['detail'];
            $result['orderInfo']['total_fee'] = (int)$deposit_trade_info['money'];
            $result['orderInfo']['create_time'] = time(); //客户端需要展示该字段，数据库表未记录，返回当前系统时间
            $result['orderInfo']['order_status'] = $order_status;

            $result['tradeInfo']['orderId'] = $deposit_trade_info['deposit_trade_id'];
            $result['tradeInfo']['tradeState'] = $trade_status;
            return $this->response->array($result);
        }
        if ('TD' == $str) {
            $tradeService = new TradeService();
            $tradeInfo = $tradeService->getInfoById(substr($orderId, 2));
            if (!$tradeInfo) {
                throw new BadRequestHttpException('支付单不存在');
            }
            $orderId = $tradeInfo['order_id'];
        }
        $from = $request->input('from', 'front_list');
        $params['promoter_user_id'] = $request->input('promoter_user_id', '');
        $params['isSalesmanPage'] = $request->input('isSalesmanPage', '');
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($authInfo['company_id'], $orderId);
        $result = [];
        if ($order) {
            /**
             * @var \OrdersBundle\Services\Orders\AbstractNormalOrder $orderService
             */
            $orderService = $this->getOrderServiceByOrderInfo($order);
            $result = $orderService->getOrderInfo($authInfo['company_id'], $order['order_id'], true, $from);
            if (!$result || !$result['orderInfo']) {
                return $this->response->array([]);
            }

            // 查询物流信息
            $orderDeliveryService = new OrderDeliveryService();
            $delivery_info = $orderDeliveryService->lists(['company_id' => $authInfo['company_id'], 'order_id' => $order['order_id']]);
            foreach ($delivery_info as $v) {
                $result['orderInfo']['delivery_info'][] = [
                    'delivery_code' => $v['delivery_code'],
                    'delivery_corp' => $v['delivery_corp'],
                    'delivery_corp_name' => $v['delivery_corp_name'],
                    'delivery_time' => $v['delivery_time'],
                ];
            }

            $userId = $result['orderInfo']['user_id'] ?? 0;
            $self_delivery_operator_id = $result['orderInfo']['self_delivery_operator_id'] ?? 0;
            $self_delivery_operator_ids = $request->input('self_delivery_operator_id',[]);
            $prescriptionOrderRandom = $request->input('prescription_order_random', '');
            if ($userId == $authInfo['user_id'] || in_array($self_delivery_operator_id,$self_delivery_operator_ids)) {
                $settingService = new SettingService();
                $pickupCodeSetting = $settingService->presalePickupcodeGet($authInfo['company_id']);
                $result['orderInfo']['pickupcode_status'] = $pickupCodeSetting['pickupcode_status'];
            } elseif ($params['promoter_user_id'] && $params['isSalesmanPage'] && ($order['promoter_user_id'] == $params['promoter_user_id'] or $order['salesman_id'] == $params['promoter_user_id'])){
                //搞不清楚  salesman_id  和 promoter_user_id 有什么区别，这里用 or 来判断
                $order['order_source'] =  'salesperson';
            } else if ($userId != $authInfo['user_id'] && $prescriptionOrderRandom) {
                // 验证扫码订单
                $random = app('redis')->get('dianwu_prescription_order_random:' . $order['order_id'] . $prescriptionOrderRandom);
                if (!$random) {
                    app('log')->info(':prescriptionOrderRandom:'.__FUNCTION__.__LINE__.'::' . json_encode($prescriptionOrderRandom));
                    // throw new ResourceException('二维码已失效');
                }
            } else {
                return $this->response->array([]);
            }
            $settingService = new SettingService();
            $pickupCodeSetting = $settingService->presalePickupcodeGet($authInfo['company_id']);
            $result['orderInfo']['pickupcode_status'] = $pickupCodeSetting['pickupcode_status'];

            $promoterUserId = 0;
            if(isset($order['promoter_user_id']) &&  $order['promoter_user_id']){
                $promoterUserId = $order['promoter_user_id'];
            }
            $result['orderInfo']['promoter_user_id'] = $promoterUserId;
            $result['orderInfo']['salespersonInfo'] = $this->getSalesPromoterInfo($promoterUserId,$result['orderInfo']);

            //PC、H5收银台界面会查询此接口。开通vip会员订单没有子订单数据
            if ($order['order_type'] != 'memberCard' && $order['order_type'] != 'service') {
                if (method_exists($orderService, 'orderRecombine')) {
                    $result = $orderService->orderRecombine($result); //订单售后数量重新计算
                }

                // 总部发货的商品订单详情分开显示
                $list = $logisticsList = [];
                $result['orderInfo']['is_split'] = false;
                foreach ($result['orderInfo']['items'] as $item) {
                    if ($item['is_logistics'] ?? false) {
                        $logisticsList[] = $item;
                    } else {
                        $list[] = $item;
                    }
                }

                if ($logisticsList && $list) {
                    $result['orderInfo']['is_split'] = true;
                }

                if (!$list) {
                    $result['orderInfo']['items'] = $logisticsList;
                    $result['orderInfo']['logistics_items'] = [];
                } else {
                    $result['orderInfo']['items'] = $list;
                    $result['orderInfo']['logistics_items'] = $logisticsList;
                }
            }
            // 是否有权限查看加密数据
            $datapassBlock = $request->get('x-datapass-block');
            if ($datapassBlock && isset($result['orderInfo']['receiver_mobile'])) {
                $result['orderInfo']['receiver_name'] = data_masking('truename', (string) $result['orderInfo']['receiver_name']);
                $result['orderInfo']['receiver_mobile'] = data_masking('mobile', (string) $result['orderInfo']['receiver_mobile']);
                $result['orderInfo']['receiver_address'] = data_masking('address', (string) $result['orderInfo']['receiver_address']);
            }
        } else {
            return $this->response->array([]);
        }

        // 计算促销优惠
        $result['orderInfo']['promotion_discount'] = 0;
        if (isset($result['orderInfo']['discount_info']) && is_array($result['orderInfo']['discount_info'])) {
            foreach ($result['orderInfo']['discount_info'] as $discountInfo) {
                if (in_array($discountInfo['type'], ['full_minus', 'full_discount', 'member_tag_targeted_promotion'])) {
                    $result['orderInfo']['promotion_discount'] += $discountInfo['discount_fee'];
                }
            }
        }

        if (!isset($result['orderInfo']['items'])) {
            return $this->response->array($result);
        }

        // 重新计算行商品总价，不含价格立减活动及会员价优惠
        foreach ($result['orderInfo']['items'] as $key => $item) {
            $result['orderInfo']['items'][$key]['item_fee_new'] = $item['total_fee']    //实付金额
                                                             + ($item['point_fee'] ?? 0)           //加上积分抵扣
                                                             + ($item['coupon_discount'] ?? 0)     //加上优惠券抵扣
                                                             + ($item['promotion_discount'] ?? 0); //加上促销优惠
            $result['orderInfo']['items'][$key]['market_fee'] = ($item['market_price'] ?: $item['price']) * $item['num'];
        }

        // 重新计算商品总价，不含价格立减活动及会员价优惠
        $result['orderInfo']['item_fee_new'] = $result['orderInfo']['total_fee']                  //实付金额
                                             - ($result['orderInfo']['freight_fee'] ?? 0)         //减去运费
                                             + ($result['orderInfo']['point_fee'] ?? 0)           //加上积分抵扣
                                             + ($result['orderInfo']['coupon_discount'] ?? 0)     //加上优惠券抵扣
                                             + ($result['orderInfo']['promotion_discount'] ?? 0); //加上促销优惠

        $result['offline_aftersales_is_open'] = $this->getOrdersSetting($authInfo['company_id'], 'offline_aftersales');
        if($invoice_list && $result['orderInfo']['freight_fee']){
            $orderInvoiceService = new OrderInvoiceService();
            if(!$orderInvoiceService->hasShippingFeeInvoice($result['orderInfo']['order_id'],$result['orderInfo']['company_id'])){
                $result['orderInfo']['items'][] = $orderService->getOrderShippingFeeLine($result['orderInfo']['freight_fee']);
            }
        }
        $result['orderInfo'] = $this->editInvoiceForOrder($result['orderInfo']);
        
        return $this->response->array($result);
    }
    public function editInvoiceForOrder($orderInfo){
        $orderId = $orderInfo['order_id'];
        $orderInvoiceService = new OrderInvoiceService();
        $invoiceInfo_list = $orderInvoiceService->getInvoiceList(['order_id' => $orderId,'invoice_status'=> ['pending','in_process','success','failed']], 1, 10000, ['id' => 'DESC'], true);
        $invoiceInfo = array_column($invoiceInfo_list['list'], null, 'order_id');
    
        $orderInfo['invoice_id'] = $invoiceInfo[$orderId]['id'] ?? 0;
        $orderInfo['invoice_info'] = $invoiceInfo[$orderId]['invoice_info'] ?? [];
        $settingService = new SettingService();
        $invoice_status = $settingService->checkoutInvoiceStatus($orderInfo);
        app('log')->info(':invoice_status:'.__FUNCTION__.__LINE__.':invoice_status:' . json_encode($invoice_status));
        $orderInfo['invoice_status'] = $invoice_status;
        $orderInfo['invoice_able'] = $invoice_status ? true : false;
        // $orderInfo['invoice_amount'] = $orderInfo['total_fee'] ?? 0;
        $orderInfo['invoice_amount'] = $orderInvoiceService->getInvoiceAmount($orderId,$orderInfo);
        // 根据运费设置


        return $orderInfo;
    }
    public function getSalesPromoterInfo($promoterUserId = 0,$params = []){
        //业务员 导购分享逻辑
        $distributorService = new DistributorService();
        $distributorInfo = $distributorService->getInfoSimple(['distributor_id' => $params['distributor_id'] ]);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":distributorInfo:". json_encode($distributorInfo));

        //导购ID信息处理
        if($promoterUserId && $distributorInfo['is_open_salesman']){
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
                app('log')->info(':brokerage:'.__FUNCTION__.__LINE__.':promoterUserId:' . json_encode($promoterUserId));
            }else{
                $promoterUserId = 0;
                $salespersonInfo = array('user_id'=>'','name'=>'','mobile'=>'');
            }

        }else{
            $promoterUserId = 0;
            $salespersonInfo = array('user_id'=>'','name'=>'','mobile'=>'');
        }
        return $salespersonInfo;

    }

    /**
     * @SWG\Get(
     *     path="/wxapp/order_new/{order_id}",
     *     summary="获取订单详情",
     *     tags={"订单"},
     *     description="获取订单详情",
     *     operationId="getOrderDetail",
     *     @SWG\Response(
     *        response=200,
     *        description="成功返回结构",
     *        @SWG\Schema(
     *          @SWG\Property(
     *             property="data",
     *             type="object",
     *             @SWG\Property(property="order_id", type="string", description="订单编号"),
     *             @SWG\Property(property="user_id", type="integer", description="会员id"),
     *             @SWG\Property(property="order_type", type="string", description="订单类型"),
     *             @SWG\Property(property="pay_type", type="string", description="支付方式"),
     *             @SWG\Property(property="point", type="integer", description="积分"),
     *             @SWG\Property(property="title", type="string", description="描述"),
     *             @SWG\Property(property="total_fee", type="integer", description="支付金额"),
     *             @SWG\Property(property="create_time", type="integer", description="创建时间"),
     *             @SWG\Property(property="payDate", type="string", description="支付时间有效期"),
     *             @SWG\Property(property="tradeId", type="integer", description="支付交易单号"),
     *             @SWG\Property(property="payStatus", type="string", description="支付状态"),
     *          ),
     *        ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getOrderDetailNew(Request $request, $order_id)
    {
        $orderId = $order_id;
        if (!$orderId) {
            throw new BadRequestHttpException('订单号必填');
        }
        $authInfo = $request->get('auth');
        $str = mb_substr($orderId, 0, 2);
        if ('CZ' == $str) {
            $depositTrade = new DepositTrade();
            $orderInfo = $depositTrade->getDepositTradeInfo($orderId);
            if (!$orderInfo) {
                throw new BadRequestHttpException('无此订单');
            }
            $result = [
                'order_id' => $orderInfo['deposit_trade_id'],
                'user_id' => $orderInfo['user_id'],
                'order_type' => 'recharge',
                'pay_type' => $orderInfo['pay_type'],
                'point' => 0,
                'title' => '充值' . round($orderInfo['money'] / 100, 2) . '元',
                'total_fee' => $orderInfo['money'],
                'create_time' => $orderInfo['time_start'],
                'payDate' => $orderInfo['time_expire'],
                'tradeId' => $orderInfo['transaction_id'],
                'payStatus' => $orderInfo['trade_status'] == 'SUCCESS' ? 'success' : 'fail',
            ];
        } else {
            if ('TD' == $str) {
                $tradeService = new TradeService();
                $tradeInfo = $tradeService->getInfoById(substr($orderId, 2));
                if (!$tradeInfo) {
                    throw new BadRequestHttpException('支付单不存在');
                }
                $orderId = $tradeInfo['order_id'];
            }

            $orderAssociationService = new OrderAssociationService();
            $order = $orderAssociationService->getOrder($authInfo['company_id'], $orderId);
            if (!$order) {
                throw new BadRequestHttpException('无此订单');
            }
            $orderService = $this->getOrderServiceByOrderInfo($order);
            $orderInfo = $orderService->getOrderInfo($authInfo['company_id'], $orderId);
            $result = [
                'order_id' => $orderId,
                'user_id' => $orderInfo['orderInfo']['user_id'],
                'order_type' => $orderInfo['orderInfo']['order_type'],
                'pay_type' => $orderInfo['orderInfo']['pay_type'],
                'point' => $orderInfo['orderInfo']['point'] ?? 0,
                'title' => $orderInfo['orderInfo']['title'],
                'total_fee' => $orderInfo['orderInfo']['total_fee'],
                'create_time' => $orderInfo['orderInfo']['create_time'],
                'payDate' => $orderInfo['tradeInfo']['timeExpire'] ?? '',
                'tradeId' => $orderInfo['tradeInfo']['tradeId'] ?? '',
                'payStatus' => isset($orderInfo['tradeInfo']['tradeState']) && $orderInfo['tradeInfo']['tradeState'] == 'SUCCESS' ? 'success' : 'fail',
            ];
        }
        $userId = $result['user_id'] ?? 0;
        if ($userId != $authInfo['user_id']) {
            return $this->response->array([]);
        }
        $invoiceRecord = $this->editInvoiceForOrder($orderInfo['orderInfo']);
        $result['orderInfo']['invoice_record'] = $invoiceRecord;

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/orders",
     *     summary="获取用户订单列表",
     *     tags={"订单"},
     *     description="获取用户订单列表",
     *     operationId="getOrderList",
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取门店列表的初始偏移位置，从1开始计数",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="order_id",
     *         in="query",
     *         description="订单号",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="手机号",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="order_type",
     *         in="query",
     *         description="订单类型",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="time_start_begin",
     *         in="query",
     *         description="查询开始时间",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="time_start_end",
     *         in="query",
     *         description="查询结束时间",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="is_rate",
     *         in="query",
     *         description="是否评价",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="order_id", type="string", example="3309653000180376", description="订单号"),
     *                           @SWG\Property(property="title", type="string", example="测试0119-2...", description="订单标题"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                           @SWG\Property(property="user_id", type="string", example="20376", description="用户id"),
     *                           @SWG\Property(property="mobile", type="string", example="13095920688", description="手机号"),
     *                           @SWG\Property(property="total_fee", type="string", example="2", description="订单金额，以分为单位"),
     *                           @SWG\Property(property="total_rebate", type="string", example="0", description="总分销金额，以分为单位"),
     *                           @SWG\Property(property="distributor_id", type="string", example="104", description="分销商id"),
     *                           @SWG\Property(property="order_status", type="string", example="DONE", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *                           @SWG\Property(property="order_source", type="string", example="member", description="订单来源。可选值有 member-用户自主下单;shop-商家代客下单"),
     *                           @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 normal:普通实体订单"),
     *                           @SWG\Property(property="auto_cancel_time", type="string", example="1611304246", description="订单自动取消时间"),
     *                           @SWG\Property(property="is_distribution", type="string", example="1", description="是否分销订单"),
     *                           @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *                           @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *                           @SWG\Property(property="delivery_corp", type="string", example="SF", description="快递公司"),
     *                           @SWG\Property(property="delivery_code", type="string", example="SF2021012216230001", description="快递单号"),
     *                           @SWG\Property(property="delivery_time", type="string", example="1611303860", description="发货时间"),
     *                           @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货"),
     *                           @SWG\Property(property="receiver_name", type="string", example="张三", description="收货人姓名"),
     *                           @SWG\Property(property="receiver_mobile", type="string", example="13095920688", description="收货人手机号"),
     *                           @SWG\Property(property="receiver_zip", type="string", example="101001", description="收货人邮编"),
     *                           @SWG\Property(property="receiver_state", type="string", example="北京市", description="收货人所在省份"),
     *                           @SWG\Property(property="receiver_city", type="string", example="北京市", description="收货人所在城市"),
     *                           @SWG\Property(property="receiver_district", type="string", example="东城", description="收货人所在地区"),
     *                           @SWG\Property(property="receiver_address", type="string", example="101", description="收货人详细地址"),
     *                           @SWG\Property(property="create_time", type="string", example="1611303646", description="订单创建时间"),
     *                           @SWG\Property(property="update_time", type="string", example="1611304538", description="订单更新时间"),
     *                           @SWG\Property(property="freight_fee", type="string", example="1", description="运费价格，以分为单位"),
     *                           @SWG\Property(property="item_fee", type="string", example="1", description="商品总金额，以分为单位"),
     *                           @SWG\Property(property="member_discount", type="string", example="0", description="会员折扣金额，以分为单位"),
     *                           @SWG\Property(property="coupon_discount", type="string", example="0", description="优惠券抵扣金额，以分为单位"),
     *                           @SWG\Property(property="coupon_discount_desc", type="string", example="", description="优惠券使用详情"),
     *                           @SWG\Property(property="member_discount_desc", type="string", example="", description="会员折扣使用详情"),
     *                           @SWG\Property(property="shop_id", type="string", example="0", description="门店id"),
     *                           @SWG\Property(property="receipt_type", type="string", example="logistics", description="收货方式。可选值有 logistics:物流;ziti:店铺自提"),
     *                           @SWG\Property(property="ziti_code", type="string", example="0", description="店铺自提码"),
     *                           @SWG\Property(property="ziti_status", type="string", example="NOTZITI", description="店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核"),
     *                           @SWG\Property(property="end_time", type="string", example="1611303892", description="订单完成时间"),
     *                           @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *                           @SWG\Property(property="cost_fee", type="string", example="10000", description="商品成本价，以分为单位"),
     *                           @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                           @SWG\Property(property="fee_rate", type="string", example="1", description="货币汇率"),
     *                           @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                           @SWG\Property(property="act_id", type="string", example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
     *                           @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单"),
     *                           @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
     *                           @SWG\Property(property="auto_finish_time", type="string", example="1611908660", description="订单自动完成时间"),
     *                           @SWG\Property(property="discount_fee", type="string", example="0", description="订单优惠金额，以分为单位"),
     *                           @SWG\Property(property="discount_info", type="string", example="0", description="订单优惠详情"),
     *                           @SWG\Property(property="point", type="string", example="0", description="商品总积分"),
     *                           @SWG\Property(property="pay_type", type="string", example="wxpay", description="支付方式"),
     *                           @SWG\Property(property="remark", type="string", example="", description="订单备注"),
     *                           @SWG\Property(property="third_params", type="string", example="", description="第三方特殊字段存储(DC2Type:json_array)"),
     *                           @SWG\Property(property="invoice", type="string", example="", description="发票信息(DC2Type:json_array)"),
     *                           @SWG\Property(property="send_point", type="string", example="1", description="是否分发积分0否 1是"),
     *                           @SWG\Property(property="step_paid_fee", type="string", example="0", description="分阶段付款已支付金额，以分为单位"),
     *                           @SWG\Property(property="delivery_corp_source", type="string", example="kuaidi100", description="快递代码来源"),
     *                           @SWG\Property(property="is_rate", type="string", example="1", description="是否评价"),
     *                           @SWG\Property(property="invoice_number", type="string", example="", description="发票号"),
     *                           @SWG\Property(property="is_invoiced", type="string", example="0", description="是否已开发票"),
     *                           @SWG\Property(property="is_online_order", type="string", example="1", description="是否为线上订单"),
     *                           @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
     *                           @SWG\Property(property="pay_status", type="string", example="PAYED", description="支付状态。可选值有 NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中"),
     *                           @SWG\Property(property="type", type="string", example="0", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                           @SWG\Property(property="identity_id", type="string", example="", description="身份证号码"),
     *                           @SWG\Property(property="identity_name", type="string", example="", description="身份证姓名"),
     *                           @SWG\Property(property="total_tax", type="string", example="0", description="总税费"),
     *                           @SWG\Property(property="audit_status", type="string", example="processing", description="跨境订单审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                           @SWG\Property(property="audit_msg", type="string", example="正在审核订单", description="审核意见"),
     *                           @SWG\Property(property="taxable_fee", type="string", example="0", description="计税总价，以分为单位"),
     *                           @SWG\Property(property="point_fee", type="string", example="0", description="积分抵扣时分摊的积分的金额，以分为单位"),
     *                           @SWG\Property(property="point_use", type="string", example="0", description="积分抵扣使用的积分数"),
     *                           @SWG\Property(property="get_point_type", type="string", example="1", description="获取积分类型，0 老订单按订单完成时送,1 新订单按下单时计算送"),
     *                           @SWG\Property(property="get_points", type="string", example="2", description="订单获取积分"),
     *                           @SWG\Property(property="bonus_points", type="string", example="0", description="购物赠送积分"),
     *                           @SWG\Property(property="is_shopscreen", type="string", example="0", description="是否门店订单"),
     *                           @SWG\Property(property="is_logistics", type="string", example="0", description="门店缺货商品总部快递发货"),
     *                           @SWG\Property(property="is_profitsharing", type="string", example="1", description="是否分账订单 1不分账 2分账"),
     *                           @SWG\Property(property="profitsharing_status", type="string", example="1", description="分账状态 1未分账 2已分账"),
     *                           @SWG\Property(property="profitsharing_rate", type="string", example="0", description="分账费率"),
     *                           @SWG\Property(property="order_auto_close_aftersales_time", type="string", example="1611390292", description="自动关闭售后时间"),
     *                           @SWG\Property(property="pack", type="string", example="", description="包装"),
     *                           @SWG\Property(property="bind_auth_code", type="string", example="", description="订单订单验证码"),
     *                           @SWG\Property(property="freight_type", type="string", example="cash", description="运费类型-用于积分商城 cash:现金 point:积分"),
     *                           @SWG\Property(property="item_point", type="string", example="0", description="商品积分"),
     *                           @SWG\Property(property="extra_points", type="string", example="0", description="订单获取额外积分"),
     *                           @SWG\Property(property="uppoint_use", type="string", example="0", description="积分抵扣使用的积分升值数"),
     *                           @SWG\Property(property="order_status_msg", type="string", example="已完成", description="订单状态描述"),
     *                           @SWG\Property(property="order_status_des", type="string", example="DONE", description="订单状态"),
     *                           @SWG\Property(property="source_name", type="string", example="-", description=""),
     *                          @SWG\Property(property="distributor_info", type="object", description="",
     *                                           @SWG\Property(property="distributor_id", type="string", example="104", description="分销商id"),
     *                                           @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                                           @SWG\Property(property="mobile", type="string", example="17621612312", description="手机号"),
     *                                           @SWG\Property(property="address", type="string", example="宜山路700号", description="店铺地址"),
     *                                           @SWG\Property(property="name", type="string", example="普天信息产业园", description="店铺名称"),
     *                                           @SWG\Property(property="created", type="string", example="1606292438", description="创建时间"),
     *                                           @SWG\Property(property="updated", type="string", example="1609999278", description="最后修改时间"),
     *                                           @SWG\Property(property="is_valid", type="string", example="true", description="店铺是否有效"),
     *                                           @SWG\Property(property="province", type="string", example="上海市", description="省份"),
     *                                           @SWG\Property(property="city", type="string", example="上海市", description="城市"),
     *                                           @SWG\Property(property="area", type="string", example="徐汇区", description="区域"),
     *                                           @SWG\Property(property="regions_id", type="array", description="",
     *                                             @SWG\Items(
     *                                                type="string", example="310000", description=""
     *                                             ),
     *                                           ),
     *                                           @SWG\Property(property="regions", type="array", description="",
     *                                             @SWG\Items(
     *                                                type="string", example="上海市", description=""
     *                                             ),
     *                                           ),
     *                                           @SWG\Property(property="contact", type="string", example="张", description="联系人名称"),
     *                                           @SWG\Property(property="child_count", type="string", example="0", description=""),
     *                                           @SWG\Property(property="shop_id", type="string", example="0", description="门店id"),
     *                                           @SWG\Property(property="is_default", type="string", example="0", description="是否默认"),
     *                                           @SWG\Property(property="is_ziti", type="string", example="1", description="是否支持自提"),
     *                                           @SWG\Property(property="lng", type="string", example="121.417537", description="腾讯地图纬度"),
     *                                           @SWG\Property(property="lat", type="string", example="31.176567", description="腾讯地图经度"),
     *                                           @SWG\Property(property="hour", type="string", example="08:00-21:00", description="营业时间"),
     *                                           @SWG\Property(property="auto_sync_goods", type="string", example="1", description="自动同步总部商品"),
     *                                           @SWG\Property(property="logo", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/Hw4SsicubkrdnwoLMY38PLNULch2rPgsGb4NCVCC4EGa8EFs2MPCSbzJolznV64F0L5VetQvyE2ZrCcIb1ZALEA/0?wx_fmt=png", description="店铺logo"),
     *                                           @SWG\Property(property="banner", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/Hw4Ssicubkre4SsqeJKcShn3CyCQc3L52zM5jHpUo4hkicCiby1qmz5g5XpAIPg5JMFxgNcHUoCtg9vLT7QbzibP2w/0?wx_fmt=png", description="店铺banner"),
     *                                           @SWG\Property(property="is_audit_goods", type="string", example="1", description="是否审核店铺商品"),
     *                                           @SWG\Property(property="is_delivery", type="string", example="1", description="是否支持配送"),
     *                                           @SWG\Property(property="shop_code", type="string", example="", description="店铺号"),
     *                                           @SWG\Property(property="review_status", type="string", example="0", description="入驻审核状态，0未审核，1已审核"),
     *                                           @SWG\Property(property="source_from", type="string", example="1", description="店铺来源，1管理端添加，2小程序申请入驻"),
     *                                           @SWG\Property(property="distributor_self", type="string", example="0", description="是否是总店配置"),
     *                                           @SWG\Property(property="is_distributor", type="string", example="1", description="是否是主店铺"),
     *                                           @SWG\Property(property="contract_phone", type="string", example="0", description="其他联系方式"),
     *                                           @SWG\Property(property="is_domestic", type="string", example="1", description="是否是中国国内门店 1:国内(包含港澳台),2:非国内	"),
     *                                           @SWG\Property(property="is_direct_store", type="string", example="1", description="是否为直营店 1:直营店,2:非直营店"),
     *                                           @SWG\Property(property="wechat_work_department_id", type="string", example="0", description="企业微信的部门ID"),
     *                                           @SWG\Property(property="regionauth_id", type="string", example="1", description="区域id"),
     *                                           @SWG\Property(property="is_open", type="string", example="true", description="是否开启分账"),
     *                                           @SWG\Property(property="rate", type="string", example="1000", description="分账平台服务费率"),
     *                          ),
     *                           @SWG\Property(property="create_date", type="string", example="2021-01-22 16:20:46", description="创建时间"),
     *                           @SWG\Property(property="items", type="array", description="",
     *                             @SWG\Items(
     *                                                                           @SWG\Property(property="id", type="string", example="8873", description="ID"),
     *                                                                           @SWG\Property(property="order_id", type="string", example="3309653000180376", description="订单号"),
     *                                                                           @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                                                                           @SWG\Property(property="user_id", type="string", example="20376", description="用户id"),
     *                                                                           @SWG\Property(property="act_id", type="string", example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
     *                                                                           @SWG\Property(property="item_id", type="string", example="5437", description="商品id"),
     *                                                                           @SWG\Property(property="item_bn", type="string", example="dsaksak1191", description="商品编码"),
     *                                                                           @SWG\Property(property="item_name", type="string", example="测试0119-2", description="商品名称"),
     *                                                                           @SWG\Property(property="pic", type="string", example="https://bbctest.aixue7.com/image/1/2021/01/06/e6d2a893739b6640ebb2c86c15ce29786JByhCPBiTPxzjMr8s9STXD01oSb7zJk", description="商品图片"),
     *                                                                           @SWG\Property(property="num", type="integer", example="1", description="购买商品数量"),
     *                                                                           @SWG\Property(property="price", type="integer", example="1", description="单价，以分为单位"),
     *                                                                           @SWG\Property(property="total_fee", type="integer", example="1", description="订单金额，以分为单位"),
     *                                                                           @SWG\Property(property="templates_id", type="integer", example="105", description="运费模板id"),
     *                                                                           @SWG\Property(property="rebate", type="integer", example="0", description="单个分销金额，以分为单位"),
     *                                                                           @SWG\Property(property="total_rebate", type="integer", example="0", description="总分销金额，以分为单位"),
     *                                                                           @SWG\Property(property="item_fee", type="integer", example="1", description="商品总金额，以分为单位"),
     *                                                                           @SWG\Property(property="cost_fee", type="integer", example="10000", description="商品成本价，以分为单位"),
     *                                                                           @SWG\Property(property="item_unit", type="string", example="", description="商品计量单位"),
     *                                                                           @SWG\Property(property="member_discount", type="integer", example="0", description="会员折扣金额，以分为单位"),
     *                                                                           @SWG\Property(property="coupon_discount", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *                                                                           @SWG\Property(property="discount_fee", type="integer", example="0", description="订单优惠金额，以分为单位"),
     *                                                                           @SWG\Property(property="discount_info", type="string", description=""),
     *                                                                           @SWG\Property(property="shop_id", type="string", example="0", description="门店id"),
     *                                                                           @SWG\Property(property="is_total_store", type="string", example="1", description="是否是总部库存(true:总部库存，false:店铺库存)"),
     *                                                                           @SWG\Property(property="distributor_id", type="string", example="104", description="分销商id"),
     *                                                                           @SWG\Property(property="create_time", type="integer", example="1611303646", description="订单创建时间"),
     *                                                                           @SWG\Property(property="update_time", type="integer", example="1611304538", description="订单更新时间"),
     *                                                                           @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *                                                                           @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *                                                                           @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
     *                                                                           @SWG\Property(property="delivery_time", type="string", example="", description="发货时间"),
     *                                                                           @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货"),
     *                                                                           @SWG\Property(property="aftersales_status", type="string", example="", description="售后状态。可选值有 WAIT_SELLER_AGREE 0 等待商家处理;WAIT_BUYER_RETURN_GOODS 1 商家接受申请，等待消费者回寄;WAIT_SELLER_CONFIRM_GOODS 2 消费者回寄，等待商家收货确认;SELLER_REFUSE_BUYER 3 售后驳回;SELLER_SEND_GOODS 4 卖家重新发货 换货完成;REFUND_SUCCESS 5 退款成功;REFUND_CLOSED 6 退款关闭;CLOSED 7 售后关闭"),
     *                                                                           @SWG\Property(property="refunded_fee", type="integer", example="0", description="退款金额，以分为单位"),
     *                                                                           @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                                                                           @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *                                                                           @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                                                                           @SWG\Property(property="cny_fee", type="integer", example="1", description=""),
     *                                                                           @SWG\Property(property="item_point", type="integer", example="0", description="商品积分"),
     *                                                                           @SWG\Property(property="point", type="integer", example="0", description="商品总积分"),
     *                                                                           @SWG\Property(property="item_spec_desc", type="string", example="", description="商品规格描述"),
     *                                                                           @SWG\Property(property="order_item_type", type="string", example="normal", description="订单商品类型,normal:正常商品，gift: 赠品, plus_buy: 加价购商品"),
     *                                                                           @SWG\Property(property="volume", type="integer", example="0", description="商品体积"),
     *                                                                           @SWG\Property(property="weight", type="integer", example="0", description="商品重量"),
     *                                                                           @SWG\Property(property="is_rate", type="string", example="1", description="是否评价"),
     *                                                                           @SWG\Property(property="auto_close_aftersales_time", type="integer", example="1611390292", description="自动关闭售后时间"),
     *                                                                           @SWG\Property(property="share_points", type="integer", example="0", description="积分抵扣时分摊的积分值"),
     *                                                                           @SWG\Property(property="point_fee", type="integer", example="0", description="积分抵扣时分摊的积分的金额，以分为单位"),
     *                                                                           @SWG\Property(property="is_logistics", type="string", example="", description="门店缺货商品总部快递发货"),
     *                                                                           @SWG\Property(property="delivery_item_num", type="integer", example="1", description="发货单发货数量"),
     *                                                                           @SWG\Property(property="get_points", type="integer", example="1", description="订单获取积分"),
     *                             ),
     *                           ),
     *                           @SWG\Property(property="distributor_name", type="string", example="普天信息产业园", description=""),
     *                           @SWG\Property(property="delivery_type", type="string", example="new", description=""),
     *                           @SWG\Property(property="orders_delivery_id", type="string", example="373", description="orders_delivery_id"),
     *                           @SWG\Property(property="is_all_delivery", type="string", example="1", description=""),
     *                           @SWG\Property(property="delivery_corp_name", type="string", example="顺丰快递", description="快递公司名称"),
     *                           @SWG\Property(property="is_split", type="string", example="", description=""),
     *                 ),
     *               ),
     *              @SWG\Property(property="pager", type="object", description="",
     *                   @SWG\Property(property="count", type="integer", example="6", description="总记录条数"),
     *                   @SWG\Property(property="page_no", type="integer", example="1", description="页码"),
     *                   @SWG\Property(property="page_size", type="integer", example="10", description="每页记录数"),
     *              ),
     *               @SWG\Property(property="rate_status", type="string", example="1", description=""),
     *            ),

     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones")))
     * )
     */
    public function getOrderList(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['user_id']) {
            $result['list'] = [];
            $result['total_count'] = [];
            return $this->response->array($result);
        }

        $validator = app('validator')->make($request->all(), [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);
        $params = $request->input();
        $filter = [];
        if (isset($params['time_start_begin'])) {
            $filter['create_time|gte'] = $params['time_start_begin'];
            $filter['create_time|lte'] = $params['time_start_end'];
        }
        if (isset($params['order_id'])) {
            $filter['order_id'] = $params['order_id'];
        }
        if (isset($params['mobile'])) {
            $filter['mobile'] = $params['mobile'];
        }
        if (isset($params['activity_id'])) {
            $filter['act_id'] = $params['activity_id'];
        }

        $filter['order_type'] = isset($params['order_type']) ? $params['order_type'] : 'service';
        $filter['invoice_list'] =  $params['invoice_list'] ?? 0;

        // 判断是否助力
        if ($filter['order_type'] === 'bargain') {
            $params['order_class'] = 'bargain';
        }
        if (isset($params['order_class']) && $params['order_class'] == 'employee_purchase') {
            $filter['order_class'] = $params['order_class'];
        } elseif (!in_array($filter['order_type'], ['bargain'])) {
            $filter['order_class|in'] = ['normal', 'groups', 'seckill', 'shopguide', 'shopadmin', 'bargain', 'pointsmall', ExcardNormalOrderService::CLASS_NAME, 'drug'];
        }

        if (isset($params['order_status']) && $filter['order_type'] != 'service') {
            $filter['order_status|neq'] = 'NOTPAY';
        } elseif (isset($params['order_status']) && $filter['order_type'] == 'service') {
            $filter['order_status'] = 'DONE';
        }

        if (isset($params['delivery_status']) && $params['delivery_status']) {
            $filter['delivery_status'] = $params['delivery_status'];
        }

        if (isset($params['self_delivery_status']) && $params['self_delivery_status']) {
            $filter['self_delivery_status'] = $params['self_delivery_status'];
        }

        if (isset($params['self_delivery_operator_id']) && $params['self_delivery_operator_id']) {
            $filter['self_delivery_operator_id'] = $params['self_delivery_operator_id'];
        }

        if (isset($params['status'])) {
            unset($filter['delivery_status'], $filter['order_status|neq']);
            $status = isset($params['status']) ? $params['status'] : 0;
            switch ($status) {
                case 1:    //待发货 待收货
                    $filter['order_status|in'] = ['PAYED', 'WAIT_BUYER_CONFIRM'];
                    $filter['ziti_status'] = 'NOTZITI';
                    break;
                case 2:  //之前是待收货，之后此项被废弃
                    $filter['order_status'] = 'WAIT_BUYER_CONFIRM';
                    $filter['delivery_status'] = 'DONE';
                    break;
                case 3:  //已完成
                    $filter['order_status'] = 'DONE';
                    $filter['delivery_status'] = 'DONE';
                    $filter['ziti_status'] = 'DONE';
                    break;
                case 4:  //待自提
                    $filter['order_status'] = 'PAYED';
                    $filter['ziti_status'] = 'PENDING';
                    break;
                case 5:  //待付款
                    $filter['order_status'] = 'NOTPAY';
                    // $filter['auto_cancel_time|gt'] = time();// 放到_filter方法了
                    break;
                case 6:  //待发货
                    $filter['order_status'] = 'PAYED';
                    $filter['ziti_status'] = 'NOTZITI';
                    break;
                case 7:  //待评价
                    $filter['order_status'] = 'DONE';
                    $filter['is_rate'] = $request->input('is_rate') ?? 0;
                    break;
            }
        }

        if ($request->input('is_distribution') != '') {
            $filter['is_distribution'] = true;
        }
        if (isset($filter['is_distribution']) && $filter['is_distribution']) {
            $distributorService = new DistributorService();
            $distributorFilter = [
                'mobile' => $authInfo['mobile'],
                'company_id' => $authInfo['company_id'],
            ];
            $distributorInfo = $distributorService->getInfo($distributorFilter);
            $filter['distributor_id'] = $distributorInfo['distributor_id'];
        }
        $filter['company_id'] = $authInfo['company_id'];

        if(isset($params['distributor_id']) && $params['distributor_id']){
            $filter['distributor_id'] = $params['distributor_id'];
        }

        if (!isset($filter['distributor_id']) && !isset($params['self_delivery_operator_id'])) {
            $filter['user_id'] = $authInfo['user_id'];
            if (!$authInfo['user_id']) {
                return $this->response->array([]);
                // throw new BadRequestHttpException('您还不是会员，无法查看订单！');
            }
        }
        if(isset($params['promoter_user_id']) &&  $params['promoter_user_id']
        && isset($params['isSalesmanPage']) && $params['isSalesmanPage']){
            unset($filter['user_id']);
            $filter['salesman_id'] =  $params['promoter_user_id'];
            $filter['order_source'] =  'salesperson';
        }

        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 50);
        $from = $request->input('from', 'front_list');

        $orderService = $this->getOrderService($filter['order_type']);
        $orderBy = ['create_time' => 'DESC'];
        $result = $orderService->getOrderList($filter, $page, $limit, $orderBy, true, $from);
        // 获取订单的invoice_id
        $orderIds = array_column($result['list'], 'order_id');
        $orderInvoiceService = new OrderInvoiceService();

        if($orderIds){
            $invoiceInfo_data = $orderInvoiceService->getInvoiceList(['order_id' => $orderIds,'invoice_status'=> ['pending','in_process','success','failed']], 1, 10000, ['id' => 'DESC'], true);
            $invoiceInfo = array_column($invoiceInfo_data['list'], null, 'order_id');
        }
        
        foreach ($result['list'] as $key => $value) {
            $result['list'][$key]['invoice_info'] = $invoiceInfo[$value['order_id']] ?? [];
            $result['list'][$key]['invoice_amount'] = $orderInvoiceService->getInvoiceAmount($value['order_id'], $value) ?? 0; //$value['total_fee'] ?? 0;
            $settingService = new SettingService();
            $invoice_status = $settingService->checkoutInvoiceStatus($value);
            app('log')->info(':invoice_status:'.__FUNCTION__.__LINE__.':invoice_status:' . json_encode($invoice_status));
            $result['list'][$key]['invoice_able'] = $invoice_status ? true : false;
            $result['list'][$key]['invoice_id'] = $invoice_status ? ($invoiceInfo[$value['order_id']]['id'] ?? 0) : 0;//todo
            
        }
        foreach ($result['list'] as $key => $value) {
            $result['list'][$key]['is_split'] = false;
            if (($value['is_logistics'] ?? false) && $value['receipt_type'] == 'ziti') {
                $result['list'][$key]['is_split'] = true;
            }

            // 计算促销优惠
            $value['promotion_discount'] = 0;
            if (isset($value['discount_info'])) {
                if (!is_array($value['discount_info'])) {
                    $value['discount_info'] = json_decode($value['discount_info'], true);
                }
                if (is_array($value['discount_info'])) {
                    foreach ($value['discount_info'] as $discountInfo) {
                        if (in_array($discountInfo['type'], ['full_minus', 'full_discount', 'member_tag_targeted_promotion'])) {
                            $value['promotion_discount'] += $discountInfo['discount_fee'];
                        }
                    }
                }
            }

            // 重新计算行商品总价，不含价格立减活动及会员价优惠
            foreach ($value['items'] as $k => $item) {
                $result['list'][$key]['items'][$k]['item_fee_new'] = $item['total_fee']    //实付金额
                                                                + ($item['point_fee'] ?? 0)           //加上积分抵扣
                                                                + ($item['coupon_discount'] ?? 0)     //加上优惠券抵扣
                                                                + ($item['promotion_discount'] ?? 0); //加上促销优惠
                $result['list'][$key]['items'][$k]['market_fee'] = ($item['market_price'] ?: $item['price']) * $item['num'];
            }

            // 重新计算商品总价，不含价格立减活动及会员价优惠
            $result['list'][$key]['item_fee_new'] = $value['total_fee']                  //实付金额
                                               - ($value['freight_fee'] ?? 0)         //减去运费
                                               + ($value['point_fee'] ?? 0)           //加上积分抵扣
                                               + ($value['coupon_discount'] ?? 0)     //加上优惠券抵扣
                                               + ($value['promotion_discount'] ?? 0); //加上促销优惠
        }

        $result['rate_status'] = $this->getGoodsRateSettingStatus($filter['company_id']);

        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/wxapp/groupOrders",
     *     summary="获取用户拼团订单列表",
     *     tags={"订单"},
     *     description="获取用户拼团订单列表",
     *     operationId="getGroupOrderList",
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取门店列表的初始偏移位置，从1开始计数",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="team_status",
     *         in="query",
     *         description="不传或者空查询全部,1 进行中, 2 成功, 3 失败",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="total_count", type="integer", description="总记录条数"),
     *                 @SWG\Property(
     *                    property="list",
     *                    type="array",
     *                    @SWG\Items(
     *                       @SWG\Property(property="act_id", type="integer", description="活动ID号"),
     *                       @SWG\Property(property="begin_time", type="integer", description="开团时间"),
     *                       @SWG\Property(property="company_id", type="integer", description="公司id"),
     *                       @SWG\Property(property="created", type="integer", description="创建时间"),
     *                       @SWG\Property(property="disabled", type="integer", description="是否禁用 true=禁用,false=启用"),
     *                       @SWG\Property(property="end_time", type="integer", description="结束时间(根据成团时效和活动结束时间算出来的)"),
     *                       @SWG\Property(property="group_goods_type", type="string", description="团购活动商品类型"),
     *                       @SWG\Property(property="head_mid", type="integer", description="团长会员ID"),
     *                       @SWG\Property(property="id", type="integer", description="id"),
     *                       @SWG\Property(property="itemId", type="integer", description="商品id"),
     *                       @SWG\Property(property="itemName", type="string", description="商品名称"),
     *                       @SWG\Property(property="join_person_num", type="integer", description="参与人数"),
     *                       @SWG\Property(
     *                           property="member_info",
     *                           type="object",
     *                           description="会员信息",
     *                           @SWG\Property(property="headimgurl", type="string", description="会员头像"),
     *                           @SWG\Property(property="nickname", type="string", description="会员昵称"),
     *                       ),
     *                       @SWG\Property(
     *                           property="member_list",
     *                           type="array",
     *                           description="拼团会员信息",
     *                           @SWG\Items(
     *                               @SWG\Property(property="act_id", type="integer", description="活动ID号"),
     *                               @SWG\Property(property="company_id", type="integer", description="公司id"),
     *                               @SWG\Property(property="disabled", type="integer", description="是否禁用 true=禁用,false=启用"),
     *                               @SWG\Property(property="group_goods_type", type="string", description="团购活动商品类型"),
     *                               @SWG\Property(property="id", type="integer", description="id"),
     *                               @SWG\Property(property="join_time", type="integer", description="参团时间"),
     *                               @SWG\Property(property="member_id", type="string", description="会员id"),
     *                               @SWG\Property(
     *                                   property="member_info",
     *                                   type="object",
     *                                   description="会员信息",
     *                                   @SWG\Property(property="headimgurl", type="string", description="会员头像"),
     *                                   @SWG\Property(property="nickname", type="string", description="会员昵称"),
     *                               ),
     *                               @SWG\Property(property="order_id", type="string", description="订单号"),
     *                               @SWG\Property(property="team_id", type="string", description="团id号"),
     *                           ),
     *                       ),
     *                       @SWG\Property(property="order_id", type="string", description="订单号"),
     *                       @SWG\Property(property="person_num", type="integer", description="拼团人数"),
     *                       @SWG\Property(property="pics", type="string", description="商品图片"),
     *                       @SWG\Property(property="price", type="integer", description="拼团价格"),
     *                       @SWG\Property(property="team_id", type="string", description="团id号"),
     *                       @SWG\Property(property="team_status", type="integer", description="状态:1.进行中2.成功3.失败"),
     *                       @SWG\Property(property="updated", type="integer", description="修改时间"),
     *                    ),
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getGroupOrderList(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['user_id']) {
            $result['list'] = [];
            $result['total_count'] = [];
            return $this->response->array($result);
        }

        $validator = app('validator')->make($request->all(), [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);
        $params = $request->input();
        $filter['m.member_id'] = $authInfo['user_id'];
        $filter['p.company_id'] = $authInfo['company_id'];
        $filter['m.disabled'] = false;
        if (isset($params['team_status'])) {
            switch ($params['team_status']) {
                case 1:
                    $filter['p.team_status'] = 1;
                    break;
                case 2:
                    $filter['p.team_status'] = 2;
                    break;
                case 3:
                    $filter['p.team_status'] = 3;
                    break;
                default:
                    break;
            }
        }
        if (isset($params['group_goods_type'])) {
            $filter['p.group_goods_type'] = $params['group_goods_type'];
        } else {
            $filter['p.group_goods_type'] = 'services';
        }
        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 50);
        $promotionGroupsTeamService = new PromotionGroupsTeamService();

        $result = $promotionGroupsTeamService->getGroupsTeamListByUser($filter, $page, $limit);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/groupOrders/{team_id}",
     *     summary="获取用户拼团订单详情",
     *     tags={"订单"},
     *     description="获取用户拼团订单详情",
     *     operationId="getGroupOrderDetail",
     *     @SWG\Parameter(
     *         name="teamId",
     *         in="path",
     *         required=true,
     *         type="string",
     *         description="拼团id",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                      property="activity_info",
     *                      type="object",
     *                      description="拼团活动信息",
     *                      @SWG\Property(property="act_name", type="string", description="活动名称"),
     *                      @SWG\Property(property="act_price", type="integer", description="活动价格 单位分"),
     *                      @SWG\Property(property="begin_time", type="integer", description="开始时间"),
     *                      @SWG\Property(property="company_id", type="integer", description="公司id"),
     *                      @SWG\Property(property="created", type="integer", description="创建时间"),
     *                      @SWG\Property(property="disabled", type="string", description="是否禁用 true=禁用,false=启用"),
     *                      @SWG\Property(property="end_time", type="integer", description="结束时间"),
     *                      @SWG\Property(property="free_post", type="integer", description="是否包邮"),
     *                      @SWG\Property(property="goods_id", type="integer", description="商品ID"),
     *                      @SWG\Property(property="group_goods_type", type="string", description="团购活动商品类型"),
     *                      @SWG\Property(property="groups_activity_id", type="integer", description="活动ID"),
     *                      @SWG\Property(property="itemId", type="integer", description="skuid"),
     *                      @SWG\Property(property="itemName", type="string", description="商品名称"),
     *                      @SWG\Property(property="last_seconds", type="integer", description=""),
     *                      @SWG\Property(property="limit_buy_num", type="integer", description="限买数量"),
     *                      @SWG\Property(property="limit_time", type="integer", description="成团时效(单位时)"),
     *                      @SWG\Property(property="over_time", type="integer", description="拼团活动结束时间"),
     *                      @SWG\Property(property="person_num", type="integer", description="拼团人数"),
     *                      @SWG\Property(
     *                          property="pics",
     *                          type="array",
     *                          description="活动封面",
     *                          @SWG\Items(
     *                            @SWG\Property(property="#0", type="string", description="图片地址链接 数组下标为数字0开始"),
     *                          )
     *                      ),
     *                      @SWG\Property(property="price", type="integer", description="活动价格"),
     *                      @SWG\Property(property="remaining_time", type="integer", description=""),
     *                      @SWG\Property(property="rig_up", type="integer", description="是否展示开团列表"),
     *                      @SWG\Property(property="robot", type="integer", description="是否成团机器人"),
     *                      @SWG\Property(property="share_desc", type="string", description="分享描述"),
     *                      @SWG\Property(property="shop_id", type="integer", description="下单门店"),
     *                      @SWG\Property(property="show_status", type="integer", description=""),
     *                      @SWG\Property(property="store", type="integer", description="拼团库存"),
     *                      @SWG\Property(property="updated", type="integer", description="更新时间"),
     *                      @SWG\Property(property="status", type="integer", description="活动的状态【null 未知错误】【1 未开始】【2 正在进行】【3 已结束】"),
     *                 ),
     *                 @SWG\Property(
     *                      property="member_list",
     *                      type="object",
     *                      description="拼团会员信息",
     *                      @SWG\Property(property="total_count", type="integer", description="参团会员总数"),
     *                      @SWG\Property(
     *                          property="list",
     *                          type="array",
     *                          description="会员信息",
     *                          @SWG\Items(
     *                               @SWG\Property(property="act_id", type="integer", description="活动ID号"),
     *                               @SWG\Property(property="company_id", type="integer", description="公司id"),
     *                               @SWG\Property(property="disabled", type="integer", description="是否禁用 true=禁用,false=启用"),
     *                               @SWG\Property(property="group_goods_type", type="string", description="团购活动商品类型"),
     *                               @SWG\Property(property="id", type="integer", description="id"),
     *                               @SWG\Property(property="join_time", type="integer", description="参团时间"),
     *                               @SWG\Property(property="member_id", type="string", description="会员id"),
     *                               @SWG\Property(
     *                                   property="member_info",
     *                                   type="object",
     *                                   description="会员信息",
     *                                   @SWG\Property(property="headimgurl", type="string", description="会员头像"),
     *                                   @SWG\Property(property="nickname", type="string", description="会员昵称"),
     *                               ),
     *                               @SWG\Property(property="order_id", type="string", description="订单号"),
     *                               @SWG\Property(property="team_id", type="string", description="团id号"),
     *                          ),
     *                      ),
     *                 ),
     *                 @SWG\Property(
     *                    property="team_info",
     *                    type="object",
     *                    description="参团信息",
     *                    @SWG\Property(property="act_id", type="integer", description="活动ID号"),
     *                    @SWG\Property(property="begin_time", type="integer", description="开团时间"),
     *                    @SWG\Property(property="company_id", type="integer", description="公司ID"),
     *                    @SWG\Property(property="created", type="integer", description="创建时间"),
     *                    @SWG\Property(property="disabled", type="integer", description="是否禁用 true=禁用,false=启用"),
     *                    @SWG\Property(property="end_time", type="integer", description="结束时间(根据成团时效和活动结束时间算出来的)"),
     *                    @SWG\Property(property="group_goods_type", type="integer", description="团购活动商品类型"),
     *                    @SWG\Property(property="head_mid", type="integer", description="团长会员ID"),
     *                    @SWG\Property(property="id", type="integer", description="id"),
     *                    @SWG\Property(property="join_person_num", type="integer", description="参与人数"),
     *                    @SWG\Property(property="team_id", type="integer", description="团id号"),
     *                    @SWG\Property(property="team_status", type="integer", description="状态:1.进行中2.成功3.失败"),
     *                    @SWG\Property(property="updated", type="integer", description="更新时间"),
     *                    @SWG\Property(property="status", type="integer", description="拼团的状态【null 未知错误】【1 未开始】【2 正在进行】【3 已结束】"),
     *                    @SWG\Property(property="progress", type="integer", description="拼团的进度【null 未知错误】【1 未开始】【2 正在拼团】【3 拼团成功】【4 拼团失败】"),
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getGroupOrderDetail($teamId)
    {
        $filter['team_id'] = $teamId;

        $promotionGroupsTeamService = new PromotionGroupsTeamService();
        $result = $promotionGroupsTeamService->getGroupsTeamDetailByUser($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/order/count",
     *     summary="统计订单数量和权益核销数量",
     *     tags={"订单"},
     *     description="统计订单数量和权益核销数量",
     *     operationId="countOrderAndRightsLog",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="rightsLogTotal", type="integer", description="权益核销数量"),
     *                    @SWG\Property(property="rightsTotal", type="integer", description="权益数量"),
     *                    @SWG\Property(property="orderTotal", type="integer", description="已完成订单数量"),
     *                    @SWG\Property(property="couponTotal", type="integer", description="优惠券数量"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function countOrderAndRightsLog(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['user_id']) {
            $result['rightsLogTotal'] = 0;
            $result['rightsTotal'] = 0;
            $result['orderTotal'] = 0;
            $result['couponTotal'] = 0;
            return $this->response->array($result);
        }

        $orderType = $request->input('order_type', 'service');
        $orderAssociationService = new OrderAssociationService();
        $filter = [
            'user_id' => $authInfo['user_id'],
            'company_id' => $authInfo['company_id']
        ];

        if ($orderType == 'service') {
            $rightsService = new RightsService(new TimesCardService());
            //权益核销数量
            $result['rightsLogTotal'] = $rightsService->countRightsLogNum($filter);
            //权益数量
            $result['rightsTotal'] = $rightsService->countRights($filter);
        }

        //已完成订单数量
        $filter['order_status'] = 'DONE';
        if ($orderType) {
            $filter['order_type'] = $orderType;
        }
        $result['orderTotal'] = $orderAssociationService->countOrderNum($filter);

        //优惠券数量
        $userDiscountService = new UserDiscountService();
        $filter = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $authInfo['user_id'],
            'end_date|gte' => time(),
            'status' => 1,
        ];
        $result['couponTotal'] = $userDiscountService->getUserDiscountCount($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/orderscount",
     *     summary="统计订单数量",
     *     tags={"订单"},
     *     description="统计订单数量",
     *     operationId="countOrders",
     *     @SWG\Parameter(
     *         name="order_type",
     *         in="query",
     *         description="订单类型",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="service_notpay", type="integer", description="未付款数量"),
     *                   @SWG\Property(property="service_payed", type="integer", description="已付款数量"),
     *                   @SWG\Property(property="normal_notpay_notdelivery", type="integer", description="未付款未发货"),
     *                   @SWG\Property(property="normal_payed_notdelivery", type="integer", description="已付款未发货 or 已付款已发货"),
     *                   @SWG\Property(property="normal_payed_daifahuo", type="integer", description="待发货"),
     *                   @SWG\Property(property="normal_payed_daishouhuo", type="integer", description="待收货"),
     *                   @SWG\Property(property="normal_payed_daiziti", type="integer", description="待自提订单"),
     *                   @SWG\Property(property="normal_not_rate", type="integer", description="待评价订单"),
     *                   @SWG\Property(property="aftersales", type="integer", description="待处理售后"),
     *                   @SWG\Property(property="rate_status", type="integer", description="")
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function countOrders(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['user_id']) {
            return $this->response->array([]);
        }

        $distributorId = null;
        if ($request->input('is_distribution')) {
            $distributorService = new DistributorService();
            $distributorFilter = [
                'mobile' => $authInfo['mobile'],
                'company_id' => $authInfo['company_id'],
            ];
            $result = $distributorService->getInfo($distributorFilter);
            $distributorId = $result['distributor_id'];
        }
        $actId = null;
        if ($request->input('activity_id')) {
            $actId = $request->input('activity_id');
        }

        $orderType = $request->input('order_type', 'service');
        $orderAssociationService = new OrderAssociationService();
        switch ($orderType) {
            case 'service':
                // 未付款
                $filter = [
                    'user_id' => $authInfo['user_id'],
                    'company_id' => $authInfo['company_id'],
                    'order_type' => 'service',
                    'order_status' => 'NOTPAY',
                ];
                $result['service_notpay'] = $orderAssociationService->countOrderNum($filter);
                // 已付款
                $filter = [
                    'user_id' => $authInfo['user_id'],
                    'company_id' => $authInfo['company_id'],
                    'order_type' => 'service',
                    'order_status' => 'DONE'
                ];
                $result['service_payed'] = $orderAssociationService->countOrderNum($filter);
                break;
            case 'normal':
                $order_class = $request->input('order_class');
                if (empty($order_class)) {
                    $order_class = ['normal', 'groups', 'seckill', 'shopguide', 'bargain', 'pointsmall', ExcardNormalOrderService::CLASS_NAME];
                }
                // 未付款未发货
                $orderService = $this->getOrderService('normal');
                $filter = [
                    'user_id' => $authInfo['user_id'],
                    'company_id' => $authInfo['company_id'],
                    'order_type' => 'normal',
                    'order_status' => 'NOTPAY',
                    // 'auto_cancel_time|gt' => time(),//这个条件放到_filter方法了
                    'order_class' => $order_class,
                ];
                if ($actId) {
                    $filter['act_id'] = $actId;
                }
                if ($distributorId) {
                    unset($filter['user_id']);
                    $filter['is_distribution'] = true;
                    $filter['distributor_id'] = $distributorId;
                    $result['normal_notpay_notdelivery'] = $orderService->countOrderNum($filter);
                } else {
                    $result['normal_notpay_notdelivery'] = $orderService->countOrderNum($filter);
                }

                // 已付款未发货 or 已付款已发货
                $filter = [
                    'user_id' => $authInfo['user_id'],
                    'company_id' => $authInfo['company_id'],
                    'order_type' => 'normal',
                    'ziti_status' => 'NOTZITI',
                    'order_class' => $order_class,
                    'order_status|in' => ['PAYED', 'WAIT_BUYER_CONFIRM'],
                ];
                if ($actId) {
                    $filter['act_id'] = $actId;
                }
                if ($distributorId) {
                    unset($filter['user_id']);
                    $filter['is_distribution'] = true;
                    $filter['distributor_id'] = $distributorId;
                    $result['normal_payed_notdelivery'] = $orderService->countOrderNum($filter);
                } else {
                    $result['normal_payed_notdelivery'] = $orderService->countOrderNum($filter);
                }

                // 待发货
                $filter = [
                    'user_id' => $authInfo['user_id'],
                    'company_id' => $authInfo['company_id'],
                    'order_type' => 'normal',
                    'order_status' => 'PAYED',
                    'ziti_status' => 'NOTZITI',
                    'order_class' => $order_class,
                ];
                if ($actId) {
                    $filter['act_id'] = $actId;
                }
                $result['normal_payed_daifahuo'] = $orderService->countOrderNum($filter);
                // 待收货
                $filter = [
                    'user_id' => $authInfo['user_id'],
                    'company_id' => $authInfo['company_id'],
                    'order_type' => 'normal',
                    'order_status' => 'WAIT_BUYER_CONFIRM',
                    'ziti_status' => 'NOTZITI',
                    'order_class' => $order_class,
                ];
                if ($actId) {
                    $filter['act_id'] = $actId;
                }
                $result['normal_payed_daishouhuo'] = $orderService->countOrderNum($filter);

                //待自提订单
                $filter = [
                    'user_id' => $authInfo['user_id'],
                    'company_id' => $authInfo['company_id'],
                    'order_type' => 'normal',
                    'order_status' => 'PAYED',
                    'ziti_status' => 'PENDING',
                    'order_class' => $order_class,
                ];
                if ($actId) {
                    $filter['act_id'] = $actId;
                }
                if ($request->input('is_distribution')) {
                    $filter['is_distribution'] = true;
                    $result['normal_payed_daiziti'] = $orderService->countOrderNum($filter);
                } else {
                    $result['normal_payed_daiziti'] = $orderService->countOrderNum($filter);
                }

                //待处理的售后
                $filter = [
                    'user_id' => $authInfo['user_id'],
                    'company_id' => $authInfo['company_id'],
                    'aftersales_status' => [0, 1]
                ];
                $aftersalesService = new AftersalesService();

                $afterDetail = $aftersalesService->getAfterSalesNumDetailList($authInfo['user_id'], $authInfo['company_id']);
                $result['aftersales'] = $afterDetail['aftersales'] ?? 0;
                $result['aftersales_pending'] = $afterDetail['aftersales_pending'] ?? 0;
                $result['aftersales_processing'] = $afterDetail['aftersales_processing'] ?? 0;

                // 待评价
                $filter = [
                    'user_id' => $authInfo['user_id'],
                    'company_id' => $authInfo['company_id'],
                    'order_type' => 'normal',
                    'order_status' => 'DONE',
                    'is_rate' => 0,
                    'order_class' => $order_class,
                ];
                if ($actId) {
                    $filter['act_id'] = $actId;
                }
                $result['normal_not_rate'] = $orderService->countOrderNum($filter);

                break;
            case 'bargain':
                // 已付款
                $filter = [
                    'user_id' => $authInfo['user_id'],
                    'company_id' => $authInfo['company_id'],
                    'order_type' => 'bargain',
                    'order_class' => 'bargain',
                    'order_status' => 'NOTPAY',
                ];
                $result['service_notpay'] = $orderAssociationService->countOrderNum($filter);
                // 未付款
                $filter = [
                    'user_id' => $authInfo['user_id'],
                    'company_id' => $authInfo['company_id'],
                    'order_type' => 'bargain',
                    'order_class' => 'bargain',
                    'order_status' => 'DONE'
                ];
                $result['service_payed'] = $orderAssociationService->countOrderNum($filter);
                break;
        }

        $result['rate_status'] = $this->getGoodsRateSettingStatus($authInfo['company_id']);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/trackerpull",
     *     summary="物流查询",
     *     tags={"订单"},
     *     description="物流查询",
     *     operationId="trackerpull",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true
     *     ),
     *     @SWG\Parameter(name="order_type", description="订单类型", in="query", type="string", required=true),
     *     @SWG\Parameter(name="order_id",   description="订单编号", in="query", type="string", required=true),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="array", description="",
     *               @SWG\Items(
     *                 @SWG\Property(property="AcceptTime", type="string", example="2021-01-27 14:58:29", description="物流时间"),
     *                 @SWG\Property(property="AcceptStation", type="string", example="暂无物流信息", description="物流详情描述"),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function trackerpull(Request $request)
    {
        $orderType = $request->input('order_type') ? $request->input('order_type') : 'normal';
        $orderService = $this->getOrderService($orderType);
        $orderId = $request->input('order_id');
        $authInfo = $request->get('auth');
        $order = $orderService->getOrderInfo($authInfo['company_id'], $orderId);

        $userId = $order['orderInfo']['user_id'] ?? 0;
        if ($userId != $authInfo['user_id']) {
            return $this->response->array([['AcceptTime' => date('Y-m-d H:i:s', time()), 'AcceptStation' => '暂无物流信息']]);
        }
        try {
            if($order['orderInfo']['receipt_type'] == 'merchant'){
                $deliveryProcessServices = new DeliveryProcessLogServices();
                $result = $deliveryProcessServices->getListByOrderData($order);
                return $this->response->array($result);
            }
            $tracker = new LogisticTracker();
            if ($result = $tracker->sfbspCheck($order['orderInfo']['delivery_code'], $order['orderInfo']['delivery_corp'], $authInfo['company_id'], $order['orderInfo']['receiver_mobile'])) {
                return $this->response->array($result);
            }

            if (isset($order['orderInfo']['delivery_corp_source']) && $order['orderInfo']['delivery_corp_source'] == 'kuaidi100') {
                $result = $tracker->kuaidi100($order['orderInfo']['delivery_corp'], $order['orderInfo']['delivery_code'], $authInfo['company_id'], $order['orderInfo']['receiver_mobile']);
            } else {
                //需要根据订单
                $result = $tracker->pullFromHqepay($order['orderInfo']['delivery_code'], $order['orderInfo']['delivery_corp'], $authInfo['company_id'], $order['orderInfo']['receiver_mobile']);
            }
        } catch (\Exception $exception) {
            return $this->response->array([['AcceptTime' => date('Y-m-d H:i:s', time()), 'AcceptStation' => '暂无物流信息']]);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/ziticode",
     *     summary="获取自提码",
     *     tags={"订单"},
     *     description="获取自提码",
     *     operationId="getZitiQRCode",
     *     @SWG\Parameter( name="barcode_url", in="query", description="条形码", required=true, type="string"),
     *     @SWG\Parameter( name="qrcode_url", in="query", description="二维码", required=true, type="string"),
     *     @SWG\Parameter( name="code", in="query", description="码", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="user_id", type="string", example="20134", description="会员中心"),
     *               @SWG\Property(property="barcode_url", type="string", example="11111", description="一维码链接"),
     *               @SWG\Property(property="qrcode_url", type="string", example="2222", description="二维码链接"),
     *               @SWG\Property(property="code", type="string", example="0979626", description="自提码"),
     *               @SWG\Property(property="ziti_status", type="string", example="NOTZITI", description="自提状态"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getZitiQRCode(Request $request)
    {
        $orderType = $request->input('order_type') ? $request->input('order_type') : 'normal';
        $orderService = $this->getOrderService($orderType);
        $orderId = $request->input('order_id');
        $authInfo = $request->get('auth');
        $result = $orderService->getOrderZitiCode($authInfo['company_id'], $orderId);
        $result['pickup_code'] = $orderService->showSmsPickupCode($authInfo['company_id'], $orderId);

        $userId = $result['user_id'] ?? 0;
        if ($userId != $authInfo['user_id']) {
            return $this->response->array([]);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/order/cancel",
     *     summary="订单取消",
     *     tags={"订单"},
     *     description="订单取消",
     *     operationId="cancelOrder",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Parameter( name="cancel_reason", in="query", description="取消原因", required=true, type="string"),
     *     @SWG\Parameter( name="other_reason", in="query", description="其他原因", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="cancel_id", type="string", example="2040", description="取消ID"),
     *               @SWG\Property(property="order_id", type="string", example="3307584000370376", description="订单号"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *               @SWG\Property(property="shop_id", type="integer", example="0", description="门店id"),
     *               @SWG\Property(property="user_id", type="string", example="20376", description="用户id"),
     *               @SWG\Property(property="distributor_id", type="string", example="104", description="分销商id"),
     *               @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 service 服务业订单;bargain 砍价订单;distribution 分销订单;normal 普通实体订单"),
     *               @SWG\Property(property="total_fee", type="string", example="1", description="订单金额，以分为单位"),
     *               @SWG\Property(property="progress", type="integer", example="0", description="处理进度。可选值有 0 待处理;1 已取消;2 处理中;3 已完成; 4 已驳回"),
     *               @SWG\Property(property="cancel_from", type="string", example="buyer", description="取消来源。可选值有 buyer 用户取消订单;shop 商家取消订单"),
     *               @SWG\Property(property="cancel_reason", type="string", example="不想要了", description="取消原因"),
     *               @SWG\Property(property="shop_reject_reason", type="string", example="", description="商家拒绝理由"),
     *               @SWG\Property(property="refund_status", type="string", example="WAIT_CHECK", description="退款状态。可选值有 READY 待审核;AUDIT_SUCCESS 审核成功待退款;SUCCESS 退款成功;SHOP_CHECK_FAILS 商家审核不通过;CANCEL 撤销退款;PROCESSING 已发起退款等待到账;FAILS 退款失败;"),
     *               @SWG\Property(property="create_time", type="integer", example="1611303164", description="订单创建时间"),
     *               @SWG\Property(property="update_time", type="integer", example="1611303164", description="订单更新时间"),
     *               @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *               @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *               @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *               @SWG\Property(property="point", type="integer", example="0", description="消费积分"),
     *               @SWG\Property(property="pay_type", type="string", example="wxpay", description="支付方式"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones")))
     * )
     */
    public function cancelOrder(Request $request)
    {
        $authInfo = $request->get('auth');
        $params = $request->all();
        $params['company_id'] = $authInfo['company_id'];
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($authInfo['company_id'], $params['order_id']);
        if (!$order) {
            throw new \Exception("订单号为{$params['order_id']}的订单不存在");
        }

        if ($authInfo['user_id'] == $order['user_id']) {
            $params['mobile'] = $authInfo['mobile'];
            $params['user_id'] = $authInfo['user_id'];
            $params['cancel_from'] = 'buyer'; //用户取消订单
        } elseif ($authInfo['chief_id'] ?? 0) {
            $orderService = $this->getOrderServiceByOrderInfo($order);
            $orderInfo = $orderService->getOrderInfo($authInfo['company_id'], $order['order_id']);
            if (!($orderInfo['orderInfo'] ?? [])) {
                throw new ResourceException(trans('OrdersBundle/Order.order_not_exist'));
            }

            if (!($orderInfo['orderInfo']['community_info'] ?? [])) {
                throw new ResourceException(trans('OrdersBundle/Order.can_only_cancel_own_group_order'));
            }

            if ($orderInfo['orderInfo']['community_info']['chief_id'] != $authInfo['chief_id']) {
                throw new ResourceException(trans('OrdersBundle/Order.can_only_cancel_own_group_order'));
            }

            $params['mobile'] = $orderInfo['orderInfo']['mobile'];
            $params['user_id'] = $orderInfo['orderInfo']['user_id'];
            $params['chief_id'] = $authInfo['chief_id'];
            $params['cancel_from'] = 'chief'; //用户取消订单
        } else {
            throw new ResourceException(trans('OrdersBundle/Order.order_data_exception'));
        }

        if ($order['order_type'] != 'normal') {
            throw new ResourceException(trans('OrdersBundle/Order.only_physical_order_can_cancel'));
        }
        if ($order['order_status'] == 'WAIT_GROUPS_SUCCESS' && $order['order_class'] == 'groups') {
            throw new ResourceException(trans('OrdersBundle/Order.group_order_cannot_cancel_before_complete'));
        }

        /* @var \OrdersBundle\Services\Orders\AbstractNormalOrder $orderService */
        $orderService = $this->getOrderServiceByOrderInfo($order);
        $result = $orderService->cancelOrder($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/order/confirmReceipt",
     *     summary="买家确认收货",
     *     tags={"订单"},
     *     description="买家确认收货",
     *     operationId="confirmReceipt",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="order_id", type="string", example="3309653000180376", description="订单号"),
     *               @SWG\Property(property="title", type="string", example="测试0119-2...", description="订单标题"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *               @SWG\Property(property="user_id", type="string", example="20376", description="用户id"),
     *               @SWG\Property(property="act_id", type="string", example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
     *               @SWG\Property(property="mobile", type="string", example="13095920688", description="手机号"),
     *               @SWG\Property(property="freight_fee", type="integer", example="1", description="运费价格，以分为单位"),
     *               @SWG\Property(property="freight_type", type="string", example="cash", description=""),
     *               @SWG\Property(property="item_fee", type="string", example="1", description="商品金额，以分为单位"),
     *               @SWG\Property(property="item_point", type="integer", example="0", description=""),
     *               @SWG\Property(property="cost_fee", type="integer", example="10000", description="商品成本价，以分为单位"),
     *               @SWG\Property(property="total_fee", type="string", example="2", description="订单金额，以分为单位"),
     *               @SWG\Property(property="step_paid_fee", type="integer", example="0", description="分阶段付款已支付金额，以分为单位"),
     *               @SWG\Property(property="total_rebate", type="integer", example="0", description="订单总分销金额，以分为单位"),
     *               @SWG\Property(property="distributor_id", type="string", example="104", description="分销商id"),
     *               @SWG\Property(property="receipt_type", type="string", example="logistics", description="收货方式。可选值有 logistics:物流;ziti:店铺自提"),
     *               @SWG\Property(property="ziti_code", type="string", example="0", description="店铺自提码"),
     *               @SWG\Property(property="shop_id", type="string", example="0", description="门店id"),
     *               @SWG\Property(property="ziti_status", type="string", example="NOTZITI", description="店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核"),
     *               @SWG\Property(property="order_status", type="string", example="DONE", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *               @SWG\Property(property="order_source", type="string", example="member", description="订单来源。可选值有 member-用户自主下单;shop-商家代客下单"),
     *               @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 normal:普通实体订单"),
     *               @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单"),
     *               @SWG\Property(property="auto_cancel_time", type="string", example="1611304246", description="订单自动取消时间"),
     *               @SWG\Property(property="auto_cancel_seconds", type="integer", example="354", description=""),
     *               @SWG\Property(property="auto_finish_time", type="string", example="1611908660", description="订单自动完成时间"),
     *               @SWG\Property(property="is_distribution", type="string", example="1", description="是否分销订单"),
     *               @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *               @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *               @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
     *               @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *               @SWG\Property(property="delivery_corp_source", type="string", example="kuaidi100", description="快递代码来源"),
     *               @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *               @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
     *               @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货"),
     *               @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *               @SWG\Property(property="delivery_time", type="integer", example="1611303860", description="发货时间"),
     *               @SWG\Property(property="end_time", type="integer", example="1611303892", description="订单完成时间"),
     *               @SWG\Property(property="end_date", type="string", example="2021-01-22 16:24:52", description=""),
     *               @SWG\Property(property="receiver_name", type="string", example="张三", description="收货人姓名"),
     *               @SWG\Property(property="receiver_mobile", type="string", example="13095920688", description="收货人手机号"),
     *               @SWG\Property(property="receiver_zip", type="string", example="101001", description="收货人邮编"),
     *               @SWG\Property(property="receiver_state", type="string", example="北京市", description="收货人所在省份"),
     *               @SWG\Property(property="receiver_city", type="string", example="北京市", description="收货人所在城市"),
     *               @SWG\Property(property="receiver_district", type="string", example="东城", description="收货人所在地区"),
     *               @SWG\Property(property="receiver_address", type="string", example="101", description="收货人详细地址"),
     *               @SWG\Property(property="member_discount", type="integer", example="0", description="会员折扣金额，以分为单位"),
     *               @SWG\Property(property="coupon_discount", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *               @SWG\Property(property="discount_fee", type="integer", example="0", description="订单优惠金额，以分为单位"),
     *               @SWG\Property(property="create_time", type="integer", example="1611303646", description="订单创建时间"),
     *               @SWG\Property(property="update_time", type="integer", example="1611303892", description="订单更新时间"),
     *               @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *               @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *               @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *               @SWG\Property(property="cny_fee", type="integer", example="2", description=""),
     *               @SWG\Property(property="point", type="integer", example="0", description="消费积分"),
     *               @SWG\Property(property="pay_type", type="string", example="wxpay", description="支付方式"),
     *               @SWG\Property(property="remark", type="string", example="", description="订单备注"),
     *              @SWG\Property(property="third_params", type="object", description="",
     *                   @SWG\Property(property="is_liveroom", type="string", example="1", description=""),
     *              ),
     *               @SWG\Property(property="invoice", type="string", example="", description="发票信息(DC2Type:json_array)"),
     *               @SWG\Property(property="send_point", type="integer", example="0", description="是否分发积分0否 1是"),
     *               @SWG\Property(property="is_rate", type="string", example="", description="是否评价"),
     *               @SWG\Property(property="is_invoiced", type="string", example="", description="是否已开发票"),
     *               @SWG\Property(property="invoice_number", type="string", example="", description="发票号"),
     *               @SWG\Property(property="audit_status", type="string", example="processing", description="跨境订单审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *               @SWG\Property(property="audit_msg", type="string", example="正在审核订单", description="审核意见"),
     *               @SWG\Property(property="point_fee", type="integer", example="0", description="积分抵扣金额，以分为单位"),
     *               @SWG\Property(property="point_use", type="integer", example="0", description="积分抵扣使用的积分数"),
     *               @SWG\Property(property="pay_status", type="string", example="PAYED", description="支付状态。可选值有 NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中"),
     *               @SWG\Property(property="get_points", type="integer", example="2", description="订单获取积分"),
     *               @SWG\Property(property="bonus_points", type="integer", example="0", description="购物赠送积分"),
     *               @SWG\Property(property="get_point_type", type="integer", example="1", description="获取积分类型，0 老订单按订单完成时送,1 新订单按下单时计算送"),
     *               @SWG\Property(property="pack", type="string", example="", description="包装"),
     *               @SWG\Property(property="is_shopscreen", type="string", example="", description="是否门店订单"),
     *               @SWG\Property(property="is_logistics", type="string", example="", description="门店缺货商品总部快递发货"),
     *               @SWG\Property(property="is_profitsharing", type="integer", example="1", description="是否分账订单 1不分账 2分账"),
     *               @SWG\Property(property="profitsharing_status", type="integer", example="1", description="分账状态 1未分账 2已分账"),
     *               @SWG\Property(property="order_auto_close_aftersales_time", type="integer", example="1611390292", description="自动关闭售后时间"),
     *               @SWG\Property(property="profitsharing_rate", type="integer", example="0", description="分账费率"),
     *               @SWG\Property(property="bind_auth_code", type="string", example="", description=""),
     *               @SWG\Property(property="extra_points", type="integer", example="0", description=""),
     *               @SWG\Property(property="type", type="integer", example="0", description="订单类型，0普通订单,1跨境订单,....其他"),
     *               @SWG\Property(property="taxable_fee", type="integer", example="0", description="计税总价，以分为单位"),
     *               @SWG\Property(property="identity_id", type="string", example="", description="身份证号码"),
     *               @SWG\Property(property="identity_name", type="string", example="", description="身份证姓名"),
     *               @SWG\Property(property="total_tax", type="integer", example="0", description="总税费"),
     *               @SWG\Property(property="discount_info", type="string", description=""),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones")))
     * )
     */
    public function confirmReceipt(Request $request)
    {
        $authInfo = $request->get('auth');
        $orderId = $request->input('order_id');
        if (!$orderId) {
            throw new ResourceException(trans('OrdersBundle/Order.order_id_required'));
        }
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($authInfo['company_id'], $orderId);
        if (!$order) {
            throw new ResourceException(trans('OrdersBundle/Order.order_not_found_with_id', ['orderId' => $orderId]));
        }
        if ($authInfo['user_id'] != $order['user_id']) {
            throw new ResourceException(trans('OrdersBundle/Order.order_data_exception'));
        }
        if ($order['order_type'] != 'normal') {
            throw new ResourceException(trans('OrdersBundle/Order.only_physical_order_can_confirm'));
        }

        $authInfo['order_id'] = $orderId;

        /**
         * @var \OrdersBundle\Services\Orders\AbstractNormalOrder $orderService
         */
        $orderService = $this->getOrderServiceByOrderInfo($order);
        $result = $orderService->confirmReceipt($authInfo);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/pickupcode/{order_id}",
     *     summary="获取自提订单提货码",
     *     tags={"订单"},
     *     description="获取自提订单提货码",
     *     operationId="getOrderPickupCode",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="order_id",
     *         in="path",
     *         description="订单号",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *          @SWG\Schema(
     *            @SWG\Property(
     *              property="data",
     *              type="object",
     *              description="",
     *                @SWG\Property(
     *                  property="status", description="发送状态", type="string", example="true",
     *                ),
     *            ),
     *        ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getOrderPickupCode(Request $request, $order_id)
    {
        if (!$order_id) {
            throw new BadRequestHttpException('订单号必填');
        }
        $authInfo = $request->get('auth');
        if (!isset($authInfo['user_id']) || !$authInfo['user_id']) {
            throw new ResourceException(trans('OrdersBundle/Order.phone_auth_required'));
        }
        // 验证订单
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($authInfo['company_id'], $order_id);
        if (!$order) {
            throw new BadRequestHttpException('此订单不存在！');
        }

        if ($order['user_id'] != $authInfo['user_id']) {
            throw new ResourceException(trans('OrdersBundle/Order.operation_failed'));
        }
        $orderService = $this->getOrderServiceByOrderInfo($order);
        // 发送提货码短信
        $return = $orderService->orderPickupCode($authInfo['company_id'], $order_id);
        $result = ['status' => $return];
        return $this->response->array($result);
    }

    /**
     * @SWG\POST(
     *     path="/wxapp/order/bind/{order_id}",
     *     summary="绑定订单",
     *     tags={"订单"},
     *     description="绑定订单",
     *     operationId="bindUserOrder",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="auth_code",
     *         in="path",
     *         description="验证码",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="status", type="string", description="状态"),
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function bindUserOrder(Request $request, $order_id)
    {
        if (!$order_id) {
            throw new BadRequestHttpException('订单号必填');
        }
        $authInfo = $request->get('auth');
        if (!isset($authInfo['user_id']) || !$authInfo['user_id']) {
            throw new ResourceException(trans('OrdersBundle/Order.phone_auth_required'));
        }

        $authCode = $request->get('auth_code');
        if (!$authCode) {
            throw new BadRequestHttpException('小票验证码必填');
        }

        $params['order_id'] = $order_id;
        $params['bind_auth_code'] = $authCode;
        $params['user_id'] = $authInfo['user_id'];
        $params['company_id'] = $authInfo['company_id'];

        $orderService = $this->getOrderService('normal');
        $return = $orderService->bindUserOrder($params);

        $result = ['status' => $return];
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/order/cancel/deliverystaff",
     *     summary="取消配送",
     *
     */
    public function cancelDeliveryStaff(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];

        $params = $request->all();
        $rules = [
            'order_id' => ['required', '订单号必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $params['order_id']);
        if(!$order) {
            throw new ResourceException('订单不存在');
        }
        $orderService = $this->getOrderServiceByOrderInfo($order);
        $orderInfo = $orderService->getOrderInfo($companyId, $params['order_id']);
        $order = $orderInfo['orderInfo'];
        if(in_array($order['self_delivery_status'],['CONFIRMING','DONE'])){
            throw new ResourceException(trans('OrdersBundle/Order.cannot_cancel_delivery_in_current_status'));
        }
        $params['self_delivery_status'] = $order['self_delivery_status'];
        if ($order['self_delivery_status'] == 'RECEIVEORDER') {
            $params['self_delivery_status'] = 'CONFIRMING';
        }
        $params['company_id'] = $companyId;

        $result = $orderService->cancelDeliveryStaff($params);
        return $this->response->array(['status'=>$result]);
    }

    /**
     * @SWG\Post(
     *     path="/order/deliverypackag/confirm",
     *     summary="订单打包确认",
     *
     */
    public function confirmDeliveryPackag(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];

        $params = $request->all();
        $rules = [
            'order_id' => ['required', '订单号必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $params['order_id']);
        if(!$order) {
            throw new ResourceException('订单不存在');
        }
        $params['company_id'] = $companyId;
        $orderService = $this->getOrderServiceByOrderInfo($order);

        $orderInfo = $orderService->getOrderInfo($companyId, $params['order_id']);
        $order = $orderInfo['orderInfo'];
        if(in_array($order['self_delivery_status'],['DELIVERING','DONE'])){
            throw new ResourceException(trans('OrdersBundle/Order.cannot_confirm_package_in_current_status'));
        }
        $result = $orderService->confirmDeliveryPackag($params);
        return $this->response->array(['status'=>$result]);
    }

    public function delivery(Request $request)
    {
        $params = $request->all();

        $rules = [
            'order_id' => ['required', '订单号缺少！'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($params['company_id'], $params['order_id']);
        if (!$order) {
            return $this->response->error('此订单不存在！', 422);
        }


        $logisticsServices = new LogisticsService();
        $logiInfo = $logisticsServices->getLogisticsFirst(['corp_code' => $params['delivery_corp']]);
        if ($logiInfo) {
            $params['logi_name'] = $logiInfo['corp_name'];
        }

        /**
         * @var \OrdersBundle\Services\Orders\AbstractNormalOrder $orderService
         */
        $orderService = $this->getOrderServiceByOrderInfo($order);
        $result = $orderService->delivery($params);

        return $this->response->array($result);
    }

    public function updateDelivery($delivery_id, Request $request)
    {
        $params = $request->all();
        $rules = [
            'delivery_corp' => ['required', '物流公司编码必填'],
            'delivery_code' => ['required', '物流公司快递号必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $params['orders_delivery_id'] = $delivery_id;
        $params['operator_type'] = 'admin';
        $orderDeliveryService = new OrderDeliveryService();
        $result = $orderDeliveryService->update($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/order/offline/backaccount",
     *     summary="线下转账--获取收款账户",
     *     tags={"订单"},
     *     description="线下转账--获取收款账户",
     *     operationId="getOfflineAccount",
     *     @SWG\Response(response=200, description="成功返回结构", @SWG\Schema(type="object", @SWG\Property(property="status", type="string", description="状态"))),
     * )
     */
    public function getOfflineAccount(Request $request)
    {
        $params = $request->all();
        $offlineBankAccountService = new OfflineBankAccountService();
        $offlinePayService = new OfflinePayService();
        $authInfo = $request->get('auth');
        $setting = $offlinePayService->getPaymentSetting($authInfo['company_id']);
        $list = $offlineBankAccountService->getLists([
            'company_id' => $authInfo['company_id'],
        ], '*', 1, -1, ['is_default' => 'DESC', 'created' => 'DESC']);
        $result = [
            'setting' => $setting,
            'list' => $list,
        ];
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/order/offline/voucher",
     *     summary="上传线下支付凭证",
     *     tags={"订单"},
     *     description="上传线下支付凭证",
     *     operationId="uploadOfflineVoucher",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="order_id", in="path", description="订单号", required=true, type="integer"),
     *     @SWG\Parameter(name="bank_account_name", in="path", description="收款户名", required=true, type="string"),
     *     @SWG\Parameter(name="bank_account_no", in="path", description="银行账号", required=true, type="string"),
     *     @SWG\Parameter(name="bank_name", in="path", description="开户银行", required=true, type="string"),
     *     @SWG\Parameter(name="china_ums_no", in="path", description="银联号", required=true, type="string"),
     *     @SWG\Parameter(name="pay_account_name", in="path", description="付款用户名", required=true, type="string"),
     *     @SWG\Parameter(name="pay_account_bank", in="path", description="付款银行", required=true, type="string"),
     *     @SWG\Parameter(name="pay_account_no", in="path", description="付款账号", required=true, type="string"),
     *     @SWG\Parameter(name="pay_sn", in="path", description="付款流水单号", required=false, type="string"),
     *     @SWG\Parameter(name="voucher_pic", in="path", description="凭证图片", required=true, type="string"),
     *     @SWG\Parameter(name="transfer_remark", in="path", description="转账备注", required=false, type="string"),
     *     ),
     *     @SWG\Response(response=200, description="成功返回结构", @SWG\Schema(type="object", @SWG\Property(property="status", type="string", description="状态"))),
     * )
     */
    public function uploadOfflineVoucher(Request $request)
    {
        $params = $request->all();
        $rules = [
            'order_id' => ['required', '订单号缺少！'],
            'bank_account_id' => ['required', '收款账户id缺少！'],
            // 'bank_account_name' => ['required', '收款户名缺少！'],
            // 'bank_account_no' => ['required', '银行账号缺少！'],
            // 'bank_name' => ['required', '开户银行缺少！'],
            // 'china_ums_no' => ['required', '银联号缺少！'],
            // 'pay_account_name' => ['required', '付款用户名缺少！'],
            // 'pay_account_bank' => ['required', '付款银行缺少！'],
            // 'pay_account_no' => ['required', '付款账号缺少！'],
            'voucher_pic.*' => ['required', '凭证图片缺少！'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        if (isset($params['promoter_user_id']) && isset($params['isSalesmanPage']) && $params['promoter_user_id'] && $params['isSalesmanPage'] == 1 ){
            $params['promoter_user_id'] = $params['promoter_user_id'];
        }
        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] =  $authInfo['user_id'];
        $params['transfer_remark'] = mb_substr(($params['transfer_remark'] ?? ''), 0, 100);
        $offlinePaymentService = new OfflinePaymentService();
        $result = $offlinePaymentService->uploadVoucher($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/order/offline/get/voucher",
     *     summary="获取线下支付凭证",
     *     tags={"订单"},
     *     description="获取线下支付凭证",
     *     operationId="getOfflineVoucher",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="order_id", in="path", description="订单号", required=true, type="integer"),
     *     @SWG\Response(response=200, description="成功返回结构", @SWG\Schema(type="object", @SWG\Property(property="status", type="string", description="状态"))),
     * )
     */
    public function getOfflineVoucher(Request $request)
    {
        $params = $request->all();
        $rules = [
            'order_id' => ['required', '订单号缺少！'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $offlinePaymentService = new OfflinePaymentService();
        $result = $offlinePaymentService->getVoucher($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/order/offline/update/voucher",
     *     summary="修改线下支付凭证",
     *     tags={"订单"},
     *     description="修改线下支付凭证",
     *     operationId="updateOfflineVoucher",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="id", in="path", description="凭证id", required=true, type="integer"),
     *     @SWG\Parameter(name="order_id", in="path", description="订单号", required=true, type="integer"),
     *     @SWG\Parameter(name="bank_account_name", in="path", description="收款户名", required=true, type="string"),
     *     @SWG\Parameter(name="bank_account_no", in="path", description="银行账号", required=true, type="string"),
     *     @SWG\Parameter(name="bank_name", in="path", description="开户银行", required=true, type="string"),
     *     @SWG\Parameter(name="china_ums_no", in="path", description="银联号", required=true, type="string"),
     *     @SWG\Parameter(name="pay_account_name", in="path", description="付款用户名", required=true, type="string"),
     *     @SWG\Parameter(name="pay_account_bank", in="path", description="付款银行", required=true, type="string"),
     *     @SWG\Parameter(name="pay_account_no", in="path", description="付款账号", required=true, type="string"),
     *     @SWG\Parameter(name="voucher_pic", in="path", description="凭证图片", required=true, type="string"),
     *     @SWG\Parameter(name="transfer_remark", in="path", description="转账备注", required=false, type="string"),
     *     @SWG\Response(response=200, description="成功返回结构", @SWG\Schema(type="object", @SWG\Property(property="status", type="string", description="状态"))),
     * )
     */
    public function updateOfflineVoucher(Request $request)
    {
        $params = $request->all();
        $rules = [
            'id' => ['required', '凭证id缺少！'],
            'order_id' => ['required', '订单号缺少！'],
            'bank_account_id' => ['required', '收款账户id缺少！'],
            // 'bank_account_name' => ['required', '收款户名缺少！'],
            // 'bank_account_no' => ['required', '银行账号缺少！'],
            // 'bank_name' => ['required', '开户银行缺少！'],
            // 'china_ums_no' => ['required', '银联号缺少！'],
            // 'pay_account_name' => ['required', '付款用户名缺少！'],
            // 'pay_account_bank' => ['required', '付款银行缺少！'],
            // 'pay_account_no' => ['required', '付款账号缺少！'],
            'voucher_pic' => ['required', '凭证图片缺少！'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        if (isset($params['promoter_user_id']) && isset($params['isSalesmanPage']) && $params['promoter_user_id'] && $params['isSalesmanPage'] == 1 ){
            $params['promoter_user_id'] = $params['promoter_user_id'];
        }
        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];
        $params['transfer_remark'] = mb_substr(($params['transfer_remark'] ?? ''), 0, 100);
        $offlinePaymentService = new OfflinePaymentService();
        $result = $offlinePaymentService->updateVoucher($params);
        return $this->response->array($result);
    }
}
