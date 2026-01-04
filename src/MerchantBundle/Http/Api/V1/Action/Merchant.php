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

namespace MerchantBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;

use MerchantBundle\Services\MerchantService;

class Merchant extends Controller
{
    /**
     * @SWG\Get(
     *     path="/merchant/list",
     *     summary="获取商户列表",
     *     tags={"商户"},
     *     description="获取商户列表。params参数为json_array。",
     *     operationId="getMerchantList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="params.page", in="query", description="当前页数", required=true, type="string"),
     *     @SWG\Parameter( name="params.page_size", in="query", description="每页数量", required=true, type="string"),
     *     @SWG\Parameter( name="params.merchant_name", in="query", description="商户名称", required=false, type="string"),
     *     @SWG\Parameter( name="params.legal_name", in="query", description="姓名", required=false, type="string"),
     *     @SWG\Parameter( name="params.legal_mobile", in="query", description="手机号", required=false, type="string"),
     *     @SWG\Parameter( name="params.time_start", in="query", description="入驻时间数组。时间格式", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="datapass_block", type="boolean", example="1", description="是否脱敏  1:是 0:否 "),
     *                  @SWG\Property( property="count", type="string", example="1", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="4", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
     *                          @SWG\Property( property="mobile", type="string", example="13100000000", description="注册手机号"),
     *                          @SWG\Property( property="merchant_type_id", type="string", example="3", description="商户类型ID（二级ID）"),
     *                          @SWG\Property( property="settled_type", type="string", example="soletrader", description="入驻类型。enterprise:企业;soletrader:个体户"),
     *                          @SWG\Property( property="merchant_name", type="string", example="商户名称", description="商户名称"),
     *                          @SWG\Property( property="social_credit_code_id", type="string", example="123213213", description="统一社会信用代码"),
     *                          @SWG\Property( property="province", type="string", example="上海市", description="省"),
     *                          @SWG\Property( property="city", type="string", example="上海市", description="市"),
     *                          @SWG\Property( property="area", type="string", example="徐汇区", description="区"),
     *                          @SWG\Property( property="regions_id", type="string", example="", description="地区编号集合(DC2Type:json_array)"),
     *                          @SWG\Property( property="address", type="string", example="详细地址", description="详细地址"),
     *                          @SWG\Property( property="legal_name", type="string", example="姓名啊", description="姓名"),
     *                          @SWG\Property( property="legal_cert_id", type="string", example="341231231231331=", description="身份证号码"),
     *                          @SWG\Property( property="legal_mobile", type="string", example="13100000000", description="手机号码"),
     *                          @SWG\Property( property="bank_acct_type", type="string", example="2", description="银行账户类型：1-对公；2-对私"),
     *                          @SWG\Property( property="card_id_mask", type="string", example="", description="结算银行卡号"),
     *                          @SWG\Property( property="bank_name", type="string", example="", description="结算银行卡所属银行名称"),
     *                          @SWG\Property( property="bank_mobile", type="string", example="13100000000", description="银行预留手机号"),
     *                          @SWG\Property( property="license_url", type="string", example="", description="营业执照图片url"),
     *                          @SWG\Property( property="legal_certid_front_url", type="string", example="", description="手持身份证正面url"),
     *                          @SWG\Property( property="legal_cert_id_back_url", type="string", example="", description="手持身份证反面url"),
     *                          @SWG\Property( property="bank_card_front_url", type="string", example="", description="结算银行卡正面url"),
     *                          @SWG\Property( property="contract_url", type="string", example="", description="合同url"),
     *                          @SWG\Property(property="audit_goods", type="boolean", example="1", description="是否需要平台审核商品 0:不需要 1:需要"),
     *                          @SWG\Property(property="settled_succ_sendsms", type="string", example="1", description="入驻成功发送时间  1:立即 2:商家H5确认入驻协议后"),
     *                          @SWG\Property( property="source", type="string", example="h5", description="来源 admin:平台管理员;h5:h5入驻;"),
     *                          @SWG\Property( property="created", type="string", example="1639638131", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1639649935", description="修改时间"),
     *
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function getList(request $request)
    {
        // 参数格式适配 前端finder组件
        $params = $request->input();
        if (is_string($params)) {
            $params = json_decode($params, true);
        }
        $rules = [
            'page' => ['required|integer|min:1', trans('MerchantBundle.current_page_positive_integer')],
            'pageSize' => ['required|integer|min:1|max:1000', trans('MerchantBundle.page_size_1_to_1000_integer')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $merchantService = new MerchantService();
        $filter['company_id'] = app('auth')->user()->get('company_id');
        if ($params['merchant_name'] ?? '') {
            $filter['merchant_name|contains'] = $params['merchant_name'];
        }
        if ($params['legal_name'] ?? '') {
            $filter['legal_name'] = $params['legal_name'];
        }
        if ($params['legal_mobile'] ?? '') {
            $filter['legal_mobile'] = $params['legal_mobile'];
        }
        if ($params['time_start'] ?? []) {
            $filter['created|gte'] = strtotime($params['time_start'][0]);
            $filter['created|lte'] = strtotime($params['time_start'][1]);
        }
        $result = $merchantService->lists($filter, '*', $params['page'], $params['pageSize'], ['created' => 'DESC']);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $result = $merchantService->merchantListDataMasking($result, $datapassBlock);
        $result['datapass_block'] = $datapassBlock;
        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/merchant/detail/{id}",
     *     summary="获取商户详情",
     *     tags={"商户"},
     *     description="获取商户详情。如果action=edit，则不返回脱敏数据。如果action=detail,则根据权限返回脱敏数据。",
     *     operationId="getMerchantDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="ID", required=true, type="string"),
     *     @SWG\Parameter( name="action", in="query", description="操作 edit:编辑 detail:详情。默认：detail", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example="5", description="ID"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
     *                  @SWG\Property( property="merchant_name", type="string", example="这是一个商户名称1", description="商户名称"),
     *                  @SWG\Property( property="merchant_type_id", type="string", example="3", description="商户类型ID（二级ID）"),
     *                  @SWG\Property( property="settled_type", type="string", example="enterprise", description="入驻类型。enterprise:企业;soletrader:个体户"),
     *                  @SWG\Property( property="social_credit_code_id", type="string", example="", description="统一社会信用代码"),
     *                  @SWG\Property( property="province", type="string", example="上海市", description="省"),
     *                  @SWG\Property( property="city", type="string", example="上海市", description="市"),
     *                  @SWG\Property( property="area", type="string", example="徐汇区", description="区"),
     *                  @SWG\Property( property="regions_id", type="string", example="", description="地区编号集合(DC2Type:json_array)"),
     *                  @SWG\Property( property="address", type="string", example="34322sssaaa是是是", description="详细地址"),
     *                  @SWG\Property( property="legal_name", type="string", example="姓名", description="姓名"),
     *                  @SWG\Property( property="legal_cert_id", type="string", example="", description="身份证号码"),
     *                  @SWG\Property( property="legal_mobile", type="string", example="", description="手机号码"),
     *                  @SWG\Property( property="bank_acct_type", type="string", example="2", description="银行账户类型：1-对公；2-对私"),
     *                  @SWG\Property( property="card_id_mask", type="string", example="", description="结算银行卡号"),
     *                  @SWG\Property( property="bank_name", type="string", example="42sss是是是", description="结算银行卡所属银行名称"),
     *                  @SWG\Property( property="bank_mobile", type="string", example="13000000000", description="银行预留手机号"),
     *                  @SWG\Property( property="license_url", type="string", example="", description="营业执照图片url"),
     *                  @SWG\Property( property="legal_certid_front_url", type="string", example="", description="手持身份证正面url"),
     *                  @SWG\Property( property="legal_cert_id_back_url", type="string", example="", description="手持身份证反面url"),
     *                  @SWG\Property( property="bank_card_front_url", type="string", example="", description="结算银行卡正面url"),
     *                  @SWG\Property( property="contract_url", type="string", example="null", description="合同url"),
     *                  @SWG\Property( property="settled_succ_sendsms", type="string", example="1", description="入驻成功发送时间  1:立即 2:商家H5确认入驻协议后"),
     *                  @SWG\Property( property="audit_goods", type="boolean", example="0", description="是否需要平台审核商品 0:不需要 1:需要"),
     *                  @SWG\Property( property="source", type="string", example="h5", description="来源 admin:平台管理员;h5:h5入驻;"),
     *                  @SWG\Property( property="disabled", type="boolean", example="false", description="是否禁用。0:可用；1:禁用"),
     *                  @SWG\Property( property="created", type="string", example="1639720801", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="1639982413", description="修改时间"),
     *                  @SWG\Property( property="datapass_block", type="boolean", example="1", description="是否脱敏  1:是 0:否 "),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function getDetail($id, Request $request)
    {
        // 456353686f7058
        $merchantService = new MerchantService();
        $result = $merchantService->getMerchantDetail($id);
        $action = $request->input('action', 'detail');
        if ($action == 'edit') {
            return $this->response->array($result);
        }
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $result['datapass_block'] = $datapassBlock;
        $result = $merchantService->merchantDetailDataMasking($result, $datapassBlock);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/merchant/{id}",
     *     summary="更新商户",
     *     tags={"商户"},
     *     description="更新商户",
     *     operationId="updateMerchant",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="merchant_type_id", in="query", type="string", required=true, description="经营范围ID。如果商户类型下没有经营范围，传商户类型ID。"),
     *     @SWG\Parameter( name="regions_id", in="query", description="省、市、区编码数组", required=true, type="string"),
     *     @SWG\Parameter( name="regions", in="query", description="省、市、区名称数组", required=true, type="string"),
     *     @SWG\Parameter( name="address", in="query", type="string", required=true, description="详细地址"),
     *     @SWG\Parameter( name="legal_mobile", in="query", type="string", required=true, description="手机号码"),
     *     @SWG\Parameter( name="email", in="query", type="string", required=false, description="联系邮箱"),
     *     @SWG\Parameter( name="audit_goods", in="query", type="string", required=true, description="是否需要平台审核商品 false:不需要 true:需要"),
     *     @SWG\Parameter( name="license_url", in="query", type="string", required=true, description="营业执照url"),
     *     @SWG\Parameter( name="legal_certid_front_url", in="query", type="string", required=true, description="手持身份证正面url"),
     *     @SWG\Parameter( name="legal_cert_id_back_url", in="query", type="string", required=true, description="手持身份证反面url"),
     *     @SWG\Parameter( name="bank_card_front_url", in="query", type="string", required=true, description="结算银行卡正面url"),
     *     @SWG\Parameter( name="contract_url", in="query", type="string", required=false, description="合同url"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description="返回状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function updateMerchant($id, Request $request)
    {
        $params = $request->all('merchant_type_id', 'regions_id', 'regions', 'address', 'legal_name', 'legal_cert_id', 'legal_mobile', 'email', 'audit_goods', 'license_url', 'legal_certid_front_url', 'legal_cert_id_back_url', 'bank_card_front_url', 'contract_url', 'bank_acct_type', 'card_id_mask', 'bank_name', 'bank_mobile');
        $rules = [
            'merchant_type_id' => ['required|min:1', trans('MerchantBundle.business_scope_required')],
            'regions_id' => ['required', trans('MerchantBundle.region_required')],
            'regions' => ['required', trans('MerchantBundle.region_required')],
            'address' => ['required', trans('MerchantBundle.detailed_address_required')],
            'legal_name' => ['required', trans('MerchantBundle.legal_name_required')],
            'legal_cert_id' => ['required|size:18', trans('MerchantBundle.id_card_18_digits')],
            'legal_mobile' => ['required', trans('MerchantBundle.mobile_number_required_simple')],
            'audit_goods' => ['required|in:true,false', trans('MerchantBundle.audit_goods_required')],
            'license_url' => ['required', trans('MerchantBundle.business_license_required')],
            'legal_certid_front_url' => ['required', trans('MerchantBundle.id_card_front_required')],
            'legal_cert_id_back_url' => ['required', trans('MerchantBundle.id_card_back_required_alt')],
            // 'bank_card_front_url' => ['required', '结算银行卡正面必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $merchantService = new MerchantService();
        $regions = [
            0 => 'province',
            1 => 'city',
            2 => 'area',
        ];
        if (isset($params['regions_id']) && isset($params['regions'])) {
            foreach ($params['regions'] as $k => $value) {
                $params[$regions[$k]] = $value;
            }
        }
        $params['regions_id'] = json_encode($params['regions_id']);
        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['audit_goods'] = $params['audit_goods'] == 'false' ? false : true;
        $merchantService->updateOneBy(['id' => $id], $params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/merchant",
     *     summary="新增商户",
     *     tags={"商户"},
     *     description="新增商户",
     *     operationId="createMerchant",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="settled_type", in="query", type="string", required=true, description="商户入驻类型。enterprise:企业;soletrader:个体户"),
     *     @SWG\Parameter( name="merchant_type_id", in="query", type="string", required=true, description="经营范围ID。如果商户类型下没有经营范围，传商户类型ID。"),
     *     @SWG\Parameter( name="merchant_name", in="query", type="string", required=true, description="商户名称"),
     *     @SWG\Parameter( name="social_credit_code_id", in="query", type="string", required=true, description="统一社会信用代码"),
     *     @SWG\Parameter( name="regions_id", in="query", description="省、市、区编码数组", required=true, type="string"),
     *     @SWG\Parameter( name="regions", in="query", description="省、市、区名称数组", required=true, type="string"),
     *     @SWG\Parameter( name="address", in="query", type="string", required=true, description="详细地址"),
     *     @SWG\Parameter( name="legal_name", in="query", type="string", required=true, description="姓名"),
     *     @SWG\Parameter( name="legal_cert_id", in="query", type="string", required=true, description="身份证号码"),
     *     @SWG\Parameter( name="legal_mobile", in="query", type="string", required=true, description="手机号码"),
     *     @SWG\Parameter( name="email", in="query", type="string", required=false, description="联系邮箱"),
     *     @SWG\Parameter( name="bank_acct_type", in="query", type="string", required=true, description="银行账户类型：1-对公；2-对私"),
     *     @SWG\Parameter( name="card_id_mask", in="query", type="string", required=true, description="结算银行卡号"),
     *     @SWG\Parameter( name="bank_name", in="query", type="string", required=false, description="结算银行卡所属银行名称 bank_acct_type=1时必填"),
     *     @SWG\Parameter( name="bank_mobile", in="query", type="string", required=false, description="银行预留手机号 bank_acct_type=2时必填"),
     *     @SWG\Parameter( name="audit_goods", in="query", type="string", required=true, description="是否需要平台审核商品 false:不需要 true:需要"),
     *     @SWG\Parameter( name="license_url", in="query", type="string", required=true, description="营业执照url"),
     *     @SWG\Parameter( name="legal_certid_front_url", in="query", type="string", required=true, description="手持身份证正面url"),
     *     @SWG\Parameter( name="legal_cert_id_back_url", in="query", type="string", required=true, description="手持身份证反面url"),
     *     @SWG\Parameter( name="bank_card_front_url", in="query", type="string", required=true, description="结算银行卡正面url"),
     *     @SWG\Parameter( name="contract_url", in="query", type="string", required=false, description="合同url"),
     *     @SWG\Parameter( name="mobile", in="query", type="string", required=true, description="生成账号的手机号"),
     *     @SWG\Parameter( name="settled_succ_sendsms", in="query", type="string", required=false, description="入驻成功发送时间  1:立即 2:商家H5确认入驻协议后"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="mobile", type="string", example="true", description="登录手机号"),
     *                  @SWG\Property( property="password", type="string", example="true", description="登录密码"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function createMerchant(Request $request)
    {
        $params = $request->all('merchant_type_id', 'settled_type', 'merchant_name', 'social_credit_code_id', 'regions_id', 'regions', 'address', 'legal_name', 'legal_cert_id', 'legal_mobile', 'email', 'bank_acct_type', 'card_id_mask', 'bank_name', 'bank_mobile', 'audit_goods', 'license_url', 'legal_certid_front_url', 'legal_cert_id_back_url', 'bank_card_front_url', 'contract_url', 'mobile', 'settled_succ_sendsms');
        $rules = [
            'merchant_type_id' => ['required|min:1', trans('MerchantBundle.correct_business_scope_required')],
            'settled_type' => ['required|in:enterprise,soletrader', trans('MerchantBundle.correct_settlement_type_required')],
            'merchant_name' => ['required', trans('MerchantBundle.merchant_name_required')],
            'social_credit_code_id' => ['required|size:18', trans('MerchantBundle.social_credit_code_18_digits')],
            'regions_id' => ['required', trans('MerchantBundle.region_required')],
            'regions' => ['required', trans('MerchantBundle.region_required')],
            'address' => ['required', trans('MerchantBundle.detailed_address_required')],
            'legal_name' => ['required', trans('MerchantBundle.legal_name_required')],
            'legal_cert_id' => ['required|size:18', trans('MerchantBundle.id_card_18_digits')],
            'legal_mobile' => ['required|size:11', trans('MerchantBundle.mobile_number_11_digits')],
            // 'bank_acct_type' => ['required|in:1,2', '银行账户类型必填'],
            // 'card_id_mask' => ['required', '结算银行卡号必填'],
            // 'bank_name' => ['required_if:bank_acct_type,1', '结算银行必填'],
            // 'bank_mobile' => ['required_if:bank_acct_type,2|size:11', '银行预留手机号必须是11位'],
            'audit_goods' => ['required|in:true,false', trans('MerchantBundle.audit_goods_required')],
            'license_url' => ['required', trans('MerchantBundle.business_license_required')],
            'legal_certid_front_url' => ['required', trans('MerchantBundle.id_card_front_required')],
            'legal_cert_id_back_url' => ['required', trans('MerchantBundle.id_card_back_required_alt')],
            // 'bank_card_front_url' => ['required', '结算银行卡正面必填'],
            'mobile' => ['required|size:11', trans('MerchantBundle.account_mobile_11_digits')],
            'settled_succ_sendsms' => ['required|in:1,2', trans('MerchantBundle.sms_send_time_required')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $merchantService = new MerchantService();
        $regions = [
            0 => 'province',
            1 => 'city',
            2 => 'area',
        ];
        if (isset($params['regions_id']) && isset($params['regions'])) {
            foreach ($params['regions'] as $k => $value) {
                $params[$regions[$k]] = $value;
            }
        }
        $params['regions_id'] = json_encode($params['regions_id']);
        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['audit_goods'] = $params['audit_goods'] == 'false' ? false : true;
        $params['source'] = 'admin';
        $params['disabled'] = false;
        $result = $merchantService->createMerchant($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/merchant/disabled/update/{id}",
     *     summary="更新商户的禁用状态",
     *     tags={"商户"},
     *     description="更新商户的禁用状态",
     *     operationId="updateMerchantDisabled",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="disabled", in="query", type="string", required=true, description="是否禁用 true:禁用 false:启用"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description="返回状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function updateMerchantDisabled($id, Request $request)
    {
        $params = $request->all('disabled');
        $rules = [
            'disabled' => ['required|in:true,false', trans('MerchantBundle.disabled_status_required')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $merchantService = new MerchantService();

        $params['disabled'] = $params['disabled'] == 'false' ? false : true;
        $merchantService->updateOneBy(['id' => $id], $params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/merchant/auditgoods/update/{id}",
     *     summary="更新商户的商品审核状态",
     *     tags={"商户"},
     *     description="更新商户的商品审核状态",
     *     operationId="updateMerchantAuditGoods",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="audit_goods", in="query", type="string", required=true, description="是否需要审核商品 true:是 false:否"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description="返回状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function updateMerchantAuditGoods($id, Request $request)
    {
        $params = $request->all('audit_goods');
        $rules = [
            'audit_goods' => ['required|in:true,false', trans('MerchantBundle.goods_audit_status_required')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $merchantService = new MerchantService();

        $params['audit_goods'] = $params['audit_goods'] == 'false' ? false : true;
        $merchantService->updateOneBy(['id' => $id], $params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/merchant/info",
     *     summary="获取登陆商户详情",
     *     tags={"商户"},
     *     description="获取登陆商户详情。",
     *     operationId="getInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example="5", description="ID"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
     *                  @SWG\Property( property="merchant_name", type="string", example="这是一个商户名称1", description="商户名称"),
     *                  @SWG\Property( property="merchant_type_id", type="string", example="3", description="商户类型ID（二级ID）"),
     *                  @SWG\Property( property="settled_type", type="string", example="enterprise", description="入驻类型。enterprise:企业;soletrader:个体户"),
     *                  @SWG\Property( property="social_credit_code_id", type="string", example="", description="统一社会信用代码"),
     *                  @SWG\Property( property="province", type="string", example="上海市", description="省"),
     *                  @SWG\Property( property="city", type="string", example="上海市", description="市"),
     *                  @SWG\Property( property="area", type="string", example="徐汇区", description="区"),
     *                  @SWG\Property( property="regions_id", type="string", example="", description="地区编号集合(DC2Type:json_array)"),
     *                  @SWG\Property( property="address", type="string", example="34322sssaaa是是是", description="详细地址"),
     *                  @SWG\Property( property="legal_name", type="string", example="姓名", description="姓名"),
     *                  @SWG\Property( property="legal_cert_id", type="string", example="", description="身份证号码"),
     *                  @SWG\Property( property="legal_mobile", type="string", example="", description="手机号码"),
     *                  @SWG\Property( property="bank_acct_type", type="string", example="2", description="银行账户类型：1-对公；2-对私"),
     *                  @SWG\Property( property="card_id_mask", type="string", example="", description="结算银行卡号"),
     *                  @SWG\Property( property="bank_name", type="string", example="42sss是是是", description="结算银行卡所属银行名称"),
     *                  @SWG\Property( property="bank_mobile", type="string", example="13000000000", description="银行预留手机号"),
     *                  @SWG\Property( property="license_url", type="string", example="", description="营业执照图片url"),
     *                  @SWG\Property( property="legal_certid_front_url", type="string", example="", description="手持身份证正面url"),
     *                  @SWG\Property( property="legal_cert_id_back_url", type="string", example="", description="手持身份证反面url"),
     *                  @SWG\Property( property="bank_card_front_url", type="string", example="", description="结算银行卡正面url"),
     *                  @SWG\Property( property="contract_url", type="string", example="null", description="合同url"),
     *                  @SWG\Property( property="settled_succ_sendsms", type="string", example="1", description="入驻成功发送时间  1:立即 2:商家H5确认入驻协议后"),
     *                  @SWG\Property( property="audit_goods", type="boolean", example="0", description="是否需要平台审核商品 0:不需要 1:需要"),
     *                  @SWG\Property( property="source", type="string", example="h5", description="来源 admin:平台管理员;h5:h5入驻;"),
     *                  @SWG\Property( property="disabled", type="boolean", example="false", description="是否禁用。0:可用；1:禁用"),
     *                  @SWG\Property( property="created", type="string", example="1639720801", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="1639982413", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function getInfo(Request $request)
    {
        $merchantId = app('auth')->user()->get('merchant_id');
        if (empty($merchantId)) {
            throw new ResourceException(trans('MerchantBundle.merchant_id_cannot_empty'));
        }
        $merchantService = new MerchantService();
        $result = $merchantService->getMerchantDetail($merchantId);
        return $this->response->array($result);
    }
}
