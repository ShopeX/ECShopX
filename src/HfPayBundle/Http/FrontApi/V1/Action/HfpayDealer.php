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

namespace HfPayBundle\Http\FrontApi\V1\Action;

use HfPayBundle\Services\HfpayEnterapplyService;
use HfPayBundle\Services\AcouService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

class HfpayDealer extends Controller
{
    /**
     * @SWG\Get(
     *     path="/hfpay/saveDistributor",
     *     summary="分销员入驻",
     *     tags={"汇付天下"},
     *     description="分销员入驻",
     *     operationId="save",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter( name="user_name", in="query", description="用户姓名", required=true, type="string"),
     *     @SWG\Parameter( name="id_card", in="query", description="证件号", required=true, type="string"),
     *     @SWG\Parameter( name="user_mobile", in="query", description="手机号", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="hfpay_enterapply_id", type="integer", description="法人证件类型"),
     *                 @SWG\Property(property="company_id", type="integer", description="公司company id"),
     *                 @SWG\Property(property="distributor_id", type="integer", description="店铺id"),
     *                 @SWG\Property(property="user_id", type="integer", description="用户id"),
     *                 @SWG\Property(property="user_cust_id", type="stirng", description="汇付客户号"),
     *                 @SWG\Property(property="acct_id", type="stirng", description="汇付子账户"),
     *                 @SWG\Property(property="apply_type", type="stirng", description="入驻类型"),
     *                 @SWG\Property(property="corp_license_type", type="stirng", description="企业证照类型"),
     *                 @SWG\Property(property="corp_name", type="stirng", description="企业名称"),
     *                 @SWG\Property(property="business_code", type="stirng", description="营业执照注册号"),
     *                 @SWG\Property(property="institution_code", type="stirng", description="组织机构代码"),
     *                 @SWG\Property(property="tax_code", type="stirng", description="税务登记证号"),
     *                 @SWG\Property(property="social_credit_code", type="stirng", description="统一社会信用代码"),
     *                 @SWG\Property(property="license_start_date", type="stirng", description="证照起始日期"),
     *                 @SWG\Property(property="license_end_date", type="stirng", description="证照结束日期"),
     *                 @SWG\Property(property="controlling_shareholder", type="stirng", description="实际控股人"),
     *                 @SWG\Property(property="legal_name", type="stirng", description="法人姓名"),
     *                 @SWG\Property(property="legal_id_card_type", type="stirng", description="法人证件类型"),
     *                 @SWG\Property(property="legal_id_card", type="stirng", description="法人证件号码"),
     *                 @SWG\Property(property="legal_cert_start_date", type="stirng", description="法人证件起始日期"),
     *                 @SWG\Property(property="legal_cert_end_date", type="stirng", description="法人证件结束日期"),
     *                 @SWG\Property(property="legal_mobile", type="stirng", description="法人手机号码"),
     *                 @SWG\Property(property="contact_name", type="stirng", description="企业联系人姓名"),
     *                 @SWG\Property(property="contact_mobile", type="stirng", description="企业联系人手机号"),
     *                 @SWG\Property(property="contact_email", type="stirng", description="联系人邮箱"),
     *                 @SWG\Property(property="bank_acct_name", type="stirng", description="开户银行账户名"),
     *                 @SWG\Property(property="bank_id", type="stirng", description="开户银行"),
     *                 @SWG\Property(property="bank_acct_num", type="stirng", description="开户银行账号"),
     *                 @SWG\Property(property="bank_prov", type="stirng", description="开户银行省份"),
     *                 @SWG\Property(property="bank_area", type="stirng", description="开户银行地区"),
     *                 @SWG\Property(property="solo_name", type="stirng", description="个体户名称"),
     *                 @SWG\Property(property="solo_business_address", type="stirng", description="个体户经营地址"),
     *                 @SWG\Property(property="solo_reg_address", type="stirng", description="个体户注册地址"),
     *                 @SWG\Property(property="solo_fixed_telephone", type="stirng", description="个体户固定电话"),
     *                 @SWG\Property(property="business_scope", type="stirng", description="经营范围"),
     *                 @SWG\Property(property="occupation", type="stirng", description="职业"),
     *                 @SWG\Property(property="user_name", type="stirng", description="用户姓名"),
     *                 @SWG\Property(property="id_card_type", type="stirng", description="证件类型"),
     *                 @SWG\Property(property="id_card", type="stirng", description="身份证号"),
     *                 @SWG\Property(property="user_mobile", type="stirng", description="手机号"),
     *                 @SWG\Property(property="hf_order_id", type="stirng", description="汇付订单号"),
     *                 @SWG\Property(property="hf_order_date", type="stirng", description="汇付订单日期"),
     *                 @SWG\Property(property="status", type="stirng", description="状态"),
     *                 @SWG\Property(property="business_code_img", type="stirng", description="营业执照注册号图片"),
     *                 @SWG\Property(property="institution_code_img", type="stirng", description="组织机构代码图片"),
     *                 @SWG\Property(property="tax_code_img", type="stirng", description="税务登记证号图片"),
     *                 @SWG\Property(property="social_credit_code_img", type="stirng", description="统一社会信用代码图片"),
     *                 @SWG\Property(property="legal_card_imgz", type="stirng", description="法人身份证正面照"),
     *                 @SWG\Property(property="legal_card_imgf", type="stirng", description="法人身份证反面照"),
     *                 @SWG\Property(property="created_at", type="datetime", description="创建时间"),
     *                 @SWG\Property(property="updated_at", type="datetime", description="最后修改时间"),
     *                 @SWG\Property(property="business_code_img_local", type="stirng", description="本地营业执照注册号图片"),
     *                 @SWG\Property(property="institution_code_img_local", type="stirng", description="本地组织机构代码图片"),
     *                 @SWG\Property(property="tax_code_img_local", type="stirng", description="本地税务登记证号图片"),
     *                 @SWG\Property(property="social_credit_code_img_local", type="stirng", description="本地统一社会信用代码图片"),
     *                 @SWG\Property(property="legal_card_imgz_local", type="stirng", description="本地法人身份证正面照"),
     *                 @SWG\Property(property="legal_card_imgf_local", type="stirng", description="本地法人身份证反面照"),
     *                 @SWG\Property(property="bank_name", type="stirng", description="开户银行名称"),
     *                 @SWG\Property(property="bank_prov_name", type="stirng", description="开户银行省份名称"),
     *                 @SWG\Property(property="bank_area_name", type="stirng", description="开户银行地区名称"),
     *                 @SWG\Property(property="resp_code", type="stirng", description="汇付响应码"),
     *                 @SWG\Property(property="resp_desc", type="stirng", description="汇付响应码描述"),
     *                 @SWG\Property(property="hf_apply_id", type="stirng", description="汇付开户申请号"),
     *                 @SWG\Property(property="bank_acct_img", type="stirng", description="开户银行许可证图片"),
     *                 @SWG\Property(property="bank_acct_img_local", type="stirng", description="本地开户银行许可证图片"),
     *                 @SWG\Property(property="bank_branch", type="stirng", description="企业开户银行的支行名称")
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfpayErrorRespones") ) )
     * )
     */
    public function save(Request $request)
    {
        $authInfo = $request->get('auth');

        $companyId = $authInfo['company_id'];
        $params = $request->all();
        $params['company_id'] = $companyId;
        $params['user_id'] = $authInfo['user_id'];
        $params['apply_type'] = '3'; //入驻类型固定为经销商
        $params['id_card_type'] = '10';//证件类型固定为10 身份证

        $applyService = new HfpayEnterapplyService();
        $filter['company_id'] = $companyId;
        $filter['user_id'] = $authInfo['user_id'];
        $data = $applyService->getEnterapply($filter);
        if ($data) {
            if (in_array($data['status'], ['2','3'])) {
                throw new ResourceException('请勿重复申请');
            }
            $params['hfpay_enterapply_id'] = $data['hfpay_enterapply_id'];
        }
        if (empty($data) || empty($data['user_cust_id'])) {
            //汇付个人开户接口
            $servce = new AcouService($companyId);
            $apiResult = $servce->user01($params);
            if (!in_array($apiResult['resp_code'], ['C00000','C00001','C00002'])) {
                throw new ResourceException($apiResult['resp_desc']);
            }
            if (isset($apiResult['order_id']) || isset($apiResult['order_date'])) {
                $params['hf_order_id'] = $apiResult['order_id'];
                $params['hf_order_date'] = $apiResult['order_date'];
            }
            if (in_array($apiResult['resp_code'], ['C00001','C00002'])) {
                $params['status'] = 2;
            }
            if ($apiResult['resp_code'] == 'C00000') {
                $params['user_cust_id'] = $apiResult['user_cust_id'];
                $params['acct_id'] = $apiResult['acct_id'];
                $params['status'] = 3;
            }
        }

        $result = $applyService->save($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/hfpay/userapply",
     *     summary="获取经销商入驻信息",
     *     tags={"汇付天下"},
     *     description="获取经销商入驻信息",
     *     operationId="getInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                     property="data",
     *                     type="object",
     *                     @SWG\Property(property="hfpay_enterapply_id", type="integer", description="法人证件类型"),
     *                     @SWG\Property(property="company_id", type="integer", description="公司company id"),
     *                     @SWG\Property(property="distributor_id", type="integer", description="店铺id"),
     *                     @SWG\Property(property="user_id", type="integer", description="用户id"),
     *                     @SWG\Property(property="user_cust_id", type="stirng", description="汇付客户号"),
     *                     @SWG\Property(property="acct_id", type="stirng", description="汇付子账户"),
     *                     @SWG\Property(property="apply_type", type="stirng", description="入驻类型"),
     *                     @SWG\Property(property="corp_license_type", type="stirng", description="企业证照类型"),
     *                     @SWG\Property(property="corp_name", type="stirng", description="企业名称"),
     *                     @SWG\Property(property="business_code", type="stirng", description="营业执照注册号"),
     *                     @SWG\Property(property="institution_code", type="stirng", description="组织机构代码"),
     *                     @SWG\Property(property="tax_code", type="stirng", description="税务登记证号"),
     *                     @SWG\Property(property="social_credit_code", type="stirng", description="统一社会信用代码"),
     *                     @SWG\Property(property="license_start_date", type="stirng", description="证照起始日期"),
     *                     @SWG\Property(property="license_end_date", type="stirng", description="证照结束日期"),
     *                     @SWG\Property(property="controlling_shareholder", type="stirng", description="实际控股人"),
     *                     @SWG\Property(property="legal_name", type="stirng", description="法人姓名"),
     *                     @SWG\Property(property="legal_id_card_type", type="stirng", description="法人证件类型"),
     *                     @SWG\Property(property="legal_id_card", type="stirng", description="法人证件号码"),
     *                     @SWG\Property(property="legal_cert_start_date", type="stirng", description="法人证件起始日期"),
     *                     @SWG\Property(property="legal_cert_end_date", type="stirng", description="法人证件结束日期"),
     *                     @SWG\Property(property="legal_mobile", type="stirng", description="法人手机号码"),
     *                     @SWG\Property(property="contact_name", type="stirng", description="企业联系人姓名"),
     *                     @SWG\Property(property="contact_mobile", type="stirng", description="企业联系人手机号"),
     *                     @SWG\Property(property="contact_email", type="stirng", description="联系人邮箱"),
     *                     @SWG\Property(property="bank_acct_name", type="stirng", description="开户银行账户名"),
     *                     @SWG\Property(property="bank_id", type="stirng", description="开户银行"),
     *                     @SWG\Property(property="bank_acct_num", type="stirng", description="开户银行账号"),
     *                     @SWG\Property(property="bank_prov", type="stirng", description="开户银行省份"),
     *                     @SWG\Property(property="bank_area", type="stirng", description="开户银行地区"),
     *                     @SWG\Property(property="solo_name", type="stirng", description="个体户名称"),
     *                     @SWG\Property(property="solo_business_address", type="stirng", description="个体户经营地址"),
     *                     @SWG\Property(property="solo_reg_address", type="stirng", description="个体户注册地址"),
     *                     @SWG\Property(property="solo_fixed_telephone", type="stirng", description="个体户固定电话"),
     *                     @SWG\Property(property="business_scope", type="stirng", description="经营范围"),
     *                     @SWG\Property(property="occupation", type="stirng", description="职业"),
     *                     @SWG\Property(property="user_name", type="stirng", description="用户姓名"),
     *                     @SWG\Property(property="id_card_type", type="stirng", description="证件类型"),
     *                     @SWG\Property(property="id_card", type="stirng", description="身份证号"),
     *                     @SWG\Property(property="user_mobile", type="stirng", description="手机号"),
     *                     @SWG\Property(property="hf_order_id", type="stirng", description="汇付订单号"),
     *                     @SWG\Property(property="hf_order_date", type="stirng", description="汇付订单日期"),
     *                     @SWG\Property(property="status", type="stirng", description="状态"),
     *                     @SWG\Property(property="business_code_img", type="stirng", description="营业执照注册号图片"),
     *                     @SWG\Property(property="institution_code_img", type="stirng", description="组织机构代码图片"),
     *                     @SWG\Property(property="tax_code_img", type="stirng", description="税务登记证号图片"),
     *                     @SWG\Property(property="social_credit_code_img", type="stirng", description="统一社会信用代码图片"),
     *                     @SWG\Property(property="legal_card_imgz", type="stirng", description="法人身份证正面照"),
     *                     @SWG\Property(property="legal_card_imgf", type="stirng", description="法人身份证反面照"),
     *                     @SWG\Property(property="created_at", type="datetime", description="创建时间"),
     *                     @SWG\Property(property="updated_at", type="datetime", description="最后修改时间"),
     *                     @SWG\Property(property="business_code_img_local", type="stirng", description="本地营业执照注册号图片"),
     *                     @SWG\Property(property="institution_code_img_local", type="stirng", description="本地组织机构代码图片"),
     *                     @SWG\Property(property="tax_code_img_local", type="stirng", description="本地税务登记证号图片"),
     *                     @SWG\Property(property="social_credit_code_img_local", type="stirng", description="本地统一社会信用代码图片"),
     *                     @SWG\Property(property="legal_card_imgz_local", type="stirng", description="本地法人身份证正面照"),
     *                     @SWG\Property(property="legal_card_imgf_local", type="stirng", description="本地法人身份证反面照"),
     *                     @SWG\Property(property="bank_name", type="stirng", description="开户银行名称"),
     *                     @SWG\Property(property="bank_prov_name", type="stirng", description="开户银行省份名称"),
     *                     @SWG\Property(property="bank_area_name", type="stirng", description="开户银行地区名称"),
     *                     @SWG\Property(property="resp_code", type="stirng", description="汇付响应码"),
     *                     @SWG\Property(property="resp_desc", type="stirng", description="汇付响应码描述"),
     *                     @SWG\Property(property="hf_apply_id", type="stirng", description="汇付开户申请号"),
     *                     @SWG\Property(property="bank_acct_img", type="stirng", description="开户银行许可证图片"),
     *                     @SWG\Property(property="bank_acct_img_local", type="stirng", description="本地开户银行许可证图片"),
     *                     @SWG\Property(property="bank_branch", type="stirng", description="企业开户银行的支行名称")
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfpayErrorRespones") ) )
     * )
     */
    public function getInfo(Request $request)
    {
        $authInfo = $request->get('auth');
        // $params = $request->all();
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];

        $applyService = new HfpayEnterapplyService();
        $result = $applyService->getEnterapply($filter);

        return $this->response->array($result);
    }
}
