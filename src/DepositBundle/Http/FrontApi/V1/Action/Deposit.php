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
use DepositBundle\Services\DepositTrade;
use PointBundle\Services\PointMemberService;
use Dingo\Api\Exception\StoreResourceFailedException;

class Deposit extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/deposit/list",
     *     summary="获取充值列表",
     *     tags={"储值"},
     *     description="获取充值列表",
     *     operationId="lists",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页数", type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页条数", type="integer" ),
     *     @SWG\Parameter( name="outin_type", in="query", description="交易类型充值或消费。outcome查消费", type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property(property="total_count", type="string", example="153", description="总条数"),
     *              @SWG\Property(property="list", type="array",
     *                  @SWG\Items( type="object",
     *                     @SWG\Property(property="depositTradeId", type="string", example="CZ3104683000010149", description="储值流水号"),
     *                     @SWG\Property(property="companyId", type="string", example="1", description="企业ID"),
     *                     @SWG\Property(property="memberCardCode", type="string", example="C3E4D04A0191", description="充值会员卡号"),
     *                     @SWG\Property(property="shopId", type="string", example="0", description="门店ID"),
     *                     @SWG\Property(property="shopName", type="string", example="天安门", description="门店名称"),
     *                     @SWG\Property(property="userId", type="string", example="20149", description="购买用户"),
     *                     @SWG\Property(property="mobile", type="string", example="18818266589", description="购买用户手机号"),
     *                     @SWG\Property(property="openId", type="string", example="oHxgH0VAiTFzBCRoGXPEhXb1RPhg", description="用户open_id"),
     *                     @SWG\Property(property="money", type="string", example="1", description="充值金额/消费金额"),
     *                     @SWG\Property(property="tradeType", type="string", example="recharge", description="交易类型充值或消费。consume:消费，recharge:充值"),
     *                     @SWG\Property(property="authorizerAppid", type="string", example="wx6b8c2837f47e8a09", description="公众号的appid"),
     *                     @SWG\Property(property="wxaAppid", type="string", example="wx912913df9fef6ddd", description="支付小程序的appid"),
     *                     @SWG\Property(property="detail", type="string", example="充值", description="交易详情"),
     *                     @SWG\Property(property="timeStart", type="string", example="1593594296", description="交易起始时间"),
     *                     @SWG\Property(property="timeExpire", type="string", example="1593594304", description="交易结束时间"),
     *                     @SWG\Property(property="tradeStatus", type="string", example="SUCCESS", description="交易状态"),
     *                     @SWG\Property(property="transactionId", type="string", example="4200000599202007019512737109", description="充值支付订单号"),
     *                     @SWG\Property(property="bankType", type="string", example="OTHERS", description="付款银行"),
     *                     @SWG\Property(property="rechargeRuleId", type="string", example="7", description="充值满足活动规则ID"),
     *                     @SWG\Property(property="payType", type="string", example="wxpay", description="充值支付方式"),
     *                     @SWG\Property(property="feeType", type="string", example="CNY", description="货币类型"),
     *                     @SWG\Property(property="curFeeType", type="string", example="CNY", description="系统配置货币类型"),
     *                     @SWG\Property(property="curFeeRate", type="string", example="1", description="系统配置货币汇率"),
     *                     @SWG\Property(property="curFeeSymbol", type="string", example="￥", description="系统配置货币符号"),
     *                     @SWG\Property(property="curPayFee", type="string", example="1", description="系统货币支付金额"),
     *              )),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DepositErrorRespones") ) )
     * )
     */
    public function lists(Request $request)
    {
        $depositTrade = new DepositTrade();
        $authInfo = $request->get('auth');

        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];
        $outinType = $request->input('outin_type');
        if ($outinType) {
            if ('outcome' == $outinType) {
                $filter['trade_type'] = 'consume';
            } else {
                $filter['trade_type|neq'] = 'consume';
            }
        }
        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);

        $filter['trade_status'] = 'SUCCESS';
        $list = $depositTrade->getDepositTradeList($filter, $pageSize, $page);
        return $this->response->array($list);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/deposit/info",
     *     summary="充值总金额",
     *     tags={"储值"},
     *     description="充值总金额",
     *     operationId="info",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *              @SWG\Property(property="user_id", type="string", example="153", description="用户id"),
     *              @SWG\Property(property="deposit", type="string", example="265", description="总额（余额）"),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DepositErrorRespones") ) )
     * )
     */
    public function info(Request $request)
    {
        // CRC: 2367340174
        $authInfo = $request->get('auth');

        $depositTrade = new DepositTrade();
        $result['company_id'] = $authInfo['company_id'];
        $result['user_id'] = $authInfo['user_id'];
        $result['deposit'] = $depositTrade->getUserDepositTotal($authInfo['company_id'], $authInfo['user_id']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/deposit/to/point",
     *     summary="储值兑换积分",
     *     tags={"储值"},
     *     description="储值兑换积分",
     *     operationId="depositToPoint",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="money", in="formData", description="金额", type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *              @SWG\Property(property="user_id", type="string", example="153", description="用户id"),
     *              @SWG\Property(property="deposit", type="string", example="265", description="余额"),
     *              @SWG\Property(property="point", type="string", example="156", description="积分"),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DepositErrorRespones") ) )
     * )
     */
    public function depositToPoint(Request $request)
    {
        // CRC: 2367340174
        $authInfo = $request->get('auth');
        $money = intval($request->input('money'));
        $depositTrade = new DepositTrade();
        $result['company_id'] = $authInfo['company_id'];
        $result['user_id'] = $authInfo['user_id'];
        $result['deposit'] = $depositTrade->getUserDepositTotal($authInfo['company_id'], $authInfo['user_id']);

        if ($money > $result['deposit']) {
            throw new StoreResourceFailedException('储值金额不足请充值');
        }

        $pointMemberService = new PointMemberService();
        $data = $authInfo;
        $data['money'] = $money;
        $pointMemberService->depositToPoint($data);
        $result['deposit'] = $depositTrade->getUserDepositTotal($authInfo['company_id'], $authInfo['user_id']);

        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];
        $pointMemberService = new PointMemberService();
        $pointInfo = $pointMemberService->getInfo($params);
        $result['point'] = $pointInfo['point'];
        return $this->response->array($result);
    }
}
