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

namespace KaquanBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\ResourceException;

use KaquanBundle\Services\MemberCardService;
use KaquanBundle\Services\VipGradeService;
use KaquanBundle\Services\VipGradeOrderService;
use OrdersBundle\Traits\GetPaymentServiceTrait;
use MembersBundle\Services\MemberService;

use DistributionBundle\Services\DistributorService;

class VipGradeController extends BaseController
{
    use GetPaymentServiceTrait;

    /**
     * @SWG\Get(
     *     path="/wxapp/vipgrades/list",
     *     summary="获取付费会员等级卡列表",
     *     tags={"卡券"},
     *     description="获取付费会员等级卡列表",
     *     operationId="listDataVipGrade",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *              @SWG\Property( property="data", type="array",
     *                  @SWG\Items( type="object",
     *                      ref="#/definitions/VipGrade"
     *                   ),
     *              ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function listDataVipGrade(Request $request)
    {
        $user = $request->get('auth');
        $filter['company_id'] = $user['company_id'];
        $filter['is_disabled'] = false;
        $vipGradeService = new VipGradeService();
        $result = $vipGradeService->lists($filter);
        foreach ($result as &$list) {
            if (isset($list['price_list']) && $list['price_list']) {
                $pricelist = [];
                foreach ($list['price_list'] as $k => $price_list) {
                    if ($price_list['price']) {
                        $pricelist[] = $price_list;
                    }
                }
                $list['price_list'] = $pricelist;
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/membercard/grades",
     *     summary="获取等级列表",
     *     tags={"卡券"},
     *     description="获取等级列表",
     *     operationId="getGradeList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                      @SWG\Property( property="member_card_list", type="array", description="会员等级列表",
     *                           @SWG\Items(
     *                              type="object",
     *                              ref="#/definitions/MemberGrade"
     *                           ),
     *                      ),
     *                      @SWG\Property( property="vip_grade_list", type="array", description="付费会员等级卡列表",
     *                           @SWG\Items( type="object",
     *                              ref="#/definitions/VipGrade"
     *                           ),
     *                      ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getGradeList(Request $request)
    {
        $user = $request->get('auth');
        $companyId = $user['company_id'];
        $kaquanService = new MemberCardService();
        $isMemberCount = false;
        $result['member_card_list'] = $kaquanService->getGradeListByCompanyId($companyId, $isMemberCount);

        $filter['company_id'] = $user['company_id'];
        $filter['is_disabled'] = false;
        $vipGradeService = new VipGradeService();

        $result['vip_grade_list'] = $vipGradeService->lists($filter);

        if ($user['user_id'] > 0) {
            $memberService = new MemberService();
            $result['total_consumption'] = $memberService->getTotalConsumption($user['user_id']);
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/vipgrades/info",
     *     summary="获取付费会员等级卡详情(废弃)",
     *     tags={"卡券"},
     *     description="获取付费会员等级卡详情(废弃)",
     *     operationId="infoDataVipGrade",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter( name="vip_grade_id", in="query", description="id", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function infoDataVipGrade(Request $request)
    {
        $user = $request->get('auth');
        $filter['company_id'] = $user['company_id'];
        $filter['vip_grade_id'] = $request->input('vip_grade_id');
        $vipGradeService = new VipGradeService();
        $result = $vipGradeService->getInfo($filter);
        if (isset($result['price_list'])) {
            foreach ($result['price_list'] as $k => $pricelist) {
                if (!$pricelist['price']) {
                    unset($result['price_list'][$k]);
                }
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/vipgrades/buy",
     *     summary="购买付费会员折扣卡",
     *     tags={"卡券"},
     *     description="购买付费会员折扣卡",
     *     operationId="buyDataVipGrade",
     *     @SWG\Parameter( name="shop_id", in="query", description="门店ID", type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", type="string"),
     *     @SWG\Parameter( name="vip_grade_id", in="query", description="付费会员折扣卡id", required=true, type="string"),
     *     @SWG\Parameter( name="card_type", in="query", description="折扣类型（monthly、quarter、year）", required=true, type="string"),
     *     @SWG\Parameter( name="pay_type", in="query", description="支付方式 wxpay(默认)", type="string"),
     *     @SWG\Parameter( name="pay_channel", in="query", description="支付渠道", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="appId", type="string", example="wx912913df9fef6ddd", description="appId"),
     *                  @SWG\Property( property="timeStamp", type="string", example="1611299634", description="时间戳"),
     *                  @SWG\Property( property="nonceStr", type="string", example="600a7b3299520", description="随机字符串"),
     *                  @SWG\Property( property="package", type="string", example="prepay_id=wx221513545851640040c60f26bfa14d0000", description="扩展字符串"),
     *                  @SWG\Property( property="signType", type="string", example="MD5", description="签名方式"),
     *                  @SWG\Property( property="paySign", type="string", example="ECB8C50491D4EB5D6AA8BE2EA0E7C7C4", description="签名"),
     *                  @SWG\Property( property="trade_info", type="object",
     *                          @SWG\Property( property="order_id", type="string", example="3309609000060264", description="订单号"),
     *                          @SWG\Property( property="trade_id", type="string", example="3309609000100264", description="交易号"),
     *                  ),
     *                  @SWG\Property( property="pay_fee", type="string", example="1", description="支付金额"),
     *                  @SWG\Property( property="total_fee", type="string", example="1", description="总金额"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function buyDataVipGrade(Request $request)
    {
        $params = $request->all('vip_grade_id', 'card_type', 'distributor_id', 'return_url');
        if (!$params['vip_grade_id']) {
            throw new ResourceException(trans('KaquanBundle.member_purchase_failed'));
        }
        if (!$params['card_type']) {
            throw new ResourceException(trans('KaquanBundle.member_purchase_failed'));
        }
        $authInfo = $request->get('auth');
        if (!$authInfo['user_id'] || !$authInfo['mobile']) {
            throw new ResourceException(trans('KaquanBundle.member_purchase_info_error'));
        }
        $params['user_id'] = $authInfo['user_id'];
        $params['company_id'] = $authInfo['company_id'];
        $params['mobile'] = $authInfo['mobile'];

        $vipGradeOrderService = new VipGradeOrderService();
        $result = $vipGradeOrderService->createData($params);
        if (!$result) {
            throw new ResourceException(trans('KaquanBundle.member_purchase_failed'));
        }

        $shopId = $request->input('shop_id', 0);
        $distributorId = $request->input('distributor_id', 0);

        if ($distributorId) {
            $distributorService = new DistributorService();
            $filter = [
                'company_id' => $authInfo['company_id'],
                'is_valid' => 'true',
                'distributor_id' => $distributorId
            ];
            $distributor = $distributorService->getInfo($filter);
            if ($distributor) {
                $shopId = $distributor['shop_id'];
            }
        }

        $data = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $authInfo['user_id'],
            'total_fee' => $result['price'],
            'pay_fee' => $result['total_fee'],
            'detail' => $result['title'],
            'order_id' => $result['order_id'],
            'body' => $result['title'],
            'open_id' => $authInfo['open_id'] ?? '',
            'wxa_appid' => $authInfo['wxapp_appid'] ?? '',
            'mobile' => $authInfo['mobile'],
            'pay_type' => $request->input('pay_type', 'wxpay'),
            'fee_type' => $result['fee_type'],
            'fee_rate' => $result['fee_rate'],
            'fee_symbol' => $result['fee_symbol'],
            'distributor_id' => $distributorId,
            'shop_id' => $shopId,
            'trade_source_type' => 'membercard',
            'return_url' => $params['return_url'] ?? '',
        ];
        if (in_array($data['pay_type'], ['adapay', 'bspay'])) {
            if (!($payChannel = $request->input('pay_channel'))) {
                throw new BadRequestHttpException($data['pay_type'].trans('KaquanBundle.pay_channel_required'));
            }
            $data['pay_channel'] = $payChannel;
        }
        if ($data['pay_type'] == 'alipaymini') {
            if (!isset($authInfo['alipay_user_id']) || !$authInfo['alipay_user_id']) {
                throw new BadRequestHttpException(trans('KaquanBundle.alipay_auth_required'));
            }
            $data['alipay_user_id'] = $authInfo['alipay_user_id'];
        }

        $authorizerAppId = $authInfo['woa_appid'] ?? '';
        $wxaAppId = $authInfo['wxapp_appid'] ?? '';
        $service = $this->getPaymentService($data['pay_type']);
        $payResult = $service->doPayment($authorizerAppId, $wxaAppId, $data, false);
        $payResult['pay_fee'] = $data['pay_fee'];
        $payResult['total_fee'] = $data['total_fee'];
        return $this->response->array($payResult);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/vipgrades/uservip",
     *     summary="获取付费会员信息",
     *     tags={"卡券"},
     *     description="获取付费会员信息",
     *     operationId="getUserVipGrade",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司ID"),
     *                  @SWG\Property( property="user_id", type="string", example="20264", description="用户ID"),
     *                  @SWG\Property( property="vip_type", type="string", example="vip", description="会员类型"),
     *                  @SWG\Property( property="vip_grade_id", type="string", example="1", description="会员等级id"),
     *                  @SWG\Property( property="end_date", type="string", example="1601434134", description="会员到期时间"),
     *                  @SWG\Property( property="lv_type", type="string", example="vip", description="等级类型,可选值有 vip:普通vip;svip:进阶vip"),
     *                  @SWG\Property( property="is_had_vip", type="string", example="true", description="是否购买过会员卡"),
     *                  @SWG\Property( property="is_vip", type="string", example="false", description="是否有效会员卡"),
     *                  @SWG\Property( property="end_time", type="string", example="2020-09-30", description="会员到期时间"),
     *                  @SWG\Property( property="day", type="string", example="0", description=""),
     *                  @SWG\Property( property="valid", type="string", example="false", description="有效"),
     *                  @SWG\Property( property="is_open", type="string", example="true", description=""),
     *                  @SWG\Property( property="discount", type="string", example="20", description=""),
     *                  @SWG\Property( property="grade_name", type="string", example="一般付费", description="等级名称"),
     *                  @SWG\Property( property="guide_title", type="string", example="开通vip的引导文本，更多优惠等你来享受哦！", description="购买引导文本"),
     *                  @SWG\Property( property="background_pic_url", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/MUQsdY0GdK5nQNFBaEhiao8MfBoP4B70L2rfqJDROzKgwUBvANmHMq9bQV2G1IWibKxK8iaukqbHiaicNkGKZPbX8EA/0?wx_fmt=jpeg", description="商家自定义会员卡背景图"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */

    public function getUserVipGrade(Request $request)
    {
        $authInfo = $request->get('auth');
        //获取付费会员卡信息
        if (!$authInfo['user_id'] || !$authInfo['mobile']) {
            throw new ResourceException(trans('KaquanBundle.member_info_error'));
        }
        $vipGradeService = new VipGradeOrderService();
        $vipgrade = $vipGradeService->userVipGradeGet($authInfo['company_id'], $authInfo['user_id']);
        if (isset($vipgrade['is_open']) && !$vipgrade['is_open']) {
            return $this->response->array(['grade_name' => '']);
        } else {
            return $this->response->array($vipgrade);
        }
    }
}
