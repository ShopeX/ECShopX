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

namespace BsPayBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use BsPayBundle\Services\WithdrawApplyService;
use PaymentBundle\Services\Payments\BsPayService;
use BsPayBundle\Services\DivFeeService;
use Dingo\Api\Exception\ResourceException;
use ThirdPartyBundle\Http\Controllers\Controller;
use BsPayBundle\Traits\WithdrawFilterTrait;

/**
 * 提现相关接口
 */
class Withdraw extends Controller
{
    use WithdrawFilterTrait;

    /** @var WithdrawApplyService */
    private $withdrawApplyService;

    /** @var DivFeeService */
    private $divFeeService;

    /** @var BsPayService */
    private $bsPayService;

    public function __construct(WithdrawApplyService $withdrawApplyService)
    {
        // Log: 456353686f7058
        $this->withdrawApplyService = $withdrawApplyService;
        $this->divFeeService = new DivFeeService();
        $this->bsPayService = new BsPayService();
    }

    /**
     * @Route("/bspay/withdraw/balance", methods={"GET"})
     * @SWG\Get(
     *     path="/bspay/withdraw/balance",
     *     summary="查询提现余额",
     *     description="根据当前登录用户身份查询可提现余额和进行中余额",
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="available_balance", type="integer", description="可提现余额（分）"),
     *             @SWG\Property(property="pending_balance", type="integer", description="进行中余额（分）")
     *         )
     *     )
     * )
     */
    public function getBalance()
    {
        // 1. 从登录信息获取用户信息
        $user = app('auth')->user();
        $companyId = $user->get('company_id');
        $operatorType = $user->get('operator_type');
        $distributorId = $user->get('distributor_id');
        $merchantId = $user->get('merchant_id');
        
        $result = $this->withdrawApplyService->getUserBalance($companyId, $operatorType, $distributorId, $merchantId);
        
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/bspay/withdraw/apply",
     *     summary="申请提现",
     *     tags={"汇付斗拱提现"},
     *     description="申请提现，需要上传发票文件",
     *     operationId="applyWithdraw",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="amount", in="formData", description="申请金额", required=true, type="string"),
     *     @SWG\Parameter( name="withdraw_type", in="formData", description="提现类型", required=true, type="string"),
     *     @SWG\Parameter( name="invoice_url", in="formData", description="发票文件URL", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(property="apply_id", type="integer", description="申请ID"),
     *             @SWG\Property(property="status", type="integer", description="申请状态"),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ErrorResponse") ) )
     * )
     */
    public function apply(Request $request)
    {
        $params = $request->all();
        // 参数验证
        $rules = [
            'amount' => ['required|numeric|min:0.01', '提现金额必填且必须大于0.01元'],
            'withdraw_type' => ['required|in:T1', '提现类型必填且只能为T1'],
            'invoice_url' => ['required|url', '发票文件URL必填且必须是有效的URL地址']
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        
        // 获取当前用户信息
        $user = app('auth')->user();
        $params['company_id'] = $user->get('company_id');
        $params['operator_type'] = $user->get('operator_type');
        $params['operator_id'] = $user->get('operator_id');
        $params['operator'] = $user->get('mobile');

        // 从登录信息中直接获取对应ID并验证
        $params['merchant_id'] = 0;
        $params['distributor_id'] = 0;
        if ($params['operator_type'] === 'distributor') {
            $params['distributor_id'] = $user->get('distributor_id');
            if (!$params['distributor_id']) {
                throw new ResourceException('分销商身份信息异常，请重新登录');
            }
        } elseif ($params['operator_type'] === 'merchant') {
            $params['merchant_id'] = $user->get('merchant_id');
            if (!$params['merchant_id']) {
                throw new ResourceException('商户身份信息异常，请重新登录');
            }
        }

        // 申请提现
        $withdrawApply = $this->withdrawApplyService->applyWithdraw($params);

        // 返回结果
        return $this->response->array([
            'apply_id' => $withdrawApply['id'],
            'status' => $withdrawApply['status'],
            'amount' => $withdrawApply['amount']
        ]);
    }

    /**
     * @SWG\Get(
     *     path="/bspay/withdraw/lists",
     *     summary="获取提现记录列表",
     *     tags={"提现管理"},
     *     @SWG\Parameter( name="page", in="query", description="页码", required=false, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", required=false, type="integer"),
     *     @SWG\Parameter( name="status", in="query", description="申请状态：0=审核中 1=审核通过 2=已拒绝 3=处理中 4=处理成功 5=处理失败", required=false, type="integer"),
     *     @SWG\Parameter( name="type", in="query", description="列表类型：list=提现列表（需要数据隔离），audit=审核列表（不做数据隔离），默认list", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(property="list", type="array", @SWG\Items(
     *                 @SWG\Property(property="id", type="integer", description="提现申请ID"),
     *                 @SWG\Property(property="amount", type="integer", description="提现金额（分）"),
     *                 @SWG\Property(property="withdraw_type", type="string", description="提现类型，固定为T1"),
     *                 @SWG\Property(property="operator_type", type="string", enum={"distributor","merchant","admin","staff"}, description="操作者类型：distributor=店铺, merchant=商户, admin=超级管理员, staff=员工"),
     *                 @SWG\Property(property="operator_id", type="integer", description="操作者ID"),
     *                 @SWG\Property(property="operator", type="string", description="申请人账号"),
     *                 @SWG\Property(property="distributor_name", type="string", description="店铺名称"),
     *                 @SWG\Property(property="merchant_name", type="string", description="商户名称"),
     *                 @SWG\Property(property="status", type="integer", description="状态：0=审核中 1=审核通过 2=已拒绝 3=处理中 4=处理成功 5=处理失败"),
     *                 @SWG\Property(property="audit_time", type="integer", description="审核时间"),
     *                 @SWG\Property(property="auditor", type="string", description="审核人账号"),
     *                 @SWG\Property(property="audit_remark", type="string", description="审核备注"),
     *                 @SWG\Property(property="request_time", type="integer", description="请求汇付时间"),
     *                 @SWG\Property(property="failure_reason", type="string", description="失败原因"),
     *                 @SWG\Property(property="created", type="integer", description="创建时间"),
     *                 @SWG\Property(property="updated", type="integer", description="更新时间")
     *             )),
     *             @SWG\Property(property="total_count", type="integer", description="总记录数"),
     *             @SWG\Property(property="page", type="integer", description="当前页码"),
     *             @SWG\Property(property="page_size", type="integer", description="每页数量")
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ErrorResponse") ) )
     * )
     */
    public function lists(Request $request)
    {
        $params = $request->all();
        
        // 参数验证
        $rules = [
            'page' => ['nullable|integer|min:1', '页码必须为大于等于1的整数'],
            'pageSize' => ['required|integer|min:1|max:50', '每页数量为1-50的整数']
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        // 获取分页参数
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 20;

        // 构建过滤条件
        $filter = $this->buildWithdrawFilter($params, app('auth')->user());

        // 获取提现记录列表（包含店铺和商户名称）
        $result = $this->withdrawApplyService->getListsWithNames($filter, '*', $page, $pageSize, ['created' => 'DESC']);

        // 格式化返回数据
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/bspay/withdraw/audit",
     *     summary="审核提现申请",
     *     tags={"汇付斗拱提现"},
     *     description="平台审核提现申请",
     *     operationId="auditWithdraw",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="apply_id", in="formData", description="申请ID", required=true, type="integer"),
     *     @SWG\Parameter( name="action", in="formData", description="审核操作 approve/reject", required=true, type="string"),
     *     @SWG\Parameter( name="remark", in="formData", description="审核备注", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(property="status", type="boolean", description="操作是否成功"),
     *             @SWG\Property(property="data", type="object",
     *                 @SWG\Property(property="id", type="integer", description="提现申请ID"),
     *                 @SWG\Property(property="amount", type="integer", description="提现金额（分）"),
     *                 @SWG\Property(property="status", type="integer", description="状态：0=审核中 1=审核通过 2=已拒绝 3=处理中 4=处理成功 5=处理失败"),
     *                 @SWG\Property(property="audit_time", type="integer", description="审核时间"),
     *                 @SWG\Property(property="auditor", type="string", description="审核人"),
     *                 @SWG\Property(property="audit_remark", type="string", description="审核备注")
     *             )
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ErrorResponse") ) )
     * )
     */
    public function audit(Request $request)
    {
        // 参数验证
        $rules = [
            'apply_id' => ['required|integer', '申请ID必填且必须为整数'],
            'action' => ['required|in:approve,reject', '审核操作必填且只能为approve或reject'],
            'remark' => ['nullable|string|max:500', '审核备注不能超过500个字符']
        ];
        $errorMessage = validator_params($request->all(), $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params = $request->all();

        // 获取当前用户信息
        $user = app('auth')->user();
        $operatorType = $user->get('operator_type');
        
        // 只允许超级管理员和员工审核
        if (!in_array($operatorType, ['admin', 'staff'])) {
            throw new ResourceException('只有超级管理员和员工可以审核提现申请');
        }
        
        $auditorOperatorId = $user->get('operator_id');
        $auditor = $user->get('mobile');

        // 审核提现申请
        $result = $this->withdrawApplyService->auditWithdraw(
            $params['apply_id'],
            $params['action'],
            $auditor,
            $auditorOperatorId,
            $params['remark'] ?? ''
        );

        // 如果审核通过，投入队列异步处理汇付取现
        if ($result && $params['action'] === 'approve') {
            $job = (new \BsPayBundle\Jobs\WithdrawJob($params['apply_id']))->onQueue('default');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
            
            app('log')->info('提现申请审核::'.$params['action'].'，已投入队列处理 apply_id:' . $params['apply_id']);
        }

        // 返回结果
        return $this->response->array([
            'status' => true,
            'data' => [
                'id' => $result['id'],
                'amount' => $result['amount'],
                'status' => $result['status'],
                'audit_time' => $result['audit_time'],
                'auditor' => $result['auditor'],
                'audit_remark' => $result['audit_remark']
            ]
        ]);
    }



} 