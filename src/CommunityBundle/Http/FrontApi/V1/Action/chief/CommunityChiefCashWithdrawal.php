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

namespace CommunityBundle\Http\FrontApi\V1\Action\chief;

use App\Http\Controllers\Controller as BaseController;
use CommunityBundle\Services\CommunityChiefService;
use CommunityBundle\Services\CommunityChiefCashWithdrawalService;
use CommunityBundle\Services\CommunityChiefDistributorService;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;

class CommunityChiefCashWithdrawal extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/wxapp/community/chief/cash_withdrawal",
     *     summary="团长佣金提现申请",
     *     tags={"社区团"},
     *     description="团长佣金提现申请",
     *     operationId="applyCashWithdrawal",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter( name="money", in="query", description="提现金额", required=false, default="0", type="string"),
     *     @SWG\Parameter( name="pay_type", in="query", description="提现支付方式", required=false, default="wechat", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="id", type="integer", description="id"),
     *                    @SWG\Property(property="company_id", type="integer", description="company_id"),
     *                    @SWG\Property(property="chief_id", type="string", description="团长id"),
     *                    @SWG\Property(property="account_name", type="string", description="提现账号姓名"),
     *                    @SWG\Property(property="pay_account", type="string", description="提现账号 微信为openid 支付宝为支付宝账号 银行卡为银行卡号"),
     *                    @SWG\Property(property="mobile", type="string", description="手机号"),
     *                    @SWG\Property(property="money", type="integer", description="提现金额，以分为单位"),
     *                    @SWG\Property(property="status", type="string", description="提现状态"),
     *                    @SWG\Property(property="remarks", type="string", description="备注"),
     *                    @SWG\Property(property="pay_type", type="string", description="提现支付类型"),
     *                    @SWG\Property(property="wxa_appid", type="string", description="提现的小程序appid"),
     *                    @SWG\Property(property="created", type="integer", description="创建时间"),
     *                    @SWG\Property(property="updated", type="integer", description="修改时间"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function applyCashWithdrawal(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['chief_id']) {
            throw new ResourceException('只有团长才能操作');
        }

        //todo 当前只能绑定一个店铺
        $chiefDistributorService = new CommunityChiefDistributorService();
        $distributor = $chiefDistributorService->getInfo(['chief_id' => $authInfo['chief_id']]);
        if (empty($distributor)) {
            throw new ResourceException('当前团长没有配置店铺');
        }

        $data = [
            'mobile' => $authInfo['mobile'],
            'company_id' => $authInfo['company_id'],
            'distributor_id' => $distributor['distributor_id'],
            'open_id' => $authInfo['open_id'],
            'chief_id' => $authInfo['chief_id'],
            'wxa_appid' => $authInfo['wxapp_appid'],
            'account_name' => $authInfo['username'],
            'money' => $request->input('money', 0),
        ];

        if ($data['money'] < 100) {
            throw new ResourceException('佣金提现最少为1元');
        }

        $payType = $request->input('pay_type', 'wechat');
        if ($payType == 'wechat' && $data['money'] > 80000) {
            throw new ResourceException('佣金单次最多提现800元');
        }

        $data['pay_type'] = $payType;

        $cashWithdrawalService = new CommunityChiefCashWithdrawalService();
        $result = $cashWithdrawalService->applyCashWithdrawal($data);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/community/chief/cash_withdrawal",
     *     summary="团长佣金提现申请列表",
     *     tags={"社区团"},
     *     description="团长佣金提现申请列表",
     *     operationId="getCashWithdrawalList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页", required=true, default="0", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="分页页码", required=true, default="wechat", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="integer", example="4", description=""),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="id", type="string", example="9", description=""),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                           @SWG\Property(property="chief_id", type="string", example="20002", description="团长id"),
     *                           @SWG\Property(property="pay_account", type="string", example="18818266589", description="提现账号 微信为openid 支付宝为支付宝账号 银行卡为银行卡号"),
     *                           @SWG\Property(property="account_name", type="string", example="冯博", description="提现账号姓名"),
     *                           @SWG\Property(property="mobile", type="string", example="18818266589", description="手机号"),
     *                           @SWG\Property(property="money", type="integer", example="100", description="提现金额，以分为单位"),
     *                           @SWG\Property(property="status", type="string", example="success", description="提现状态"),
     *                           @SWG\Property(property="remarks", type="string", example="", description="备注"),
     *                           @SWG\Property(property="pay_type", type="string", example="alipay", description="提现支付类型"),
     *                           @SWG\Property(property="wxa_appid", type="string", example="wx912913df9fef6ddd", description="提现的小程序appid"),
     *                           @SWG\Property(property="created", type="integer", example="1582262981", description=""),
     *                           @SWG\Property(property="updated", type="integer", example="1582262995", description=""),
     *                           @SWG\Property(property="created_date", type="string", example="2020-02-21 13:29:41", description=""),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getCashWithdrawalList(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['chief_id']) {
            throw new ResourceException('只有团长才能操作');
        }

        $params = $request->all('pageSize', 'page');

        $rules = [
            'page' => ['required|integer|min:1', '分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:50', '每页最多查询50条数据'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        $filter['chief_id'] = $authInfo['chief_id'];

        $cashWithdrawalService = new CommunityChiefCashWithdrawalService();
        $result = $cashWithdrawalService->lists($filter, ["created" => "DESC"], $params['pageSize'], $params['page']);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/community/chief/cash_withdrawal/count",
     *     summary="团长业绩统计",
     *     tags={"社区团"},
     *     description="团长业绩统计",
     *     operationId="cashWithdrawalCount",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *                @SWG\Property(property="cash_withdrawal_rebate", type="string", description="可提现"),
     *                @SWG\Property(property="payed_rebate", type="string", description="已提现"),
     *                @SWG\Property(property="total_fee", type="integer", description="下单总金额"),
     *                @SWG\Property(property="rebate_total", type="string", description="佣金总额"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function cashWithdrawalCount(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['chief_id']) {
            throw new ResourceException('只有团长才能操作');
        }
        
        $result = [
            'total_fee' => 0, //下单总金额
            'rebate_total' => 0, //佣金总额
            'cash_withdrawal_rebate' => 0, //可提现佣金
            'payed_rebate' => 0, //已提现金额
        ];

        $cashWithdrawalService = new CommunityChiefCashWithdrawalService();
        $chiefRebate = $cashWithdrawalService->getChiefRebateCount($authInfo['company_id'], $authInfo['chief_id']);
        if (isset($chiefRebate[$authInfo['chief_id']])) {
            $result = [
                'total_fee' => $chiefRebate[$authInfo['chief_id']]['total_fee'],
                'rebate_total' => $chiefRebate[$authInfo['chief_id']]['rebate_total'],
                'cash_withdrawal_rebate' => $chiefRebate[$authInfo['chief_id']]['cash_withdrawal_rebate'] ?? 0,
                'payed_rebate' => $chiefRebate[$authInfo['chief_id']]['payed_rebate'] ?? 0,
            ];
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/community/chief/cash_withdrawal/account",
     *     summary="获取团长提现账户信息",
     *     tags={"社区团"},
     *     description="获取团长提现账户信息",
     *     operationId="getCashWithdrawalAccount",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="alipay_name", type="string", description="提现支付宝姓名"),
     *                 @SWG\Property(property="alipay_account", type="string", description="提现支付宝账号"),
     *                 @SWG\Property(property="bank_name", type="string", description="提现银行名称"),
     *                 @SWG\Property(property="bankcard_no", type="string", description="提现银行卡号"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getCashWithdrawalAccount(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['chief_id']) {
            throw new ResourceException('只有团长才能操作');
        }

        $chiefService = new CommunityChiefService();
        $chief = $chiefService->getInfoById($authInfo['chief_id']);
        if (!$chief) {
            throw new ResourceException('团长信息获取失败');
        }

        $result['alipay_name'] = $chief['alipay_name'];
        $result['alipay_account'] = $chief['alipay_account'];
        $result['bank_name'] = $chief['bank_name'];
        $result['bankcard_no'] = $chief['bankcard_no'];

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/community/chief/cash_withdrawal/account",
     *     summary="更新团长提现账户信息",
     *     tags={"社区团"},
     *     description="更新团长提现账户信息",
     *     operationId="updateCashWithdrawalAccount",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter( name="alipay_name", in="query", description="提现支付宝姓名", required=false, type="string"),
     *     @SWG\Parameter( name="alipay_account", in="query", description="提现支付宝账号", required=false, type="string"),
     *     @SWG\Parameter( name="bank_name", in="query", description="提现银行名称", required=false, type="string"),
     *     @SWG\Parameter( name="bankcard_no", in="query", description="提现银行卡号", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="stirng", example="true"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function updateCashWithdrawalAccount(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['chief_id']) {
            throw new ResourceException('只有团长才能操作');
        }

        $data = [];
        if ($request->input('alipay_name', null)) {
            $data['alipay_name'] = trim($request->input('alipay_name'));
        }

        if ($request->input('alipay_account', null)) {
            $data['alipay_account'] = trim($request->input('alipay_account'));
        }

        if ($request->input('bank_name', null)) {
            $data['bank_name'] = trim($request->input('bank_name'));
        }

        if ($request->input('bankcard_no', null)) {
            $data['bankcard_no'] = trim($request->input('bankcard_no'));
        }

        if ($data) {
            $chiefService = new CommunityChiefService();
            $chiefService->updateBy(['chief_id' => $authInfo['chief_id']], $data);
        }
        return $this->response->array(['status' => true]);
    }
}
