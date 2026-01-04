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

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use AdaPayBundle\Services\CorpMemberService;
use AdaPayBundle\Services\BankCodeService;

// use PaymentBundle\Services\Payments\AdaPaymentService;

class CorpMember extends Controller
{
    /**
     * @SWG\Post(
     *     path="/adapay/corp_member/create",
     *     summary="创建企业用户对象",
     *     tags={"Adapay"},
     *     description="创建企业用户对象",
     *     operationId="corp_member_create",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="formData", description="企业名称", required=true, type="string"),
     *     @SWG\Parameter( name="area", in="formData", description="地区([1111,12121])", required=true, type="string"),
     *     @SWG\Parameter( name="social_credit_code", in="formData", description="统一社会信用码", required=true, type="string"),
     *     @SWG\Parameter( name="social_credit_code_expires", in="formData", description="统一社会信用证有效期(格式：YYYYMMDD，例如：20190909)", required=true, type="string"),
     *     @SWG\Parameter( name="business_scope", in="formData", description="经营范围", required=true, type="string"),
     *     @SWG\Parameter( name="legal_person", in="formData", description="法人姓名", required=true, type="string"),
     *     @SWG\Parameter( name="legal_cert_id", in="formData", description="法人身份证号码", required=true, type="string"),
     *     @SWG\Parameter( name="legal_cert_id_expires", in="formData", description="法人身份证有效期(20220112)", required=true, type="string"),
     *     @SWG\Parameter( name="legal_mp", in="formData", description="法人手机号", required=true, type="string"),
     *     @SWG\Parameter( name="address", in="formData", description="企业地址", required=true, type="string"),
     *     @SWG\Parameter( name="zip_code", in="formData", description="邮编", required=false, type="string"),
     *     @SWG\Parameter( name="telphone", in="formData", description="企业电话", required=false, type="string"),
     *     @SWG\Parameter( name="email", in="formData", description="企业邮箱", required=false, type="string"),
     *     @SWG\Parameter( name="attach_file", in="formData", description="上传附件(zip)", required=true, type="file"),
     *     @SWG\Parameter( name="bank_code", in="formData", description="银行代码", required=true, type="string"),
     *     @SWG\Parameter( name="bank_acct_type", in="formData", description="银行账户类型：1-对公；2-对私", required=true, type="string"),
     *     @SWG\Parameter( name="card_no", in="formData", description="银行卡号", required=true, type="string"),
     *     @SWG\Parameter( name="card_name", in="formData", description="银行卡对应的户名，若银行账户类型是对公，必须与企业名称一致", required=true, type="string"),
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
        $operatorId = app('auth')->user()->get('operator_id');
        //$submitReview = $request->input('submit_review', 'N');
        $params = $request->all();
        $params['company_id'] = $companyId;
        $params['operator_id'] = $operatorId;
        // if()
        $params['attach_file'] = $request->file('attach_file');
        $rules = [
            'name' => ['required', '企业名称必填'], //截取30个字符
            'area' => ['required', '地区必填'],
            'email' => ['email', '邮箱格式有误'],
            'bank_acct_type' => ['required|in:1,2', '银行账户类型错误'],
            'social_credit_code' => ['required', '营业执照号必填'],
            'social_credit_code_expires' => ['required', '商户有效日期必填'],
            'business_scope' => ['required', '经营范围必填'],
            'legal_person' => ['required', '法人姓名必填'],
            'legal_cert_id' => ['required|size:18', '法人身份证号码必须是18位'],
            'legal_cert_id_expires' => ['required', '法人身份证有效期必填'],
            'legal_mp' => ['required|size:11', '法人手机号必须是11位'],
            'address' => ['required', '企业地址必填'],
            'attach_file' => ['required', '附件必须上传'],
            'bank_code' => ['required', '请选择结算银行卡所属银行'],
            'card_no' => ['required', '银行卡号必填'],
            'card_name' => ['required|max:20', '银行卡对应的户名必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $corpMemberService = new CorpMemberService($companyId);
        $params = $corpMemberService->checkParams($params, true);//校验参数
        $result = $corpMemberService->createCorpMember($params);
        if (!$result) {
            throw new ResourceException('用户创建失败');
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/adapay/corp_member/modify",
     *     summary="修改企业用户对象(未开户重新提交)",
     *     tags={"Adapay"},
     *     description="修改企业用户对象(未开户重新提交)",
     *     operationId="corp_member_modify",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="member_id", in="path", description="用户ID", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="formData", description="企业名称", required=true, type="string"),
     *     @SWG\Parameter( name="area", in="formData", description="地区([1111,12121])", required=true, type="string"),
     *     @SWG\Parameter( name="social_credit_code", in="formData", description="统一社会信用码", required=true, type="string"),
     *     @SWG\Parameter( name="social_credit_code_expires", in="formData", description="统一社会信用证有效期(格式：YYYYMMDD，例如：20190909)", required=true, type="string"),
     *     @SWG\Parameter( name="business_scope", in="formData", description="经营范围", required=true, type="string"),
     *     @SWG\Parameter( name="legal_person", in="formData", description="法人姓名", required=true, type="string"),
     *     @SWG\Parameter( name="legal_cert_id", in="formData", description="法人身份证号码", required=true, type="string"),
     *     @SWG\Parameter( name="legal_cert_id_expires", in="formData", description="法人身份证有效期", required=true, type="string"),
     *     @SWG\Parameter( name="legal_mp", in="formData", description="法人手机号", required=true, type="string"),
     *     @SWG\Parameter( name="address", in="formData", description="企业地址", required=true, type="string"),
     *     @SWG\Parameter( name="zip_code", in="formData", description="邮编", required=false, type="string"),
     *     @SWG\Parameter( name="telphone", in="formData", description="企业电话", required=false, type="string"),
     *     @SWG\Parameter( name="email", in="formData", description="企业邮箱", required=false, type="string"),
     *     @SWG\Parameter( name="attach_file", in="formData", description="上传附件(zip)", required=false, type="file"),
     *     @SWG\Parameter( name="bank_code", in="formData", description="银行代码", required=true, type="string"),
     *     @SWG\Parameter( name="bank_acct_type", in="formData", description="银行账户类型：1-对公；2-对私", required=true, type="string"),
     *     @SWG\Parameter( name="card_no", in="formData", description="银行卡号", required=false, type="string"),
     *     @SWG\Parameter( name="card_name", in="formData", description="银行卡对应的户名，若银行账户类型是对公，必须与企业名称一致", required=false, type="string"),
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
        $operatorId = app('auth')->user()->get('operator_id');
        //$submitReview = $request->input('submit_review', 'N');
        $params = $request->all();
        $params['attach_file'] = $request->file('attach_file');
        $params['confirm_letter_file'] = $request->file('confirm_letter_file');
        //这里的operator_id是店铺ID，不能覆盖
        //$params['operator_id'] = $operatorId;
        $params['company_id'] = $companyId;
        $rules = [
            'member_id' => ['required','开户id不能为空'],
            'name' => ['required', '企业名称必填'],
            'area' => ['required', '地区必填'],
            'bank_acct_type' => ['required|in:1,2', '银行账户类型错误'],
            'social_credit_code' => ['required', '营业执照号必填'],
            'social_credit_code_expires' => ['required', '商户有效日期必填'],
            'business_scope' => ['required', '经营范围必填'],
            'legal_person' => ['required', '法人姓名必填'],
            'legal_cert_id' => ['required|size:18', '法人身份证号码必须是18位'],
            'legal_cert_id_expires' => ['required', '法人身份证有效期必填'],
            'legal_mp' => ['required|size:11', '法人手机号必须是11位'],
            'address' => ['required', '企业地址必填'],
            'bank_code' => ['required', '请选择结算银行卡所属银行'],
            'card_no' => ['required', '银行卡号必填'],
            'card_name' => ['required', '银行卡对应的户名必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $corpMemberService = new CorpMemberService($companyId);
        $params = $corpMemberService->checkParams($params, false);//校验参数
        $result = $corpMemberService->modifyCorpMember($params);
        if (!$result) {
            throw new BadRequestHttpException('用户更新失败');
        }
        //$memberId = $result['member_id'];

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/adapay/corp_member/update",
     *     summary="更新企业用户对象",
     *     tags={"Adapay"},
     *     description="更新企业用户对象",
     *     operationId="corp_member_update",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="member_id", in="path", description="用户ID", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="formData", description="企业名称", required=true, type="string"),
     *     @SWG\Parameter( name="area", in="formData", description="地区([1111,12121])", required=true, type="string"),
     *     @SWG\Parameter( name="social_credit_code", in="formData", description="统一社会信用码", required=true, type="string"),
     *     @SWG\Parameter( name="social_credit_code_expires", in="formData", description="统一社会信用证有效期(格式：YYYYMMDD，例如：20190909)", required=true, type="string"),
     *     @SWG\Parameter( name="business_scope", in="formData", description="经营范围", required=true, type="string"),
     *     @SWG\Parameter( name="legal_person", in="formData", description="法人姓名", required=true, type="string"),
     *     @SWG\Parameter( name="legal_cert_id", in="formData", description="法人身份证号码", required=true, type="string"),
     *     @SWG\Parameter( name="legal_cert_id_expires", in="formData", description="法人身份证有效期", required=true, type="string"),
     *     @SWG\Parameter( name="legal_mp", in="formData", description="法人手机号", required=true, type="string"),
     *     @SWG\Parameter( name="address", in="formData", description="企业地址", required=true, type="string"),
     *     @SWG\Parameter( name="zip_code", in="formData", description="邮编", required=false, type="string"),
     *     @SWG\Parameter( name="telphone", in="formData", description="企业电话", required=false, type="string"),
     *     @SWG\Parameter( name="email", in="formData", description="企业邮箱", required=false, type="string"),
     *     @SWG\Parameter( name="attach_file", in="formData", description="上传附件(zip)", required=false, type="file"),
     *     @SWG\Parameter( name="bank_code", in="formData", description="银行代码", required=true, type="string"),
     *     @SWG\Parameter( name="bank_acct_type", in="formData", description="银行账户类型：1-对公；2-对私", required=true, type="string"),
     *     @SWG\Parameter( name="card_no", in="formData", description="银行卡号", required=false, type="string"),
     *     @SWG\Parameter( name="card_name", in="formData", description="银行卡对应的户名，若银行账户类型是对公，必须与企业名称一致", required=false, type="string"),
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
        $operatorId = app('auth')->user()->get('operator_id');
        //$submitReview = $request->input('submit_review', 'N');
        $params = $request->all();
        $params['attach_file'] = $request->file('attach_file');
        //这里的operator_id是店铺ID，不能覆盖
        //$params['operator_id'] = $operatorId;
        $params['company_id'] = $companyId;
        $rules = [
            'member_id' => ['required','开户id不能为空'],
            'name' => ['required', '企业名称必填'],
            'area' => ['required', '地区必填'],
            'bank_acct_type' => ['required|in:1,2', '银行账户类型错误'],
            'social_credit_code' => ['required', '营业执照号必填'],
            'social_credit_code_expires' => ['required', '商户有效日期必填'],
            'business_scope' => ['required', '经营范围必填'],
            'legal_person' => ['required', '法人姓名必填'],
            'legal_cert_id' => ['required|size:18', '法人身份证号码必须是18位'],
            'legal_cert_id_expires' => ['required', '法人身份证有效期必填'],
            'legal_mp' => ['required|size:11', '法人手机号必须是11位'],
            'address' => ['required', '企业地址必填'],
            'bank_code' => ['required', '请选择结算银行卡所属银行'],
            'card_no' => ['required', '银行卡号必填'],
            'card_name' => ['required', '银行卡对应的户名必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $corpMemberService = new CorpMemberService($companyId);
        $params = $corpMemberService->checkParams($params, false);//校验参数
        $result = $corpMemberService->updateCorpMember($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/adapay/corp_member/get",
     *     summary="查询企业用户对象",
     *     tags={"Adapay"},
     *     description="查询企业用户对象",
     *     operationId="corp_member_get",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="member_id", in="path", description="用户ID", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="id", type="string", description="企业ID"),
     *                   @SWG\Property(property="member_id", type="string", description="用户ID"),
     *                   @SWG\Property(property="name", type="string", description="企业名称"),
     *                   @SWG\Property(property="prov_code", type="string", description="省份编码"),
     *                   @SWG\Property(property="area_code", type="string", description="地区编码"),
     *                   @SWG\Property(property="social_credit_code", type="string", description="统一社会信用码"),
     *                   @SWG\Property(property="social_credit_code_expires", type="string", description="统一社会信用证有效期(1121)"),
     *                   @SWG\Property(property="business_scope", type="string", description="经营范围"),
     *                   @SWG\Property(property="legal_person", type="string", description="法人姓名"),
     *                   @SWG\Property(property="legal_cert_id", type="string", description="法人身份证号码"),
     *                   @SWG\Property(property="legal_cert_id_expires", type="string", description="法人身份证有效期(20220112)"),
     *                   @SWG\Property(property="legal_mp", type="string", description="法人手机号"),
     *                   @SWG\Property(property="address", type="string", description="企业地址"),
     *                   @SWG\Property(property="bank_code", type="string", description="银行代码"),
     *                   @SWG\Property(property="bank_name", type="string", description="银行名称"),
     *                   @SWG\Property(property="bank_acct_type", type="string", description="银行账户类型：1-对公；2-对私"),
     *                   @SWG\Property(property="card_no", type="string", description="银行卡号"),
     *                   @SWG\Property(property="card_name", type="string", description="银行卡对应的户名，若银行账户类型是对公，必须与企业名称一致"),
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
        $operatorId = app('auth')->user()->get('operator_id');
        $filter = [
            'operator_id' => $operatorId,
            'company_id' => $companyId,
        ];
        $corpMemberService = new CorpMemberService($companyId);
        $result = $corpMemberService->getInfo($filter);
        if (!$result) {
            throw new BadRequestHttpException('用户信息不存在');
        }
        $result['disabled_type'] = '';
        // //根据审核状态判断可编辑字段
        // if ($result['audit_state'] == 'D') {
        //     //开户成功但未创建结算账户，可以修改电话
        //     $result['disabled_type'] = 'user';
        // }
        // if ($result['audit_state'] == 'E') {
        //     //开户和创建结算账户成功，不允许修改
        //     $result['disabled_type'] = 'all';
        // }

        $bankCode = $result['bank_code'] ?? '';
        if ($bankCode) {
            $banCodeService = new BankCodeService();
            $bankInfo = $banCodeService->getInfo(['bank_code' => $bankCode]);
            $result['bank_name'] = $bankInfo['bank_name'] ?? '';
        }

        if ($result['attach_file']) {
            $result['attach_file_url'] = $corpMemberService->getFileUrl($result['attach_file']);
        }
        if ($result['confirm_letter_file']) {
            $result['confirm_letter_file_url'] = $corpMemberService->getFileUrl($result['attach_file']);
        }
        //分账扣费方式
        $result['div_fee_mode'] = '内扣';
        // $service = new AdaPaymentService();
        // $result['balance'] = $service->balance($companyId, $member_id);

        return $this->response->array($result);
    }
}
