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

namespace DepositBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use DepositBundle\Services\RechargeRule;
use DepositBundle\Services\RechargeAgreement;
use DepositBundle\Services\DepositTrade;
use Dingo\Api\Exception\StoreResourceFailedException;

class Recharge extends Controller
{
    /**
     * @SWG\Get(
     *     path="/weapp/deposit/rechargerules",
     *     summary="获取充值面额规则",
     *     tags={"储值"},
     *     description="获取充值面额规则",
     *     operationId="getRechargeRuleList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页数", type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数", type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property(property="total_count", type="string", example="153", description="总条数"),
     *              @SWG\Property(property="list", type="array",
     *                  @SWG\Items( type="object",
     *                     @SWG\Property(property="id", type="string", example="1", description="ID"),
     *                     @SWG\Property(property="companyId", type="string", example="1", description="企业ID"),
     *                     @SWG\Property(property="money", type="string", example="50000.00", description="充值固定金额"),
     *                     @SWG\Property(property="ruleType", type="string", example="money", description="充值规则类型"),
     *                     @SWG\Property(property="ruleData", type="string", example="10", description="充值规则数据"),
     *                     @SWG\Property(property="createTime", type="string", example="1564061088", description="创建时间"),
     *              )),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DepositErrorRespones") ) )
     * )
     */
    public function getRechargeRuleList(Request $request)
    {
        $rechargeRule = new RechargeRule();
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];

        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);

        $list = $rechargeRule->getRechargeRuleList($filter, $pageSize, $page);
        if ($list['total_count'] > 0) {
            foreach ($list['list'] as &$row) {
                $row['money'] = bcdiv($row['money'], 100, 2);
            }
        }
        return $this->response->array($list);
    }

    /**
     * @SWG\Get(
     *     path="/weapp/deposit/recharge/agreement",
     *     summary="获取储值协议",
     *     tags={"储值"},
     *     description="获取储值协议",
     *     operationId="getRechargeAgreementByCompanyId",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property(property="company_id", type="string", example="1", description="企业id"),
     *              @SWG\Property(property="content", type="string", example="充值协议...", description="内容"),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DepositErrorRespones") ) )
     * )
     */
    public function getRechargeAgreementByCompanyId(Request $request)
    {
        $rechargeRule = new RechargeAgreement();
        $authInfo = $request->get('auth');
        $data = $rechargeRule->getRechargeAgreementByCompanyId($authInfo['company_id']);
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/weapp/deposit/recharge",
     *     summary="充值",
     *     tags={"储值"},
     *     description="对会员卡储值金额进行充值",
     *     operationId="recharge",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="poiid", in="formData", description="", required=false, type="integer" ),
     *     @SWG\Parameter( name="shop_id", in="formData", description="门店id", required=false, type="integer" ),
     *     @SWG\Parameter( name="shop_name", in="formData", description="门店名称", required=false, type="string" ),
     *     @SWG\Parameter( name="recharge_rule_id", in="formData", description="充值规则id", required=false, type="string" ),
     *     @SWG\Parameter( name="total_fee", in="formData", description="充值金额", required=true, type="integer" ),
     *     @SWG\Parameter( name="member_card_code", in="formData", description="充值会员卡号", required=true, type="string" ),
     *     @SWG\Parameter( name="body", in="formData", description="描述", required=true, type="string" ),
     *     @SWG\Parameter( name="detail", in="formData", description="交易详情", required=true, type="string" ),
     *     @SWG\Parameter( name="pay_type", in="formData", description="充值支付方式", required=true, type="string" ),
     *     @SWG\Parameter( name="company_id", in="formData", description="企业id", required=true, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property(property="appId", type="string", example="wx912913df9fef6ddd", description="应用id"),
     *              @SWG\Property(property="timeStamp", type="string", example="1611130961", description="时间"),
     *              @SWG\Property(property="nonceStr", type="string", example="6007e851b4b26", description=""),
     *              @SWG\Property(property="package", type="string", example="prepay_id=wx20162241727737ce0e249da0a089350000", description=""),
     *              @SWG\Property(property="signType", type="string", example="MD5", description=""),
     *              @SWG\Property(property="paySign", type="string", example="AA3E206228051980EA404D3600764BF0", description=""),
     *                  @SWG\Property(property="trade_info", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property(property="order_id", type="string", example="CZ3307655000010134", description="订单id"),
     *                          @SWG\Property(property="trade_id", type="string", example="CZ3307655000010134", description="交易id"),
     *                  )),
     *              )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DepositErrorRespones") ) )
     * )
     */
    public function recharge(Request $request)
    {
        $depositTrade = new DepositTrade();

        if (intval($request->input('total_fee')) <= 0) {
            throw new StoreResourceFailedException('请填写正确的充值金额');
        }

        $authInfo = $request->get('auth');
        $params = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $authInfo['user_id'],
            'member_card_code' => $authInfo['user_card_code'],
            'money' => intval($request->input('total_fee')),
            'detail' => $request->input('detail'),
            'shop_id' => $request->input('shop_id', 0),
            'shop_name' => $request->input('shop_name', ''),
            'open_id' => $authInfo['open_id'] ?? '',
            'wxa_appid' => $authInfo['wxapp_appid'] ?? '',
            'mobile' => $authInfo['mobile'],
            'pay_type' => $request->input('pay_type', 'wxpay'),
        ];
        if ($params['pay_type'] == 'alipaymini') {
            if (!isset($authInfo['alipay_user_id']) || !$authInfo['alipay_user_id']) {
                throw new BadRequestHttpException('请在支付宝小程序授权登录');
            }
            $params['alipay_user_id'] = $authInfo['alipay_user_id'];
        }

        $wxaAppId = $authInfo['wxapp_appid'] ?? '';
        $authorizerAppId = $authInfo['woa_appid'] ?? '';

        $data = $depositTrade->recharge($authorizerAppId, $wxaAppId, $params);
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/weapp/deposit/recharge_new",
     *     summary="充值-新",
     *     tags={"储值"},
     *     description="对会员卡储值金额进行充值",
     *     operationId="rechargeNew",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="poiid", in="formData", description="", required=false, type="integer" ),
     *     @SWG\Parameter( name="shop_id", in="formData", description="门店id", required=false, type="integer" ),
     *     @SWG\Parameter( name="shop_name", in="formData", description="门店名称", required=false, type="string" ),
     *     @SWG\Parameter( name="recharge_rule_id", in="formData", description="充值规则id", required=false, type="string" ),
     *     @SWG\Parameter( name="total_fee", in="formData", description="充值金额", required=true, type="integer" ),
     *     @SWG\Parameter( name="member_card_code", in="formData", description="充值会员卡号", required=true, type="string" ),
     *     @SWG\Parameter( name="body", in="formData", description="描述", required=true, type="string" ),
     *     @SWG\Parameter( name="detail", in="formData", description="交易详情", required=true, type="string" ),
     *     @SWG\Parameter( name="pay_type", in="formData", description="充值支付方式", required=true, type="string" ),
     *     @SWG\Parameter( name="company_id", in="formData", description="企业id", required=true, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property(property="order_id", type="string", example="CZ3307736000010134", description="订单号"),
     *              @SWG\Property(property="pay_type", type="string", example="wxpay", description="支付类型"),
     *              @SWG\Property(property="order_type", type="recharge", example="6007e851b4b26", description="订单类型"),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DepositErrorRespones") ) )
     * )
     */
    public function rechargeNew(Request $request)
    {
        $depositTrade = new DepositTrade();

        if (intval($request->input('total_fee')) <= 0) {
            throw new StoreResourceFailedException('请填写正确的充值金额');
        }

        $authInfo = $request->get('auth');
        $params = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $authInfo['user_id'],
            'member_card_code' => $authInfo['user_card_code'],
            'money' => intval($request->input('total_fee')),
            'detail' => $request->input('detail', '充值储值'),
            'shop_id' => $request->input('shop_id', 0),
            'shop_name' => $request->input('shop_name', ''),
            'open_id' => $authInfo['open_id'] ?? '',
            'wxa_appid' => $authInfo['wxapp_appid'] ?? '',
            'mobile' => $authInfo['mobile'],
            'pay_type' => $request->input('pay_type', 'wxpayh5'),
        ];

        $wxaAppId = $authInfo['wxapp_appid'] ?? '';
        $authorizerAppId = $authInfo['woa_appid'] ?? '';

        $result = $depositTrade->rechargeNew($authorizerAppId, $wxaAppId, $params);
        $payResult = [
            'order_id' => $result['deposit_trade_id'],
            'pay_type' => $params['pay_type'],
            'order_type' => $result['trade_type'],
        ];
        return $this->response->array($payResult);
    }
}
