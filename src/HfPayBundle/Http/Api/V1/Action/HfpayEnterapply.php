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

namespace HfPayBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use HfPayBundle\Services\HfpayEnterapplyService;
use HfPayBundle\Services\AcouService;
use HfPayBundle\Services\HfBaseService;
use HfPayBundle\Services\HfpayLedgerConfigService;
use DistributionBundle\Services\DistributorService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;

class HfpayEnterapply extends Controller
{
    /**
     * @SWG\Get(
     *     path="/hfpay/enterapply/apply",
     *     summary="获取入驻信息",
     *     tags={"汇付天下"},
     *     description="获取入驻信息",
     *     operationId="apply",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="hfpay_enterapply_id", type="integer", description="法人证件类型"),
     *                     @SWG\Property(property="company_id", type="integer", description="公司company id"),
     *                     @SWG\Property(property="distributor_id", type="integer", description="店铺id"),
     *                     @SWG\Property(property="user_id", type="integer", description="用户id"),
     *                     @SWG\Property(property="user_cust_id", type="string", description="汇付客户号"),
     *                     @SWG\Property(property="acct_id", type="string", description="汇付子账户"),
     *                     @SWG\Property(property="apply_type", type="string", description="入驻类型"),
     *                     @SWG\Property(property="corp_license_type", type="string", description="企业证照类型"),
     *                     @SWG\Property(property="corp_name", type="string", description="企业名称"),
     *                     @SWG\Property(property="business_code", type="string", description="营业执照注册号"),
     *                     @SWG\Property(property="institution_code", type="string", description="组织机构代码"),
     *                     @SWG\Property(property="tax_code", type="string", description="税务登记证号"),
     *                     @SWG\Property(property="social_credit_code", type="string", description="统一社会信用代码"),
     *                     @SWG\Property(property="license_start_date", type="string", description="证照起始日期"),
     *                     @SWG\Property(property="license_end_date", type="string", description="证照结束日期"),
     *                     @SWG\Property(property="controlling_shareholder", type="string", description="实际控股人"),
     *                     @SWG\Property(property="legal_name", type="string", description="法人姓名"),
     *                     @SWG\Property(property="legal_id_card_type", type="string", description="法人证件类型"),
     *                     @SWG\Property(property="legal_id_card", type="string", description="法人证件号码"),
     *                     @SWG\Property(property="legal_cert_start_date", type="string", description="法人证件起始日期"),
     *                     @SWG\Property(property="legal_cert_end_date", type="string", description="法人证件结束日期"),
     *                     @SWG\Property(property="legal_mobile", type="string", description="法人手机号码"),
     *                     @SWG\Property(property="contact_name", type="string", description="企业联系人姓名"),
     *                     @SWG\Property(property="contact_mobile", type="string", description="企业联系人手机号"),
     *                     @SWG\Property(property="contact_email", type="string", description="联系人邮箱"),
     *                     @SWG\Property(property="bank_acct_name", type="string", description="开户银行账户名"),
     *                     @SWG\Property(property="bank_id", type="string", description="开户银行"),
     *                     @SWG\Property(property="bank_acct_num", type="string", description="开户银行账号"),
     *                     @SWG\Property(property="bank_prov", type="string", description="开户银行省份"),
     *                     @SWG\Property(property="bank_area", type="string", description="开户银行地区"),
     *                     @SWG\Property(property="solo_name", type="string", description="个体户名称"),
     *                     @SWG\Property(property="solo_business_address", type="string", description="个体户经营地址"),
     *                     @SWG\Property(property="solo_reg_address", type="string", description="个体户注册地址"),
     *                     @SWG\Property(property="solo_fixed_telephone", type="string", description="个体户固定电话"),
     *                     @SWG\Property(property="business_scope", type="string", description="经营范围"),
     *                     @SWG\Property(property="occupation", type="string", description="职业"),
     *                     @SWG\Property(property="user_name", type="string", description="用户姓名"),
     *                     @SWG\Property(property="id_card_type", type="string", description="证件类型"),
     *                     @SWG\Property(property="id_card", type="string", description="身份证号"),
     *                     @SWG\Property(property="user_mobile", type="string", description="手机号"),
     *                     @SWG\Property(property="hf_order_id", type="string", description="汇付订单号"),
     *                     @SWG\Property(property="hf_order_date", type="string", description="汇付订单日期"),
     *                     @SWG\Property(property="status", type="string", description="状态"),
     *                     @SWG\Property(property="business_code_img", type="string", description="营业执照注册号汇付文件id"),
     *                     @SWG\Property(property="institution_code_img", type="string", description="组织机构代码汇付文件id"),
     *                     @SWG\Property(property="tax_code_img", type="string", description="税务登记证号汇付文件id"),
     *                     @SWG\Property(property="social_credit_code_img", type="string", description="统一社会信用代码汇付文件id"),
     *                     @SWG\Property(property="legal_card_imgz", type="string", description="法人身份证正面照"),
     *                     @SWG\Property(property="legal_card_imgf", type="string", description="法人身份证反面照"),
     *                     @SWG\Property(property="created_at", type="datetime", description="创建时间"),
     *                     @SWG\Property(property="updated_at", type="datetime", description="最后修改时间"),
     *                     @SWG\Property(property="business_code_img_local", type="string", description="本地营业执照注册号图片"),
     *                     @SWG\Property(property="institution_code_img_local", type="string", description="本地组织机构代码图片"),
     *                     @SWG\Property(property="tax_code_img_local", type="string", description="本地税务登记证号图片"),
     *                     @SWG\Property(property="social_credit_code_img_local", type="string", description="本地统一社会信用代码图片"),
     *                     @SWG\Property(property="legal_card_imgz_local", type="string", description="本地法人身份证正面照"),
     *                     @SWG\Property(property="legal_card_imgf_local", type="string", description="本地法人身份证反面照"),
     *                     @SWG\Property(property="bank_name", type="string", description="开户银行名称"),
     *                     @SWG\Property(property="bank_prov_name", type="string", description="开户银行省份名称"),
     *                     @SWG\Property(property="bank_area_name", type="string", description="开户银行地区名称"),
     *                     @SWG\Property(property="resp_code", type="string", description="汇付响应码"),
     *                     @SWG\Property(property="resp_desc", type="string", description="汇付响应码描述"),
     *                     @SWG\Property(property="hf_apply_id", type="string", description="汇付开户申请号"),
     *                     @SWG\Property(property="bank_acct_img", type="string", description="开户银行许可证汇付文件id"),
     *                     @SWG\Property(property="bank_acct_img_local", type="string", description="本地开户银行许可证图片"),
     *                     @SWG\Property(property="bank_branch", type="string", description="企业开户银行的支行名称"),
     *                     @SWG\Property(property="business_code_img_full_url", type="string", description="本地营业执照注册号url地址"),
     *                     @SWG\Property(property="institution_code_img_full_url", type="string", description="本地组织机构代码url地址"),
     *                     @SWG\Property(property="tax_code_img_full_url", type="string", description="税务登记证号url地址"),
     *                     @SWG\Property(property="social_credit_code_img_full_url", type="string", description="统一社会信用代码url地址"),
     *                     @SWG\Property(property="legal_card_imgz_full_url", type="string", description="法人身份证正面照url地址"),
     *                     @SWG\Property(property="legal_card_imgf_full_url", type="string", description="法人身份证反面照url地址"),
     *                     @SWG\Property(property="bank_acct_img_full_url", type="string", description="开户银行许可证url地址"),
     *                     @SWG\Property(property="controlling_shareholder_cust_name", type="string", description="股东姓名"),
     *                     @SWG\Property(property="controlling_shareholder_id_card", type="string", description="股东身份证号码"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfPayErrorRespones") ) )
     * )
     */
    public function apply(Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['distributor_id'] = $request->input('distributor_id');
        $filter['apply_type'] = $request->input('apply_type', '1');
        $applyService = new HfpayEnterapplyService();
        $result = $applyService->getEnterapply($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/hfpay/enterapply/save",
     *     summary="保存入驻信息",
     *     tags={"汇付天下"},
     *     description="保存入驻信息",
     *     operationId="saveEnterapply",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=true, type="integer"),
     *     @SWG\Parameter( name="apply_type", in="query", description="入驻类型：1企业；2个体户", required=true, type="string"),
     *     @SWG\Parameter( name="corp_license_type", in="query", description="证照类型：1 普通证照；2 三证合一", required=true, type="string"),
     *     @SWG\Parameter( name="corp_name", in="query", description="企业的公司全称", type="string"),
     *     @SWG\Parameter( name="business_code", in="query", description="营业执照注册号  证照类型为1必传", type="string"),
     *     @SWG\Parameter( name="business_code_img", in="query", description="汇付营业执照汇付文件编号", type="string"),
     *     @SWG\Parameter( name="business_code_img_local", in="query", description="本地服务器营业执照图片文件", type="string"),
     *     @SWG\Parameter( name="institution_code", in="query", description="组织机构代码", type="string"),
     *     @SWG\Parameter( name="institution_code_img", in="query", description="组织机构代码汇付文件编号", type="string"),
     *     @SWG\Parameter( name="institution_code_img_local", in="query", description="本地服务器组织机构代码图片文件", type="string"),
     *     @SWG\Parameter( name="tax_code", in="query", description="税务登记证号", required=true, type="string"),
     *     @SWG\Parameter( name="tax_code_img", in="query", description="税务登记号汇付文件编号", required=true, type="string"),
     *     @SWG\Parameter( name="tax_code_img_local", in="query", description="本地服务器税务登记号图片文件", required=true, type="string"),
     *     @SWG\Parameter( name="social_credit_code", in="query", description="统一社会信用代码  证照类型为2必传", type="string"),
     *     @SWG\Parameter( name="social_credit_code_img", in="query", description="统一社会信用代码汇付文件编号", type="string"),
     *     @SWG\Parameter( name="social_credit_code_img_local", in="query", description="本地统一社会信用代码图片文件", type="string"),
     *     @SWG\Parameter( name="license_start_date", in="query", description="营业证照起始日期，精确到年月日", type="string"),
     *     @SWG\Parameter( name="license_end_date", in="query", description="营业证照结束日期，精确到年月日，支持“永久”", type="string"),
     *     @SWG\Parameter( name="controlling_shareholder_cust_name", in="query", description="控股股东 股东姓名", type="string"),
     *     @SWG\Parameter( name="controlling_shareholder_id_card", in="query", description="控股股东 股东身份证号码", type="string"),
     *     @SWG\Parameter( name="legal_name", in="query", description="法人姓名", type="string"),
     *     @SWG\Parameter( name="legal_id_card_type", in="query", description="法人证件类型 默认传10", type="string"),
     *     @SWG\Parameter( name="legal_id_card", in="query", description="证件号码", type="string"),
     *     @SWG\Parameter( name="legal_cert_start_date", in="query", description="法人的证件起始日期，精确到年月日", type="string"),
     *     @SWG\Parameter( name="legal_cert_end_date", in="query", description="法人的证件结束日期，精确到年月日，支持“永久”", type="string"),
     *     @SWG\Parameter( name="legal_mobile", in="query", description="法人手机号", type="string"),
     *     @SWG\Parameter( name="legal_card_imgz", in="query", description="法人身份证正面汇付文件编号", type="string"),
     *     @SWG\Parameter( name="legal_card_imgz_local", in="query", description="本地服务器法人身份证正面图片文件", type="string"),
     *     @SWG\Parameter( name="legal_card_imgf", in="query", description="法人身份证反面汇付文件编号", type="string"),
     *     @SWG\Parameter( name="legal_card_imgf_local", in="query", description="本地服务器法人身份证反面图片文件", type="string"),
     *     @SWG\Parameter( name="contact_name", in="query", description="企业联系人姓名", type="string"),
     *     @SWG\Parameter( name="contact_mobile", in="query", description="联系人手机号", type="string"),
     *     @SWG\Parameter( name="contact_email", in="query", description="联系人邮箱", type="string"),
     *     @SWG\Parameter( name="bank_acct_name", in="query", description="开户银行账户名", type="string"),
     *     @SWG\Parameter( name="bank_id", in="query", description="开户银行代号", type="string"),
     *     @SWG\Parameter( name="bank_name", in="query", description="开户银行名称", type="string"),
     *     @SWG\Parameter( name="bank_acct_num", in="query", description="开户银行账号", type="string"),
     *     @SWG\Parameter( name="bank_prov", in="query", description="银行卡开户省份编码", type="string"),
     *     @SWG\Parameter( name="bank_prov_name", in="query", description="银行卡开户省份名称", type="string"),
     *     @SWG\Parameter( name="bank_area", in="query", description="银行卡开户地区编码", type="string"),
     *     @SWG\Parameter( name="bank_area_name", in="query", description="银行卡开户地区名称", type="string"),
     *     @SWG\Parameter( name="solo_name", in="query", description="个体户名称 入驻类型为个体户必填", type="string"),
     *     @SWG\Parameter( name="solo_business_address", in="query", description="个体户经营地址  入驻类型为个体户必填", type="string"),
     *     @SWG\Parameter( name="solo_reg_address", in="query", description="个体户注册地址 入驻类型为个体户必填", type="string"),
     *     @SWG\Parameter( name="solo_fixed_telephone", in="query", description="个体户固定电话  入驻类型为个体户必填", type="string"),
     *     @SWG\Parameter( name="business_scope", in="query", description="经营范围  入驻类型为个体户必填", type="string"),
     *     @SWG\Parameter( name="occupation", in="query", description="职业  入驻类型为个体户必填", type="string"),
     *     @SWG\Parameter( name="bank_acct_img", in="query", description="开户银行许可证图片汇付文件编号", type="string"),
     *     @SWG\Parameter( name="bank_acct_img_local", in="query", description="本地开户银行许可证图片", type="string"),
     *     @SWG\Parameter( name="bank_branch", in="query", description="企业开户银行的支行名称", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="hfpay_enterapply_id", type="integer", description="法人证件类型"),
     *                     @SWG\Property(property="company_id", type="integer", description="公司company id"),
     *                     @SWG\Property(property="distributor_id", type="integer", description="店铺id"),
     *                     @SWG\Property(property="user_id", type="integer", description="用户id"),
     *                     @SWG\Property(property="user_cust_id", type="string", description="汇付客户号"),
     *                     @SWG\Property(property="acct_id", type="string", description="汇付子账户"),
     *                     @SWG\Property(property="apply_type", type="string", description="入驻类型"),
     *                     @SWG\Property(property="corp_license_type", type="string", description="企业证照类型"),
     *                     @SWG\Property(property="corp_name", type="string", description="企业名称"),
     *                     @SWG\Property(property="business_code", type="string", description="营业执照注册号"),
     *                     @SWG\Property(property="institution_code", type="string", description="组织机构代码"),
     *                     @SWG\Property(property="tax_code", type="string", description="税务登记证号"),
     *                     @SWG\Property(property="social_credit_code", type="string", description="统一社会信用代码"),
     *                     @SWG\Property(property="license_start_date", type="string", description="证照起始日期"),
     *                     @SWG\Property(property="license_end_date", type="string", description="证照结束日期"),
     *                     @SWG\Property(property="controlling_shareholder", type="string", description="实际控股人"),
     *                     @SWG\Property(property="legal_name", type="string", description="法人姓名"),
     *                     @SWG\Property(property="legal_id_card_type", type="string", description="法人证件类型"),
     *                     @SWG\Property(property="legal_id_card", type="string", description="法人证件号码"),
     *                     @SWG\Property(property="legal_cert_start_date", type="string", description="法人证件起始日期"),
     *                     @SWG\Property(property="legal_cert_end_date", type="string", description="法人证件结束日期"),
     *                     @SWG\Property(property="legal_mobile", type="string", description="法人手机号码"),
     *                     @SWG\Property(property="contact_name", type="string", description="企业联系人姓名"),
     *                     @SWG\Property(property="contact_mobile", type="string", description="企业联系人手机号"),
     *                     @SWG\Property(property="contact_email", type="string", description="联系人邮箱"),
     *                     @SWG\Property(property="bank_acct_name", type="string", description="开户银行账户名"),
     *                     @SWG\Property(property="bank_id", type="string", description="开户银行"),
     *                     @SWG\Property(property="bank_acct_num", type="string", description="开户银行账号"),
     *                     @SWG\Property(property="bank_prov", type="string", description="开户银行省份"),
     *                     @SWG\Property(property="bank_area", type="string", description="开户银行地区"),
     *                     @SWG\Property(property="solo_name", type="string", description="个体户名称"),
     *                     @SWG\Property(property="solo_business_address", type="string", description="个体户经营地址"),
     *                     @SWG\Property(property="solo_reg_address", type="string", description="个体户注册地址"),
     *                     @SWG\Property(property="solo_fixed_telephone", type="string", description="个体户固定电话"),
     *                     @SWG\Property(property="business_scope", type="string", description="经营范围"),
     *                     @SWG\Property(property="occupation", type="string", description="职业"),
     *                     @SWG\Property(property="user_name", type="string", description="用户姓名"),
     *                     @SWG\Property(property="id_card_type", type="string", description="证件类型"),
     *                     @SWG\Property(property="id_card", type="string", description="身份证号"),
     *                     @SWG\Property(property="user_mobile", type="string", description="手机号"),
     *                     @SWG\Property(property="hf_order_id", type="string", description="汇付订单号"),
     *                     @SWG\Property(property="hf_order_date", type="string", description="汇付订单日期"),
     *                     @SWG\Property(property="status", type="string", description="状态"),
     *                     @SWG\Property(property="business_code_img", type="string", description="营业执照注册号汇付文件id"),
     *                     @SWG\Property(property="institution_code_img", type="string", description="组织机构代码汇付文件id"),
     *                     @SWG\Property(property="tax_code_img", type="string", description="税务登记证号汇付文件id"),
     *                     @SWG\Property(property="social_credit_code_img", type="string", description="统一社会信用代码汇付文件id"),
     *                     @SWG\Property(property="legal_card_imgz", type="string", description="法人身份证正面照"),
     *                     @SWG\Property(property="legal_card_imgf", type="string", description="法人身份证反面照"),
     *                     @SWG\Property(property="created_at", type="datetime", description="创建时间"),
     *                     @SWG\Property(property="updated_at", type="datetime", description="最后修改时间"),
     *                     @SWG\Property(property="business_code_img_local", type="string", description="本地营业执照注册号图片"),
     *                     @SWG\Property(property="institution_code_img_local", type="string", description="本地组织机构代码图片"),
     *                     @SWG\Property(property="tax_code_img_local", type="string", description="本地税务登记证号图片"),
     *                     @SWG\Property(property="social_credit_code_img_local", type="string", description="本地统一社会信用代码图片"),
     *                     @SWG\Property(property="legal_card_imgz_local", type="string", description="本地法人身份证正面照"),
     *                     @SWG\Property(property="legal_card_imgf_local", type="string", description="本地法人身份证反面照"),
     *                     @SWG\Property(property="bank_name", type="string", description="开户银行名称"),
     *                     @SWG\Property(property="bank_prov_name", type="string", description="开户银行省份名称"),
     *                     @SWG\Property(property="bank_area_name", type="string", description="开户银行地区名称"),
     *                     @SWG\Property(property="resp_code", type="string", description="汇付响应码"),
     *                     @SWG\Property(property="resp_desc", type="string", description="汇付响应码描述"),
     *                     @SWG\Property(property="hf_apply_id", type="string", description="汇付开户申请号"),
     *                     @SWG\Property(property="bank_acct_img", type="string", description="开户银行许可证汇付文件id"),
     *                     @SWG\Property(property="bank_acct_img_local", type="string", description="本地开户银行许可证图片"),
     *                     @SWG\Property(property="bank_branch", type="string", description="企业开户银行的支行名称"),
     *                     @SWG\Property(property="business_code_img_full_url", type="string", description="本地营业执照注册号url地址"),
     *                     @SWG\Property(property="institution_code_img_full_url", type="string", description="本地组织机构代码url地址"),
     *                     @SWG\Property(property="tax_code_img_full_url", type="string", description="税务登记证号url地址"),
     *                     @SWG\Property(property="social_credit_code_img_full_url", type="string", description="统一社会信用代码url地址"),
     *                     @SWG\Property(property="legal_card_imgz_full_url", type="string", description="法人身份证正面照url地址"),
     *                     @SWG\Property(property="legal_card_imgf_full_url", type="string", description="法人身份证反面照url地址"),
     *                     @SWG\Property(property="bank_acct_img_full_url", type="string", description="开户银行许可证url地址"),
     *                     @SWG\Property(property="controlling_shareholder_cust_name", type="string", description="股东姓名"),
     *                     @SWG\Property(property="controlling_shareholder_id_card", type="string", description="股东身份证号码"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfPayErrorRespones") ) )
     * )
     */
    public function saveEnterapply(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $params['company_id'] = $companyId;

        $applyService = new HfpayEnterapplyService();
        $filter['company_id'] = $companyId;
        $filter['distributor_id'] = $params['distributor_id'];
        $data = $applyService->getEnterapply($filter);
        if ($data) {
            if (in_array($data['status'], ['2','3'])) {
                throw new ResourceException('该状态下不允许编辑');
            }
            $params['hfpay_enterapply_id'] = $data['hfpay_enterapply_id'];
        }
        if (empty($data)) {
            $params['status'] = '1';
        }
        // $params['legal_id_card_type'] = 10;
        $result = $applyService->save($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/hfpay/enterapply/getList",
     *     summary="获取店铺进件信息列表",
     *     tags={"汇付天下"},
     *     description="获取店铺进件信息列表",
     *     operationId="getApplyList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="path",
     *         description="页码",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="path",
     *         description="每页条数",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter( name="name", in="query", description="店铺名称", required=false, type="string"),
     *     @SWG\Parameter( name="province", in="query", description="省份", required=false, type="string"),
     *     @SWG\Parameter( name="city", in="query", description="城市", required=false, type="string"),
     *     @SWG\Parameter( name="area", in="query", description="区域", required=false, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="联系人手机号", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="hfpay_enterapply_id", type="integer", description="店铺进件表id"),
     *                     @SWG\Property(property="distributor_id", type="integer", description="店铺id"),
     *                     @SWG\Property(property="status", type="integer", description="状态"),
     *                     @SWG\Property(property="name", type="string", description="店铺名称"),
     *                     @SWG\Property(property="status_msg", type="string", description="状态描述"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfPayErrorRespones") ) )
     * )
     */
    public function getApplyList(Request $request)
    {
        // Powered by ShopEx EcShopX
        $companyId = app('auth')->user()->get('company_id');
        $applyService = new HfpayEnterapplyService();
        $filter['company_id'] = $companyId;
        $filter['apply_type'] = ['1','2','3'];
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $name = $request->input('name');
        $province = $request->input('province');
        $city = $request->input('city');
        $area = $request->input('area');
        $mobile = $request->input('mobile');
        $distributorId = $request->input('distributor_id');

        if ($name) {
            $filter['name|contains'] = $name;
        }
        if ($province) {
            $filter['province'] = $province;
        }
        if ($city) {
            $filter['city'] = $city;
        }
        if ($area) {
            $filter['area'] = $area;
        }
        if ($mobile) {
            $filter['mobile'] = $mobile;
        }
        if ($distributorId) {
            $filter['distributor_id'] = $distributorId;
        }
        $result = $applyService->getApplyList($filter, $page, $pageSize);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/hfpay/enterapply/hfkaihu",
     *     summary="企业个体户开户",
     *     tags={"汇付天下"},
     *     description="企业个体户开户",
     *     operationId="hfkaihu",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="hfpay_enterapply_id", type="integer", description="法人证件类型"),
     *                     @SWG\Property(property="company_id", type="integer", description="公司company id"),
     *                     @SWG\Property(property="distributor_id", type="integer", description="店铺id"),
     *                     @SWG\Property(property="user_id", type="integer", description="用户id"),
     *                     @SWG\Property(property="user_cust_id", type="string", description="汇付客户号"),
     *                     @SWG\Property(property="acct_id", type="string", description="汇付子账户"),
     *                     @SWG\Property(property="apply_type", type="string", description="入驻类型"),
     *                     @SWG\Property(property="corp_license_type", type="string", description="企业证照类型"),
     *                     @SWG\Property(property="corp_name", type="string", description="企业名称"),
     *                     @SWG\Property(property="business_code", type="string", description="营业执照注册号"),
     *                     @SWG\Property(property="institution_code", type="string", description="组织机构代码"),
     *                     @SWG\Property(property="tax_code", type="string", description="税务登记证号"),
     *                     @SWG\Property(property="social_credit_code", type="string", description="统一社会信用代码"),
     *                     @SWG\Property(property="license_start_date", type="string", description="证照起始日期"),
     *                     @SWG\Property(property="license_end_date", type="string", description="证照结束日期"),
     *                     @SWG\Property(property="controlling_shareholder", type="string", description="实际控股人"),
     *                     @SWG\Property(property="legal_name", type="string", description="法人姓名"),
     *                     @SWG\Property(property="legal_id_card_type", type="string", description="法人证件类型"),
     *                     @SWG\Property(property="legal_id_card", type="string", description="法人证件号码"),
     *                     @SWG\Property(property="legal_cert_start_date", type="string", description="法人证件起始日期"),
     *                     @SWG\Property(property="legal_cert_end_date", type="string", description="法人证件结束日期"),
     *                     @SWG\Property(property="legal_mobile", type="string", description="法人手机号码"),
     *                     @SWG\Property(property="contact_name", type="string", description="企业联系人姓名"),
     *                     @SWG\Property(property="contact_mobile", type="string", description="企业联系人手机号"),
     *                     @SWG\Property(property="contact_email", type="string", description="联系人邮箱"),
     *                     @SWG\Property(property="bank_acct_name", type="string", description="开户银行账户名"),
     *                     @SWG\Property(property="bank_id", type="string", description="开户银行"),
     *                     @SWG\Property(property="bank_acct_num", type="string", description="开户银行账号"),
     *                     @SWG\Property(property="bank_prov", type="string", description="开户银行省份"),
     *                     @SWG\Property(property="bank_area", type="string", description="开户银行地区"),
     *                     @SWG\Property(property="solo_name", type="string", description="个体户名称"),
     *                     @SWG\Property(property="solo_business_address", type="string", description="个体户经营地址"),
     *                     @SWG\Property(property="solo_reg_address", type="string", description="个体户注册地址"),
     *                     @SWG\Property(property="solo_fixed_telephone", type="string", description="个体户固定电话"),
     *                     @SWG\Property(property="business_scope", type="string", description="经营范围"),
     *                     @SWG\Property(property="occupation", type="string", description="职业"),
     *                     @SWG\Property(property="user_name", type="string", description="用户姓名"),
     *                     @SWG\Property(property="id_card_type", type="string", description="证件类型"),
     *                     @SWG\Property(property="id_card", type="string", description="身份证号"),
     *                     @SWG\Property(property="user_mobile", type="string", description="手机号"),
     *                     @SWG\Property(property="hf_order_id", type="string", description="汇付订单号"),
     *                     @SWG\Property(property="hf_order_date", type="string", description="汇付订单日期"),
     *                     @SWG\Property(property="status", type="string", description="状态"),
     *                     @SWG\Property(property="business_code_img", type="string", description="营业执照注册号汇付文件id"),
     *                     @SWG\Property(property="institution_code_img", type="string", description="组织机构代码汇付文件id"),
     *                     @SWG\Property(property="tax_code_img", type="string", description="税务登记证号汇付文件id"),
     *                     @SWG\Property(property="social_credit_code_img", type="string", description="统一社会信用代码汇付文件id"),
     *                     @SWG\Property(property="legal_card_imgz", type="string", description="法人身份证正面照"),
     *                     @SWG\Property(property="legal_card_imgf", type="string", description="法人身份证反面照"),
     *                     @SWG\Property(property="created_at", type="datetime", description="创建时间"),
     *                     @SWG\Property(property="updated_at", type="datetime", description="最后修改时间"),
     *                     @SWG\Property(property="business_code_img_local", type="string", description="本地营业执照注册号图片"),
     *                     @SWG\Property(property="institution_code_img_local", type="string", description="本地组织机构代码图片"),
     *                     @SWG\Property(property="tax_code_img_local", type="string", description="本地税务登记证号图片"),
     *                     @SWG\Property(property="social_credit_code_img_local", type="string", description="本地统一社会信用代码图片"),
     *                     @SWG\Property(property="legal_card_imgz_local", type="string", description="本地法人身份证正面照"),
     *                     @SWG\Property(property="legal_card_imgf_local", type="string", description="本地法人身份证反面照"),
     *                     @SWG\Property(property="bank_name", type="string", description="开户银行名称"),
     *                     @SWG\Property(property="bank_prov_name", type="string", description="开户银行省份名称"),
     *                     @SWG\Property(property="bank_area_name", type="string", description="开户银行地区名称"),
     *                     @SWG\Property(property="resp_code", type="string", description="汇付响应码"),
     *                     @SWG\Property(property="resp_desc", type="string", description="汇付响应码描述"),
     *                     @SWG\Property(property="hf_apply_id", type="string", description="汇付开户申请号"),
     *                     @SWG\Property(property="bank_acct_img", type="string", description="开户银行许可证汇付文件id"),
     *                     @SWG\Property(property="bank_acct_img_local", type="string", description="本地开户银行许可证图片"),
     *                     @SWG\Property(property="bank_branch", type="string", description="企业开户银行的支行名称"),
     *                     @SWG\Property(property="business_code_img_full_url", type="string", description="本地营业执照注册号url地址"),
     *                     @SWG\Property(property="institution_code_img_full_url", type="string", description="本地组织机构代码url地址"),
     *                     @SWG\Property(property="tax_code_img_full_url", type="string", description="税务登记证号url地址"),
     *                     @SWG\Property(property="social_credit_code_img_full_url", type="string", description="统一社会信用代码url地址"),
     *                     @SWG\Property(property="legal_card_imgz_full_url", type="string", description="法人身份证正面照url地址"),
     *                     @SWG\Property(property="legal_card_imgf_full_url", type="string", description="法人身份证反面照url地址"),
     *                     @SWG\Property(property="bank_acct_img_full_url", type="string", description="开户银行许可证url地址"),
     *                     @SWG\Property(property="controlling_shareholder_cust_name", type="string", description="股东姓名"),
     *                     @SWG\Property(property="controlling_shareholder_id_card", type="string", description="股东身份证号码"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfPayErrorRespones") ) )
     * )
     */
    public function hfKaiHu(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();

        $applyService = new HfpayEnterapplyService();

        $filter['company_id'] = $companyId;
        $filter['distributor_id'] = $params['distributor_id'];

        $data = $applyService->getEnterapply($filter);
        if (empty($data)) {
            throw new ResourceException('请先提交资质信息');
        }
        if (in_array($data['status'], ['2','3'])) {
            throw new ResourceException('请勿重复开户');
        }
        if (empty($data['hf_order_id'])) {
            $data['operate_type'] = 'A';
        } else {
            $data['operate_type'] = 'M';
        }
        //汇付开户接口
        $apiResult = [];
        $servce = new AcouService($companyId);
        switch ($data['apply_type']) {
            case '1':
                //附件信息
                $data['attach_nos'] = $data['legal_card_imgz'].','.$data['legal_card_imgf'].','.$data['bank_acct_img'];
                switch ($data['corp_license_type']) {
                    case '1':
                        $data['attach_nos'] .= ','.$data['business_code_img'].','.$data['institution_code_img'].','.$data['tax_code_img'];
                        break;
                    case '2':
                        $data['attach_nos'] .= ','.$data['social_credit_code_img'];
                        break;
                    default:
                        $data['attach_nos'] .= '';
                        break;
                }
                $apiResult = $servce->corp01($data);
                break;
            case '2':
                //附件信息
                $data['attach_nos'] = $data['business_code_img'].','.$data['legal_card_imgz'].','.$data['legal_card_imgf'].','.$data['bank_acct_num_imgz'].','.$data['bank_acct_num_imgz'];
                $apiResult = $servce->solo01($data);
                break;
            case '3':
                $data['card_num'] = $data['bank_acct_num'];
                $apiResult = $servce->bind01($data);
                break;
            default:
                throw new ResourceException('该类型暂不支持开户');
                break;
        }
        if (!in_array($apiResult['resp_code'], ['C00000','C00001','C00002'])) {
            throw new ResourceException($apiResult['resp_desc']);
        }
        if (isset($apiResult['order_id']) || isset($apiResult['order_date'])) {
            $editData['hf_order_id'] = $apiResult['order_id'];
            $editData['hf_order_date'] = $apiResult['order_date'];
            $editData['hf_apply_id'] = $apiResult['apply_id'] ?? '';
        }
        if (in_array($apiResult['resp_code'], ['C00001','C00002'])) {
            $editData['status'] = 2;
        }
        if ($apiResult['resp_code'] == 'C00000') {
            $editData['user_cust_id'] = $apiResult['user_cust_id'];
            $editData['acct_id'] = $apiResult['acct_id'];
            $editData['status'] = 3;
        }

        $editFilter = [
            'hfpay_enterapply_id' => $data['hfpay_enterapply_id'],
            'company_id' => $companyId,
        ];
        $result = $applyService->updateApply($editFilter, $editData);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/hfpay/enterapply/hffile",
     *     summary="汇付文件上传",
     *     tags={"汇付天下"},
     *     description="汇付文件上传",
     *     operationId="hffile",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="trans_type", in="query", description="1：代理商开户 2：生利宝开户 3：企业开户 4：个体户开户 5：线下充值批量代发", required=true, type="string"),
     *     @SWG\Parameter( name="attach_type", in="query", description="1：营业执照注册号 2：组织结构代码证 3：税务登记证 4：法人证件正面 5：开户银行许可证 6：统一社会信用代码 7：快捷协议 8：代扣协议 9：开户电子协议 10：法人证件反面 11：经营照片 12：经营照片（地址照片） 13：经营照片（门头照片） 14：结算卡正面 15：结算卡反面 16：经办人证件 99：其他", required=true, type="string"),
     *     @SWG\Parameter( name="file", in="formData", description="文件资源", required=true, type="file"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="attach_no", type="string", description="汇付文件信息"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfPayErrorRespones") ) )
     * )
     */
    public function hfFile(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();
        $params['file'] = $request->file('file');

        $baseService = new HfBaseService();
        $params['attach_no'] = $baseService->getAttachNo();

        $servce = new AcouService($companyId);
        $apiResult = $servce->file01($params);
        if (!in_array($apiResult['resp_code'], ['C00000','C00001','C00002'])) {
            throw new ResourceException($apiResult['resp_desc']);
        }
        $apiResult['attach_no'] = $params['attach_no'];
        return $this->response->array($apiResult);
    }

    /**
     * @SWG\Post(
     *     path="/hfpay/enterapply/opensplit",
     *     summary="店铺分账开关",
     *     tags={"汇付天下"},
     *     description="店铺分账开关",
     *     operationId="opensplit",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=true, type="integer"),
     *     @SWG\Parameter( name="is_open", in="query", description="是否分账 true开启分账 false 关闭分账， ture、false都是字符串类型", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="distributor_id", type="integer", description="店铺id"),
     *                     @SWG\Property(property="company_id", type="integer", description="公司id"),
     *                     @SWG\Property(property="mobile", type="string", description="店铺手机号"),
     *                     @SWG\Property(property="address", type="string", description="店铺地址"),
     *                     @SWG\Property(property="name", type="string", description="店铺名称"),
     *                     @SWG\Property(property="created", type="integer", description="创建时间"),
     *                     @SWG\Property(property="updated", type="integer", description="修改时间"),
     *                     @SWG\Property(property="is_valid", type="string", description="店铺是否有效"),
     *                     @SWG\Property(property="province", type="string", description="省份"),
     *                     @SWG\Property(property="city", type="string", description="城市"),
     *                     @SWG\Property(property="area", type="string", description="区域"),
     *                     @SWG\Property(property="regions_id", type="string", description="国家行政区划编码组合，逗号隔开"),
     *                     @SWG\Property(property="regions", type="string", description="地区名称组合。json格式"),
     *                     @SWG\Property(property="contact", type="string", description="联系人名称"),
     *                     @SWG\Property(property="child_count", type="integer", description=""),
     *                     @SWG\Property(property="shop_id", type="integer", description="门店id"),
     *                     @SWG\Property(property="is_default", type="integer", description=""),
     *                     @SWG\Property(property="is_ziti", type="integer", description="是否支持自提"),
     *                     @SWG\Property(property="lng", type="string", description="腾讯地图纬度"),
     *                     @SWG\Property(property="lat", type="string", description="腾讯地图经度"),
     *                     @SWG\Property(property="hour", type="string", description="营业时间"),
     *                     @SWG\Property(property="auto_sync_goods", type="integer", description="自动同步总部商品"),
     *                     @SWG\Property(property="logo", type="string", description="店铺logo"),
     *                     @SWG\Property(property="banner", type="string", description="店铺banner"),
     *                     @SWG\Property(property="is_audit_goods", type="integer", description="是否审核店铺商品"),
     *                     @SWG\Property(property="is_delivery", type="integer", description="是否支持配送"),
     *                     @SWG\Property(property="shop_code", type="string", description="店铺号"),
     *                     @SWG\Property(property="review_status", type="integer", description="入驻审核状态，0未审核，1已审核"),
     *                     @SWG\Property(property="source_from", type="integer", description="店铺来源，1管理端添加，2小程序申请入驻"),
     *                     @SWG\Property(property="distributor_self", type="integer", description="是否是总店配置"),
     *                     @SWG\Property(property="is_distributor", type="integer", description="是否是主店铺"),
     *                     @SWG\Property(property="contract_phone", type="string", description="其他联系方式"),
     *                     @SWG\Property(property="is_domestic", type="integer", description="是否是中国国内门店 1:国内(包含港澳台),2:非国内"),
     *                     @SWG\Property(property="is_direct_store", type="integer", description="是否为直营店 1:直营店,2:非直营店"),
     *                     @SWG\Property(property="wechat_work_department_id", type="integer", description="企业微信的部门ID"),
     *                     @SWG\Property(property="regionauth_id", type="string", description="区域id"),
     *                     @SWG\Property(property="is_open", type="string", description="是否开启分账"),
     *                     @SWG\Property(property="rate", type="integer", description="平台服务费率"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfPayErrorRespones") ) )
     * )
     */
    public function openSplit(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all();

        $hfpayLedgerConfigService = new HfpayLedgerConfigService();
        $ledgerConfig = $hfpayLedgerConfigService->getLedgerConfig(['company_id' => $companyId]);
        if (empty($ledgerConfig) || $ledgerConfig['is_open'] == 'false') {
            throw new ResourceException("请先在分账及结算-基础配置中开启分账");
        }

        $filter['company_id'] = $companyId;
        $filter['distributor_id'] = $params['distributor_id'];
        $applyService = new HfpayEnterapplyService();
        $data = $applyService->getEnterapply($filter);

        $editData['company_id'] = $companyId;
        if ($params['is_open'] == 'false') {
            $editData['is_open'] = 'false';
        }
        if ($params['is_open'] == 'true') {
            $editData['is_open'] = 'true';
        }
        if (empty($data) && $params['is_open'] == 'true') {
            $saveEnterapply = $applyService->createInitApply($companyId, $params['distributor_id']);
        }

        $distributorService = new DistributorService();
        $editDistrbutor = $distributorService->updateDistributor($params['distributor_id'], $editData);

        return $this->response->array($editDistrbutor);
    }
}
