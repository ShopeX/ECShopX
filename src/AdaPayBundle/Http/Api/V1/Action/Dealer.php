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

namespace AdaPayBundle\Http\Api\V1\Action;

use AdaPayBundle\Services\AdapayLogService;
use AdaPayBundle\Services\DealerService;
use AdaPayBundle\Services\MemberService;
use App\Http\Controllers\Controller as Controller;
use CompanysBundle\Services\OperatorsService;
use DistributionBundle\Services\DistributorService;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Events\DistributorUpdateEvent;

class Dealer extends Controller
{
    /**
     * @SWG\Get(
     *     path="/adapay/dealer/list",
     *     summary="经销商列表",
     *     tags={"经销商"},
     *     description="经销商列表",
     *     operationId="dealerList",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="username", description="企业名称" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="contact", description="联系人" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="mobile", description="联系电话" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_start", description="创建时间（开始）" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_end", description="创建时间（结束）" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="open_account_start", description="开户时间（结束）" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="open_account_end", description="开户时间（结束）" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="operator_id", type="string", example="225", description="经销商id"),
     *                          @SWG\Property( property="username", type="string", example="null", description="企业名称"),
     *                          @SWG\Property( property="contact", type="string", example="66", description="联系人"),
     *                          @SWG\Property( property="mobile", type="string", example="12598712365", description="联系电话"),
     *                          @SWG\Property( property="created", type="string", example="1", description="创建时间"),
     *                          @SWG\Property( property="open_account_time", type="string", example="null", description="开户时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     * )
     */
    public function dealerList(Request $request)
    {
        // 456353686f7058
        $companyId = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $params = $request->all('username', 'contact', 'mobile', 'time_start', 'time_end', 'open_account_start', 'open_account_end');
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;
        $dealerService = new DealerService();
        $result = $dealerService->dealerListService($companyId, $params, $page, $pageSize);
        if ($operatorType != 'dealer') {//经销商端暂时不需要脱敏
            // 是否有权限查看加密数据
            $datapassBlock = $request->get('x-datapass-block');
            foreach ($result['list'] as &$value) {
                if ($datapassBlock) {
                    $value['mobile'] = data_masking('mobile', (string) $value['mobile']);
                    $value['contact'] = data_masking('truename', (string) $value['contact']);
                }
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/dealer/{id}",
     *     summary="查询经销商详情",
     *     tags={"经销商"},
     *     description="查询经销商详情",
     *     operationId="dealerInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="id", type="string", description="企业ID"),
     *                   @SWG\Property(property="member_id", type="string", description="用户ID"),
     *                   @SWG\Property(property="user_name", type="string", description="企业名称/用户名"),
     *                   @SWG\Property(property="prov_code", type="string", description="省份编码"),
     *                   @SWG\Property(property="area_code", type="string", description="地区编码"),
     *                   @SWG\Property(property="area", type="string", description="省市地区"),
     *                   @SWG\Property(property="cert_id", type="string", description="法人身份证/用户身份证"),
     *                   @SWG\Property(property="social_credit_code_expires", type="string", description="统一社会信用证有效期(1121)"),
     *                   @SWG\Property(property="social_credit_code", type="string", description="营业执照号"),
     *                   @SWG\Property(property="business_scope", type="string", description="经营范围"),
     *                   @SWG\Property(property="legal_person", type="string", description="法人姓名"),
     *                   @SWG\Property(property="legal_cert_id", type="string", description="法人身份证号码"),
     *                   @SWG\Property(property="legal_cert_id_expires", type="string", description="法人身份证有效期(20220112)"),
     *                   @SWG\Property(property="tel_no", type="string", description="法人手机号/个人手机号"),
     *                   @SWG\Property(property="address", type="string", description="企业地址"),
     *                   @SWG\Property(property="bank_code", type="string", description="银行代码"),
     *                   @SWG\Property(property="bank_name", type="string", description="银行名称"),
     *                   @SWG\Property(property="bank_acct_type", type="string", description="银行账户类型：1-对公；2-对私"),
     *                   @SWG\Property(property="card_no", type="string", description="银行卡号"),
     *                   @SWG\Property(property="zip_code", type="string", description="邮编"),
     *                   @SWG\Property(property="member_type", type="string", description="账户类型"),
     *                   @SWG\Property(property="div_fee_mode", type="string", description="分账扣费方式"),
     *                   @SWG\Property(property="split_ledger_info", type="object", description="分账比例",
     *                       @SWG\Property(property="adapay_fee_mode", type="string", description="手续费扣费方式"),
     *                       @SWG\Property(property="headquarters_proportion", type="string", description="分账总部占比"),
     *                       @SWG\Property(property="distributor_proportion", type="string", description="分账店铺占比"),
     *                       @SWG\Property(property="dealer_proportion", type="string", description="分账经销商占比"),
     *                   ),
     *                   @SWG\Property(property="card_name", type="string", description="银行卡对应的户名，若银行账户类型是对公，必须与企业名称一致"),
     *                   @SWG\Property(property="bank_card_name", type="string", description="银行卡对应的户名，若银行账户类型是对公，必须与企业名称一致"),
     *                   @SWG\Property( property="bank_tel_no", description="银行预留手机号", type="string"),
     *                   @SWG\Property( property="bank_cert_id", description="开户证件号", type="string"),
     *                   @SWG\Property( property="bank_card_id", description="银行卡号", type="string"),
     *                   @SWG\Property( property="basinInfo", description="基本信息", type="array",
     *                       @SWG\Items(
     *                         @SWG\Property( property="name", description="企业名称", type="string"),
     *                         @SWG\Property( property="contact", description="联系人", type="string"),
     *                         @SWG\Property( property="area", description="地区", type="string"),
     *                         @SWG\Property( property="email", description="企业邮箱", type="string"),
     *                         @SWG\Property( property="tel_no", description="企业电话", type="string"),
     *                       )
     *                   ),
     *                   @SWG\Property(property="attach_file", type="string", description="附件"),
     *                       @SWG\Property(property="disabled_type", type="string", description="可编辑状态：user 用户信息不可编辑，all 所有字段不可编辑"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AdaPayErrorResponse") ) )
     * )
     */
    public function dealerInfo($id, Request $request)
    {
        $dealerService = new DealerService();
        $result = $dealerService->dealerInfo($id);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block');
        if ($datapassBlock) {
            $result['basicInfo']['contact'] = data_masking('truename', (string) $result['basicInfo']['contact']);
            $result['basicInfo']['tel_no'] = data_masking('mobile', (string) $result['basicInfo']['tel_no']);
            isset($result['user_name']) and $result['user_name'] = data_masking('truename', (string) $result['user_name']);
            isset($result['tel_no']) and $result['tel_no'] = data_masking('mobile', (string) $result['tel_no']);
            isset($result['cert_id']) and $result['cert_id'] = data_masking('idcard', (string) $result['cert_id']);
            isset($result['bank_card_name']) and $result['bank_card_name'] = data_masking('truename', (string) $result['bank_card_name']);
            isset($result['bank_tel_no']) and $result['bank_tel_no'] = data_masking('mobile', (string) $result['bank_tel_no']);
            isset($result['bank_card_id']) and $result['bank_card_id'] = data_masking('bankcard', (string) $result['bank_card_id']);
            isset($result['bank_cert_id']) and $result['bank_cert_id'] = data_masking('idcard', (string) $result['bank_cert_id']);
            if (isset($result['member_type']) && $result['member_type'] == 'corp') {// 企业
                $result['legal_person'] = data_masking('truename', (string) $result['legal_person']);
                $result['legal_cert_id'] = data_masking('idcard', (string) $result['legal_cert_id']);
                $result['card_no'] = data_masking('bankcard', (string) $result['card_no']);
                $result['card_name'] = data_masking('truename', (string) $result['card_name']);
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/dealer/distributors",
     *     summary="经销商关联店铺列表",
     *     tags={"经销商"},
     *     description="经销商关联店铺列表",
     *     operationId="distributorList",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="dealer_id", description="经销商id" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="name", description="店铺名称" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="contact", description="联系人" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="mobile", description="联系电话" ),
     *     @SWG\Parameter( name="audit_state", in="query", description="状态包括：1 未入网, 2 待审核, 3 入网成功", required=false, type="string"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="province", description="省" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="city", description="市" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="area", description="区" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="adapay_fee_mode", description="手续费扣费方式" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_start", description="创建时间（开始）" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_end", description="创建时间（结束）" ),
     *     @SWG\Parameter( name="page_size", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="count", type="integer", example="1", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="distributor_id", type="string", example="225", description="店铺id"),
     *                          @SWG\Property( property="name", type="string", example="null", description="店铺名称"),
     *                          @SWG\Property( property="contact", type="string", example="66", description="联系人"),
     *                          @SWG\Property( property="mobile", type="string", example="12598712365", description="联系电话"),
     *                          @SWG\Property( property="address", type="string", example="12598712365", description="经营地址"),
     *                          @SWG\Property( property="audit_state", type="string", example="1", description="状态: 1 未入网, 2 待审核, 3 入网成功"),
     *                          @SWG\Property( property="created", type="string", example="1", description="创建时间"),
     *                          @SWG\Property(property="split_ledger_info", type="object", description="分账比例",
     *                              @SWG\Property(property="adapay_fee_mode", type="string", description="手续费扣费方式"),
     *                              @SWG\Property(property="headquarters_proportion", type="string", description="分账总部占比"),
     *                              @SWG\Property(property="distributor_proportion", type="string", description="分账店铺占比"),
     *                              @SWG\Property(property="dealer_proportion", type="string", description="分账经销商占比"),
     *                          ),
     *                       ),
     *                  ),
     *          ),
     *     )),
     * )
     */
    public function distributorList(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $params['company_id'] = $companyId;
        $dealerService = new DealerService();
        $result = $dealerService->getDistributorList($params);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block');
        foreach ($result['list'] as &$value) {
            if ($datapassBlock) {
                $value['mobile'] = data_masking('mobile', (string) $value['mobile']);
                $value['contact'] = data_masking('truename', (string) $value['contact']);
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/adapay/dealer/disable",
     *     summary="经销商开启或禁用",
     *     tags={"经销商"},
     *     description="经销商开启或禁用",
     *     operationId="openOrDisable",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="is_disable", description="是否禁用 1:禁用 0:开启" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="operator_id", description="operator_id" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     * )
     */
    public function openOrDisable(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $params = $request->all('is_disable', 'operator_id');

        $dealerService = new DealerService();
        $rs = $dealerService->operatorsRepository->updateOneBy(['company_id' => $companyId, 'operator_id' => $params['operator_id']], ['is_disable' => $params['is_disable']]);
        $distributorService = new DistributorService();

        $param['company_id'] = $companyId;
        if ($params['is_disable'] == 1) {
            $param['is_valid'] = 'false';
        } else {
            $param['is_valid'] = 'true';
        }
        if ($rs['distributor_ids']) {
//            $distributorIds = json_decode($rs['distributor_ids'], true);

            foreach ($rs['distributor_ids'] as $distributorId) {
                $param['distributor_id'] = $distributorId['distributor_id'];
                $data = $distributorService->updateDistributor($distributorId['distributor_id'], $param);

                event(new DistributorUpdateEvent($data));
            }
        }

        $operatorInfo = (new OperatorsService())->getInfo(['company_id' => $companyId, 'operator_id' => $params['operator_id']]);
        $name = $operatorInfo['username'];

        $logParams = [
            'company_id' => $companyId,
            'is_disable' => $params['is_disable'],
            'name' => $name
        ];
        $relMerchantId = app('auth')->user()->get('operator_id');

        // 找到被禁的主经销商ID
        if (isset($operatorInfo['is_dealer_main']) && !$operatorInfo['is_dealer_main']) {
            $relDealerId = $operatorInfo['dealer_parent_id'];
        } else {
            $relDealerId = $params['operator_id'];
        }
        $adapayLogService = new AdapayLogService();
        $adapayLogService->logRecord($logParams, $relMerchantId, 'dealer/disable', 'merchant');
        $adapayLogService->logRecord($logParams, $relDealerId, 'dealer/disable', 'dealer');

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/adapay/dealer/rel",
     *     summary="经销商关联店铺",
     *     tags={"经销商"},
     *     description="经销商关联店铺",
     *     operationId="dealerRelDistributor",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="operator_id", description="经销商id" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="distributor_id", description="店铺id" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="name", description="店铺名称" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="is_rel", description="是否关联  1:关联  0:取消关联" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="headquarters_proportion", description="总部占比" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="dealer_proportion", description="经销商占比" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     * )
     */
    public function dealerRelDistributor(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all('operator_id', 'distributor_id', 'name', 'is_rel', 'headquarters_proportion', 'dealer_proportion');
        $dealerService = new DealerService();
        $result = $dealerService->dealerRelDistributorService($companyId, $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/adapay/dealer/reset/{operator_id}",
     *     summary="经销商重置密码",
     *     tags={"经销商"},
     *     description="经销商重置密码",
     *     operationId="resetPassword",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="path", type="string", required=true, name="operator_id", description="经销商id" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     * )
     */
    public function resetPassword($operatorId)
    {
        $companyId = app('auth')->user()->get('company_id');
        $dealerService = new DealerService();
        $result = $dealerService->resetPasswordService($companyId, $operatorId);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/adapay/dealer/sub/del/{operator_id}",
     *     summary="删除经销商子账号",
     *     tags={"经销商"},
     *     description="删除经销商子账号",
     *     operationId="delDealerSub",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="path", type="string", required=true, name="operator_id", description="经销商id" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     * )
     */
    public function delDealerSub($operatorId)
    {
        $filter = [
            'operator_id' => $operatorId
        ];
        $operatorsService = new OperatorsService();
        $info = $operatorsService->getInfo($filter);
        if (!$info) {
            throw new ResourceException('未找到删除账号');
        }

        if ($info['is_dealer_main']) {
            throw new ResourceException('主账号不可删除');
        }
        $operatorsService->deleteBy($filter);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/adapay/dealer/update/{operator_id}",
     *     summary="经销商端账号编辑",
     *     tags={"经销商"},
     *     description="经销商端账号编辑",
     *     operationId="update",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="path", type="string", required=true, name="operator_id", description="经销商id" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="password", description="密码" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     * )
     */
    public function update(Request $request, $operatorId)
    {
        $params = $request->all('password');
        $rules = [
            'password' => ['required|min:6|max:16', '密码必须6-16位'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $operatorsService = new OperatorsService();
        $operatorsService->updateOperator($operatorId, $params);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/dealer/dealer_parent/get",
     *     summary="获取经销商主账号id",
     *     tags={"经销商"},
     *     description="获取经销商主账号id",
     *     operationId="getDealerParentId",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="operator_type", type="string", example="dealer"),
     *                  @SWG\Property( property="operator_id", type="string", example="1"),
     *                  @SWG\Property( property="dealer_parent_id", type="string", example="1"),
     *          ),
     *     )),
     * )
     */
    public function getDealerParentId()
    {
        $memberService = new MemberService();
        $operator = $memberService->getOperator();
        if ($operator['operator_type'] != 'dealer') {
            throw new ResourceException('登陆类型不是经销商');
        }
        $operator['dealer_parent_id'] = $operator['operator_id'];

        return $this->response->array($operator);
    }
}
