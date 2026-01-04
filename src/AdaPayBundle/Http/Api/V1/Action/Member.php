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

use AdaPayBundle\Services\CorpMemberService;
use AdaPayBundle\Services\MemberService;
// use AdaPayBundle\Services\Payments\AdaPaymentService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Member extends Controller
{
    /**
     * @SWG\Post(
     *     path="/adapay/member/create",
     *     summary="创建个人用户对象",
     *     tags={"Adapay"},
     *     description="创建个人用户对象",
     *     operationId="member_create",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="tel_no", in="formData", description="用户手机号", required=true, type="string"),
     *     @SWG\Parameter( name="user_name", in="formData", description="用户姓名", required=true, type="string"),
     *     @SWG\Parameter( name="cert_id", in="formData", description="身份证号", required=true, type="string"),
     *     @SWG\Parameter( name="cert_type", in="formData", description="证件类型，仅支持：00-身份证", required=false, type="string"),
     *     @SWG\Parameter( name="bank_card_id", in="formData", description="银行卡号", required=true, type="string"),
     *     @SWG\Parameter( name="bank_card_name", in="formData", description="银行卡对应的户名", required=true, type="string"),
     *     @SWG\Parameter( name="bank_cert_id", in="formData", description="开户证件号", required=true, type="string"),
     *     @SWG\Parameter( name="bank_cert_type", in="formData", description="开户证件类型，仅支持：00-身份证", required=false, type="string"),
     *     @SWG\Parameter( name="bank_tel_no", in="formData", description="银行预留手机号", required=true, type="string"),
     *     @SWG\Parameter( name="submit_review", in="formData", description="是否提交审核(Y/N)", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AdaPayErrorResponse") ) )
     * )
     */
    public function create(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $params['company_id'] = $companyId;
        $rules = [
            'tel_no' => ['required|size:11', '手机号码必须是11位'],
            'user_name' => ['required|max:15', '姓名必填且不能超过15个字符'],
            'cert_id' => ['required|size:18', '身份证号必须是18位'],
            'bank_card_name' => ['required|max:15', '开户人姓名必填且不能超过15个字符'],
            'bank_tel_no' => ['required|size:11', '银行预留手机号必须是11位'],
            'bank_card_id' => ['required', '银行账号必填'],
            'bank_cert_id' => ['required|size:18', '开户人身份证号必须是18位'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        //判断重复
        $filter = [
            'cert_id' => $params['cert_id'],
            'company_id' => $params['company_id'],
            'pid' => 0,
        ];
        $memberService = new MemberService($companyId);
        $params = $memberService->checkParams($params, true);//校验参数
        // if ($memberService->count($filter) >= 1) {
        //     throw new BadRequestHttpException('身份证号不能重复');
        // }
        $result = $memberService->createMember($params, true);
        if (!$result) {
            throw new BadRequestHttpException('用户创建失败');
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/adapay/member/update",
     *     summary="更新个人用户对象",
     *     tags={"Adapay"},
     *     description="更新个人用户对象",
     *     operationId="member_update",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="用户ID", required=true, type="string"),
     *     @SWG\Parameter( name="tel_no", in="formData", description="用户手机号", required=true, type="string"),
     *     @SWG\Parameter( name="user_name", in="formData", description="用户姓名", required=true, type="string"),
     *     @SWG\Parameter( name="cert_id", in="formData", description="身份证号", required=true, type="string"),
     *     @SWG\Parameter( name="cert_type", in="formData", description="证件类型，仅支持：00-身份证", required=false, type="string"),
     *     @SWG\Parameter( name="bank_card_id", in="formData", description="银行卡号", required=true, type="string"),
     *     @SWG\Parameter( name="bank_card_name", in="formData", description="银行卡对应的户名", required=true, type="string"),
     *     @SWG\Parameter( name="bank_cert_id", in="formData", description="开户证件号", required=true, type="string"),
     *     @SWG\Parameter( name="bank_cert_type", in="formData", description="开户证件类型，仅支持：00-身份证", required=false, type="string"),
     *     @SWG\Parameter( name="bank_tel_no", in="formData", description="银行预留手机号", required=true, type="string"),
     *     @SWG\Parameter( name="submit_review", in="formData", description="是否提交审核(Y/N)", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AdaPayErrorResponse") ) )
     * )
     */
    public function update(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        //$submitReview = $request->input('submit_review', 'N');
        $params = $request->all();
        $params['company_id'] = $companyId;
        $rules = [
            'member_id' => ['required', 'id必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $memberService = new MemberService();
        $params = $memberService->checkParams($params, true);//校验参数
        $result = $memberService->updateMember($params);


        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/adapay/member/modify",
     *     summary="修改个人用户对象(未开户)",
     *     tags={"Adapay"},
     *     description="修改个人用户对象(未开户)",
     *     operationId="member_modify",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="用户ID", required=true, type="string"),
     *     @SWG\Parameter( name="tel_no", in="formData", description="用户手机号", required=true, type="string"),
     *     @SWG\Parameter( name="user_name", in="formData", description="用户姓名", required=true, type="string"),
     *     @SWG\Parameter( name="cert_id", in="formData", description="身份证号", required=true, type="string"),
     *     @SWG\Parameter( name="cert_type", in="formData", description="证件类型，仅支持：00-身份证", required=false, type="string"),
     *     @SWG\Parameter( name="bank_card_id", in="formData", description="银行卡号", required=true, type="string"),
     *     @SWG\Parameter( name="bank_card_name", in="formData", description="银行卡对应的户名", required=true, type="string"),
     *     @SWG\Parameter( name="bank_cert_id", in="formData", description="开户证件号", required=true, type="string"),
     *     @SWG\Parameter( name="bank_cert_type", in="formData", description="开户证件类型，仅支持：00-身份证", required=false, type="string"),
     *     @SWG\Parameter( name="bank_tel_no", in="formData", description="银行预留手机号", required=true, type="string"),
     *     @SWG\Parameter( name="submit_review", in="formData", description="是否提交审核(Y/N)", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AdaPayErrorResponse") ) )
     * )
     */
    public function modify(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        //$submitReview = $request->input('submit_review', 'N');
        $params = $request->all();
        $params['company_id'] = $companyId;
        $rules = [
            'member_id' => ['required', 'id必填'],
            'tel_no' => ['required|size:11', '手机号码必须是11位'],
            'user_name' => ['required|max:15', '姓名必填且不能超过15个字符'],
            'cert_id' => ['required|size:18', '身份证号必须是18位'],
            'bank_card_name' => ['required|max:15', '开户人姓名必填且不能超过15个字符'],
            'bank_tel_no' => ['required|size:11', '银行预留手机号必须是11位'],
            'bank_card_id' => ['required', '银行账号必填'],
            'bank_cert_id' => ['required|size:18', '开户人身份证号必须是18位'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $memberService = new MemberService($companyId);
        $params = $memberService->checkParams($params, true);//校验参数
        $result = $memberService->modifyMember($params);
        if (!$result) {
            throw new BadRequestHttpException('用户更新失败');
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/member/get",
     *     summary="查询用户对象",
     *     tags={"Adapay"},
     *     description="查询个人用户对象",
     *     operationId="member_get",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="operator_id", in="query", description="operator_id", required=false, type="string"),
     *     @SWG\Parameter( name="operator_type", in="query", description="operator_type: distributor - 店铺; dealer - 经销商", required=false, type="string"),
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
     *                   @SWG\Property(property="split_ledger_info", type="string", description="分账比例",
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
     *                         @SWG\Property( property="name", description="店铺名/企业名称", type="string"),
     *                         @SWG\Property( property="contact", description="联系人", type="string"),
     *                         @SWG\Property( property="area", description="地区", type="string"),
     *                         @SWG\Property( property="email", description="企业邮箱", type="string"),
     *                         @SWG\Property( property="tel_no", description="企业电话", type="string"),
     *                         @SWG\Property( property="hour", description="营业时间", type="string"),
     *                         @SWG\Property( property="is_ziti", description="是否支持自提", type="string"),
     *                         @SWG\Property( property="auto_sync_goods", description="自动上下架", type="string"),
     *                         @SWG\Property( property="is_delivery", description="支持快递", type="string"),
     *                         @SWG\Property( property="is_dada", description="同城配送", type="string"),
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
    public function get(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $memberService = new MemberService($companyId);
        $filter = [
            'company_id' => $companyId,
        ];
        $result = $memberService->getMemberInfo($filter);

        if (isset($result['is_update']) && $result['is_update']) {
            $corpMemberService = new CorpMemberService();
            $result = $corpMemberService->waitDataTranf($result);
        }
        //拆分错误信息
        $audit_desc = $result['audit_desc'] ?? '';
        if ($audit_desc) {
            $audit_desc = explode('###', $audit_desc);
            $result['audit_desc_1'] = $audit_desc[0] ?? '';
            $result['audit_desc_2'] = $audit_desc[1] ?? '';
        }


        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/adapay/member/auditState",
     *     summary="查询用户对象状态",
     *     tags={"Adapay"},
     *     description="查询用户对象状态",
     *     operationId="getAuditState",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="audit_state", type="string", description="审核状态，状态包括： A-待审核；B-审核失败；C-开户成功;D-待提交"),
     *                   @SWG\Property(property="audit_desc", type="string", description="审核结果描述"),
     *                   @SWG\Property(property="member_type", type="string", description="开户类型:person-个人;corp-企业"),
     *                   @SWG\Property(property="update_time", type="string", description="更新时间"),
     *                   @SWG\Property(property="valid", type="boolean", description="是否点过结算中心"),
     *
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AdaPayErrorResponse") ) )
     * )
     */
    public function getAuditState(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $memberService = new MemberService($companyId);

        $filter = [
            'company_id' => $companyId,
        ];
        $result = $memberService->getAuditState($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/member/setValid",
     *     summary="设置进入结算页状态",
     *     tags={"Adapay"},
     *     description="设置进入结算页状态",
     *     operationId="setValid",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                  @SWG\Property(property="status", type="boolean"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AdaPayErrorResponse") ) )
     * )
     */
    public function setValid(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $filter['id'] = $request->get('member_id');
        $memberService = new MemberService($companyId);
        $memberService->setValid();
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/member/list",
     *     summary="adapay开户列表(店铺端 经销商端)",
     *     tags={"Adapay"},
     *     description="adapay开户列表(店铺端 经销商端)",
     *     operationId="lists",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="keywords", description="搜索条件" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="member_type", description="账户类型 person-个人   corp-企业" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="operator_type", description="distributor-店铺;dealer-经销" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="page", description="页码" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="page_size", description="页数" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="2", description="id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="location", type="string", example="1111", description="地址"),
     *                          @SWG\Property( property="user_name", type="string", example="", description="商户名称"),
     *                          @SWG\Property( property="member_type", type="string", example="person", description="账户类型 person-个人   corp-企业"),
     *                          @SWG\Property( property="operator_type", type="string", example="dealer", description="distributor-店铺;dealer-经销"),
     *                          @SWG\Property( property="legal_person", type="string", example="null", description="法人姓名"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     * )
     */
    public function lists(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all('member_type', 'operator_type', 'keywords');
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);
        $memberService = new MemberService();
        $result = $memberService->listsService($companyId, $params, $page, $pageSize);

        $datapassBlock = $request->get('x-datapass-block');

        if ($datapassBlock && isset($result['list']) && $result['list']) {
            foreach ($result['list'] as &$v) {
                $v['legal_person'] = $v['legal_person'] ? data_masking('truename', $v['legal_person']) : $v['legal_person'];
//                $v['location'] = $v['location'] ? data_masking('detailedaddress', $v['location']) : $v['location'];

                if ($v['member_type'] == 'person') {
                    $v['user_name'] = data_masking('truename', $v['user_name']);
                    $v['legal_person'] = $v['user_name'];
                }
            }
        }
        return $this->response->array($result);
    }
}
