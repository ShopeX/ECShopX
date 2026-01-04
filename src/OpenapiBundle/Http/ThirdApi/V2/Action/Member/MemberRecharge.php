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

namespace OpenapiBundle\Http\ThirdApi\V2\Action\Member;

use Illuminate\Http\Request;
use OpenapiBundle\Http\Controllers\Controller as Controller;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Constants\ErrorCode;

use OpenapiBundle\Services\Member\MemberRechargeService as OpenapiMemberRechargeService;

class MemberRecharge extends Controller
{
    public $openapiMemberRechargeService;

    public function __construct()
    {
        $this->openapiMemberRechargeService = new OpenapiMemberRechargeService();
    }
    /**
     * @SWG\Post(
     *     path="/ecx.member.rechargerule.add",
     *     summary="新增储值面额规则",
     *     tags={"会员储值"},
     *     description="根据条件新增储值面额规则（最多可创建14条规则）。",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.rechargerule.add" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="fixed_money", description="需付金额（固定面额，以元为单位）" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="rule_type", description="充值类型（money:充值送钱 point:充值送积分）" ),
     *     @SWG\Parameter( in="query", type="integer", required=true, name="rule_data", description="赠送具体金额（以元为单位）或积分，不赠送传0" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="rechargerule_id", type="string", example="19", description="储值规则ID"),
     *                  @SWG\Property( property="money", type="string", example="0.07", description="需付金额（固定面额，以元为单位）"),
     *                  @SWG\Property( property="rule_type", type="string", example="point", description="充值类型（money:充值送钱 point:充值送积分）"),
     *                  @SWG\Property( property="rule_data", type="string", example="2", description="赠送具体金额（以元为单位）或积分"),
     *                  @SWG\Property( property="create_time", type="string", example="2021-07-01 17:45:57", description="创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function createRechargeRule(Request $request)
    {
        // Built with ShopEx Framework
        $params = $request->all('fixed_money', 'rule_type', 'rule_data');

        $rules = [
            'fixed_money' => ['required', '需付金额必填'],
            'rule_type' => ['required|in:money,point', '请填写正确的充值类型'],
            'rule_data' => ['required|integer|min:0', '请填写正确的赠送金额'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $companyId = $request->get('auth')['company_id'];

        try {
            $return = $this->openapiMemberRechargeService->createRule($companyId, $params);
        } catch (\Exception $e) {
            throw new ErrorException(ErrorCode::MEMBER_RECHARGE_ERROR, $e->getMessage());
        }
        return $this->response->array($return);
    }

    /**
     * @SWG\Delete(
     *     path="/ecx.member.rechargerule.delete",
     *     summary="删除储值面额规则",
     *     tags={"会员储值"},
     *     description="根据储值面额规则ID，删除已创建的储值规则。",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.rechargerule.delete" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="rechargerule_id", description="储值规则ID" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example=true),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function deleteRechargeRule(Request $request)
    {
        $params = $request->all('rechargerule_id');

        $rules = [
            'rechargerule_id' => ['required|integer|min:1', '请填写正确的储值规则ID'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $companyId = $request->get('auth')['company_id'];
        $this->openapiMemberRechargeService->deleteRule($companyId, $params['rechargerule_id']);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.member.rechargerule.update",
     *     summary="修改储值面额规则",
     *     tags={"会员储值"},
     *     description="根据储值面额规则ID，修改储值规则设置。",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.rechargerule.update" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="rechargerule_id", description="储值规则ID" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="fixed_money", description="需付金额（固定面额，以元为单位）" ),
     *     @SWG\Parameter( in="query", type="integer", required=true, name="rule_type", description="充值类型（money:充值送钱 point:充值送积分）" ),
     *     @SWG\Parameter( in="query", type="integer", required=true, name="rule_data", description="赠送具体金额（以元为单位）或积分，不赠送传0" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="sort", description="排序" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="rechargerule_id", type="string", example="19", description="储值规则ID"),
     *                  @SWG\Property( property="money", type="string", example="0.07", description="需付金额（固定面额，以元为单位）"),
     *                  @SWG\Property( property="rule_type", type="string", example="money", description="充值类型（money:充值送钱 point:充值送积分）"),
     *                  @SWG\Property( property="rule_data", type="string", example="2", description="赠送具体金额（以元为单位）或积分"),
     *                  @SWG\Property( property="create_time", type="string", example="2021-07-01 17:45:57", description="创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function updateRechargeRule(Request $request)
    {
        $params = $request->all('rechargerule_id', 'fixed_money', 'rule_type', 'rule_data');

        $rules = [
            'rechargerule_id' => ['required|integer|min:1', '请填写正确的储值规则ID'],
            'fixed_money' => ['required', '需付金额必填'],
            'rule_type' => ['required|in:money,point', '请填写正确的充值类型'],
            'rule_data' => ['required|integer|min:0', '请填写正确的赠送金额'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $companyId = $request->get('auth')['company_id'];

        try {
            $return = $this->openapiMemberRechargeService->updateRule($companyId, $params);
        } catch (\Exception $e) {
            throw new ErrorException(ErrorCode::MEMBER_RECHARGE_ERROR, $e->getMessage());
        }

        return $this->response->array($return);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member.rechargerule.get",
     *     summary="查询储值面额规则列表",
     *     tags={"会员储值"},
     *     description="查询已创建的储值面额规则列表。",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.rechargerule.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="rechargerule_id", type="string", example="19", description="储值规则ID"),
     *                          @SWG\Property( property="money", type="string", example="0.06", description="需付金额（固定面额，以元为单位）"),
     *                          @SWG\Property( property="rule_type", type="string", example="2", description="充值类型（money:充值送钱 point:充值送积分）"),
     *                          @SWG\Property( property="rule_data", type="string", example="3", description="赠送具体金额（以元为单位）或积分"),
     *                          @SWG\Property( property="create_time", type="string", example="2021-07-01 17:45:57", description="创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getRechargeRuleList(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];

        $return = $this->openapiMemberRechargeService->getAllRuleList($companyId);

        return $this->response->array($return);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member.recharge.trade.get",
     *     summary="查询储值交易记录",
     *     tags={"会员储值"},
     *     description="分页查询会员储值交易成功记录。",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.recharge.trade.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="当前页面，从1开始计数（不填默认1）" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="每页显示数量（不填默认20条）,最大为500" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="mobile", description="会员手机号" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="trade_id", description="交易流水号" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="shop_id", description="门店ID" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="date_begin", description="开始时间（交易创建时间，日期格式:yyyy-MM-dd HH:mm:ss）" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="date_end", description="结束时间（交易创建时间，日期格式:yyyy-MM-dd HH:mm:ss）" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property(property="total_count", type="integer", default="8", description="列表数据总数量"),
     *                  @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *                  @SWG\Property( property="pager", type="object",
     *                      ref="#definitions/Pager",
     *                  ),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="trade_id", type="string", example="CZ3462472000010032", description="交易流水号"),
     *                          @SWG\Property( property="trade_type", type="string", example="consume", description="交易类型充值或消费。recharge:充值;recharge_gift:赠送;consume:消费;recharge_send:返佣;refund:退回;"),
     *                          @SWG\Property( property="money", type="string", example="12.01", description="具体交易金额（以元为单位，金额>0）"),
     *                          @SWG\Property( property="mobile", type="string", example="15901872216", description="会员手机号  "),
     *                          @SWG\Property( property="member_card_code", type="string", example="45A8B38D10A1", description="储值会员卡号"),
     *                          @SWG\Property( property="shop_id", type="string", example="0", description="归属门店ID"),
     *                          @SWG\Property( property="shop_name", type="string", example="", description="归属门店名称"),
     *                          @SWG\Property( property="open_id", type="string", example="", description="会员openid"),
     *                          @SWG\Property( property="transaction_id", type="string", example="null", description="储值支付关联订单号"),
     *                          @SWG\Property( property="recharge_rule_id", type="string", example="null", description="关联储值规则ID"),
     *                          @SWG\Property( property="bank_type", type="string", example="null", description="付款所属银行"),
     *                          @SWG\Property( property="authorizer_appid", type="string", example="", description="关联公众号的appid"),
     *                          @SWG\Property( property="detail", type="string", example="购买商品", description="交易记录描述"),
     *                          @SWG\Property( property="time_start", type="string", example="", description="交易创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *                          @SWG\Property( property="time_expire", type="string", example="", description="交易结束时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getRechargeTradeList(Request $request)
    {
        $params = $request->all('page', 'page_size', 'mobile', 'trade_id', 'shop_id', 'date_begin', 'date_end');
        $params['page'] = $this->getPage();
        $params['page_size'] = $this->getPageSize();
        $rules = [
            'page' => ['integer|min:1', '当前页面最小值为1'],
            'page_size' => ['integer|min:1|max:500', '每页显示数量为1-500'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $companyId = $request->get('auth')['company_id'];
        $params['page_size'] = $this->getPageSize();
        $filter = [
            'company_id' => $companyId,
            'trade_status' => 'SUCCESS',
        ];
        if ($params['mobile']) {
            $filter['mobile'] = $params['mobile'];
        }
        if ($params['trade_id']) {
            $filter['deposit_trade_id'] = $params['trade_id'];
        }
        if ($params['shop_id']) {
            $filter['shop_id'] = $params['shop_id'];
        }
        if ($params['date_begin']) {
            $filter['date_begin'] = strtotime($params['date_begin']);
        }
        if ($params['date_end']) {
            $filter['date_end'] = strtotime($params['date_end']);
        }
        $return = $this->openapiMemberRechargeService->getTradeList($filter, (int)$params['page'], (int)$params['page_size']);
        return $this->response->array($return);
    }
}
