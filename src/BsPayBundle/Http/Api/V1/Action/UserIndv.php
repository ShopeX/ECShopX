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

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
// use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use BsPayBundle\Services\UserIndvService;
use BsPayBundle\Services\UserService;
// use BsPayBundle\Services\BankCodeService;

/**
 * 个人用户
 */
class UserIndv extends Controller
{
    /**
     * @SWG\Post(
     *     path="/bspay/user_indv/create",
     *     summary="创建个人用户对象",
     *     tags={"汇付斗拱"},
     *     description="创建个人用户对象",
     *     operationId="user_indv_create",
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
        $params = $request->all();
        // print_r($params);exit;
        $params['company_id'] = $companyId;
        $params['operator_id'] = $operatorId;
        $params['cert_no'] = trim($params['cert_no'] ?? '') ?? '';
        $params['card_no'] = trim($params['card_no'] ?? '') ?? '';
        $rules = [
            'name' => ['required', '个人姓名必填'],
            'cert_no' => ['required_if:card_type,1', '个人身份证号必填'],
            'cert_validity_type' => ['required', '个人身份证有效期类型必填'],
            'cert_begin_date' => ['required', '个人身份证有效期起始日期必填'],
            'cert_end_date' => ['required_if:cert_validity_type,0', '个人身份证有效期截止日期必填'],
            'mobile_no' => ['required|size:11', '手机号格式错误'],
            // 'card_type' => ['required|in:0,1,2', '银行卡类型错误'],
            // 'card_name' => ['required|max:20', '持卡人姓名必填'],
            'card_no' => ['required', '银行卡号必填'],
            'card_regions_id' => ['required', '银行卡开户地区必填'],
            // 'bank_code' => ['required_if:card_type,0', '银行号必填'],
            // 'branch_name' => ['required_if:card_type,0', '支行名称必填'],
            // 'cert_no' => ['required_if:card_type,1', '持卡人身份证号必填'],
            // 'cert_validity_type' => ['required', '持卡人身份证有效期类型必填'],
            // 'cert_begin_date' => ['required', '持卡人身份证有效期起始日期必填'],
            // 'cert_end_date' => ['required_if:cert_validity_type,0', '持卡人身份证有效期截止日期必填'],
            'mp' => ['required|size:11', '银行预留手机号格式错误'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $userIndvService = new UserIndvService();
        $params = $userIndvService->checkParams($params, true);//校验参数
        $result = $userIndvService->createUser($params);
        if (!$result) {
            throw new ResourceException('用户创建失败');
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/bspay/user_ent/modify",
     *     summary="修改企业用户对象(未开户重新提交)",
     *     tags={"汇付斗拱"},
     *     description="修改企业用户对象(未开户重新提交)",
     *     operationId="user_ent_modify",
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
        $params = $request->all();
        $params['company_id'] = $companyId;
        $rules = [
            'id' => ['required', 'Id必填'],
            'name' => ['required', '个人姓名必填'],
            'cert_no' => ['required_if:card_type,1', '个人身份证号必填'],
            'cert_validity_type' => ['required', '个人身份证有效期类型必填'],
            'cert_begin_date' => ['required', '个人身份证有效期起始日期必填'],
            'cert_end_date' => ['required_if:cert_validity_type,0', '个人身份证有效期截止日期必填'],
            'mobile_no' => ['required|size:11', '手机号格式错误'],
            // 'card_type' => ['required|in:0,1,2', '银行卡类型错误'],
            // 'card_name' => ['required|max:20', '持卡人姓名必填'],
            'card_no' => ['required', '银行卡号必填'],
            'card_regions_id' => ['required', '银行卡开户地区必填'],
            // 'bank_code' => ['required_if:card_type,0', '银行号必填'],
            // 'branch_name' => ['required_if:card_type,0', '支行名称必填'],
            // 'cert_no' => ['required_if:card_type,1', '持卡人身份证号必填'],
            // 'cert_validity_type' => ['required', '持卡人身份证有效期类型必填'],
            // 'cert_begin_date' => ['required', '持卡人身份证有效期起始日期必填'],
            // 'cert_end_date' => ['required_if:cert_validity_type,0', '持卡人身份证有效期截止日期必填'],
            'mp' => ['required|size:11', '银行预留手机号格式错误'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $userIndvService = new UserIndvService();
        $params = $userIndvService->checkParams($params, false);//校验参数
        $result = $userIndvService->modifyUser($params);
        if (!$result) {
            throw new ResourceException('用户更新失败');
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/bspay/user_ent/update",
     *     summary="更新企业用户对象（已开户）",
     *     tags={"汇付斗拱"},
     *     description="更新企业用户对象",
     *     operationId="user_ent_update",
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
        $params['company_id'] = $companyId;
        $rules = [
            'id' => ['required', 'Id必填'],
            'reg_name' => ['required', '企业名称必填'], //截取30个字符
            'license_code' => ['required', '营业执照号必填'],
            'license_validity_type' => ['required', '营业执照有效期类型必填'],
            'license_begin_date' => ['required', '营业执照有效期起始日期必填'],
            'license_end_date' => ['required_if:license_validity_type,0', '营业执照有效期结束日期必填'],
            'license_regions_id' => ['required', '注册地区必填'],
            'reg_detail' => ['required', '注册地址必填'],
            'legal_name' => ['required', '法人姓名必填'],
            'legal_cert_no' => ['required|size:18', '法人身份证号码必须是18位'],
            'legal_cert_validity_type' => ['required', '法人身份证有效期类型必填'],
            'legal_cert_begin_date' => ['required', '法人身份证有效期起始日期必填'],
            'legal_cert_end_date' => ['required_if:legal_cert_validity_type,0', '法人身份证有效期截止日期必填'],
            'contact_mobile' => ['required|size:11', '联系人手机必须是11位'],
            'ent_type' => ['required', '公司类型必填'],
            'card_type' => ['required|in:0,1,2', '银行卡类型错误'],
            'card_name' => ['required|max:20', '持卡人姓名必填'],
            'card_no' => ['required', '银行卡号必填'],
            'card_regions_id' => ['required', '银行卡开户地区必填'],
            'bank_code' => ['required_if:card_type,0', '银行号必填'],
            'cert_no' => ['required_if:card_type,1', '持卡人身份证号必填'],
            'cert_validity_type' => ['required', '持卡人身份证有效期类型必填'],
            'cert_begin_date' => ['required', '持卡人身份证有效期起始日期必填'],
            'cert_end_date' => ['required_if:cert_validity_type,0', '持卡人身份证有效期截止日期必填'],
            'mp' => ['required|size:11', '银行卡绑定手机号必须是11位'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $userIndvService = new UserIndvService();
        $params = $userIndvService->checkParams($params, false);//校验参数
        $result = $userIndvService->updateUser($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/bspay/user_ent/get",
     *     summary="查询企业用户对象",
     *     tags={"汇付斗拱"},
     *     description="查询企业用户对象",
     *     operationId="user_ent_get",
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
        $userService = new UserService();
        $companyId = app('auth')->user()->get('company_id');
        $operator = $userService->getOperator();
        // $operatorId = app('auth')->user()->get('operator_id');
        $filter = [
            'operator_id' => $operator['operator_id'],
            'company_id' => $companyId,
        ];
        $result = $userService->getUserDetail($filter);
        if (!$result) {
            throw new BadRequestHttpException('用户信息不存在');
        }
        $result['disabled_type'] = '';
        // //根据审核状态判断可编辑字段
        if ($result['audit_state'] == 'D') {
            //开户成功但未创建结算账户，可以修改电话
            $result['disabled_type'] = 'user';
        }
        if ($result['audit_state'] == 'E') {
            //开户和创建结算账户成功，不允许修改
            $result['disabled_type'] = 'all';
        }
        // $result['disabled_type'] = '';
        // //根据审核状态判断可编辑字段
        // if ($result['audit_state'] == 'D') {
        //     //开户成功但未创建结算账户，可以修改电话
        //     $result['disabled_type'] = 'user';
        // }
        // if ($result['audit_state'] == 'E') {
        //     //开户和创建结算账户成功，不允许修改
        //     $result['disabled_type'] = 'all';
        // }

        // $bankCode = $result['bank_code'] ?? '';
        // if ($bankCode) {
        //     $banCodeService = new BankCodeService();
        //     $bankInfo = $banCodeService->getInfo(['bank_code' => $bankCode]);
        //     $result['bank_name'] = $bankInfo['bank_name'] ?? '';
        // }

        // if ($result['attach_file']) {
        //     $result['attach_file_url'] = $corpMemberService->getFileUrl($result['attach_file']);
        // }
        // if ($result['confirm_letter_file']) {
        //     $result['confirm_letter_file_url'] = $corpMemberService->getFileUrl($result['attach_file']);
        // }
        //分账扣费方式
        // $result['div_fee_mode'] = '内扣';
        // $service = new AdaPaymentService();
        // $result['balance'] = $service->balance($companyId, $member_id);

        return $this->response->array($result);
    }
}
