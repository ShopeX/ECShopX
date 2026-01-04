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

namespace MerchantBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;

use MerchantBundle\Services\MerchantSettingService;
use MerchantBundle\Services\MerchantSettlementApplyService;

class Merchant extends Controller
{
    /**
     * @SWG\Post(
     *     path="/wxapp/merchant/login",
     *     summary="商户申请入驻登录/注册",
     *     tags={"商户"},
     *     description="商户申请入驻登录，如果没有账号先注册再登录",
     *     operationId="merchantLogin",
     *     @SWG\Parameter( name="company_id", in="query", description="企业ID", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", required=true, type="string"),
     *     @SWG\Parameter( name="vcode", in="query", description="短信验证码", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="token", type="string", example="1", description="JWT验证token"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */

    /**
     * @SWG\Get(
     *     path="/wxapp/merchant/basesetting",
     *     summary="获取商户基础设置",
     *     tags={"商户"},
     *     description="获取商户基础设置",
     *     operationId="getBaseSetting",
     *     @SWG\Parameter( name="company_id", in="query", description="企业ID", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="settled_type", type="array", description="允许加盟商入驻类型 enterprise:企业 soletrader:个体户",
     *                      @SWG\Items( type="string", example="", description=""),
     *                  ),
     *                  @SWG\Property( property="content", type="string", example="", description="入驻协议内容"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function getBaseSetting(Request $request)
    {
        $authInfo = $request->get('auth');
        $settingService = new MerchantSettingService();
        $result = $settingService->getBaseSetting($authInfo['company_id']);
        $return = [
            'display_on_pc' => $result['display_on_pc'],
            'settled_type' => $result['settled_type'],
            'content' => $result['content'],
        ];
        return $this->response->array($return);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/merchant/type/list",
     *     summary="获取商户类型列表",
     *     tags={"商户"},
     *     description="获取可见商户类型列表,可以根据名称筛选。查询商户类型数据时，只返回有可见经营范围的数据。",
     *     operationId="getVisibleTypeList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", type="integer", required=true),
     *     @SWG\Parameter( name="page_size", in="query", description="分页条数", type="integer", required=true),
     *     @SWG\Parameter( name="parent_id", in="query", description="父级ID,顶级传0", required=false, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="商户类型名称", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="3", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
     *                          @SWG\Property( property="name", type="string", example="五金交电", description="类型名称"),
     *                          @SWG\Property( property="parent_id", type="string", example="2", description="父分类id,顶级为0"),
     *                          @SWG\Property( property="path", type="string", example="2,3", description="路径"),
     *                          @SWG\Property( property="sort", type="string", example="1", description="排序，数字越小越靠前"),
     *                          @SWG\Property( property="level", type="string", example="2", description="等级"),
     *                          @SWG\Property( property="created", type="string", example="1639465518", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1639465518", description="修改时间"),
     *                          @SWG\Property( property="is_show", type="boolean", example="1", description="是否展示,1展示 0不展示"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function getVisibleTypeList(Request $request)
    {
        $params = $request->all('page', 'page_size');
        $rules = [
            'page' => ['required|integer|min:1', trans('MerchantBundle.current_page_positive_integer')],
            'page_size' => ['required|integer|min:1|max:50', trans('MerchantBundle.page_size_1_to_50_integer')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $authInfo = $request->get('auth');
        $settingService = new MerchantSettingService();
        $filter = [
            'company_id' => $authInfo['company_id'],
            'is_show' => true,
            'parent_id' => $request->input('parent_id', 0),
        ];
        if ($request->input('name', '')) {
            $filter['name|contains'] = $request->input('name');
        }
        $result = $settingService->getVisibleTypeList($filter, '*', $params['page'], $params['page_size'], ['sort' => 'ASC', 'created' => 'ASC']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/merchant/settlementapply/step",
     *     summary="获取商户入驻当前步骤",
     *     tags={"商户"},
     *     description="获取商户入驻当前步骤",
     *     operationId="getSettlementApplyStep",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="step", type="string", example="1", description="当前步骤 1:已注册;2:已填写入驻信息;3:已填写商户信息;4:已填写证照信息;"),
     *
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function getSettlementApplyStep(Request $request)
    {
        $authInfo = $request->get('auth');
        $settlementApplyService = new MerchantSettlementApplyService();
        $result = $settlementApplyService->getSettlementApplyStep($authInfo['company_id'], $authInfo['account_id']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/merchant/settlementapply/{step}",
     *     summary="保存商户入驻信息",
     *     tags={"商户"},
     *     description="保存商户入驻信息",
     *     operationId="saveSettlementApply",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="step", in="path", description="入驻信息填写步骤 1:入驻信息;2:商户信息;3:证照信息", required=true, type="string"),
     *     @SWG\Parameter( name="merchant_type_id", in="query", description="经营范围ID。如果商户类型下没有经营范围，传商户类型ID。", required=true, type="string"),
     *     @SWG\Parameter( name="settled_type", in="query", description="入驻类型。enterprise:企业;soletrader:个体户", required=true, type="string"),
     *     @SWG\Parameter( name="merchant_name", in="query", description="商户名称", required=true, type="string"),
     *     @SWG\Parameter( name="social_credit_code_id", in="query", description="统一社会信用代码", required=true, type="string"),
     *     @SWG\Parameter( name="regions_id", in="query", description="省、市、区编码数组", required=true, type="string"),
     *     @SWG\Parameter( name="regions", in="query", description="省、市、区名称数组", required=true, type="string"),
     *     @SWG\Parameter( name="address", in="query", description="详细地址", required=true, type="string"),
     *     @SWG\Parameter( name="legal_name", in="query", description="姓名", required=true, type="string"),
     *     @SWG\Parameter( name="legal_cert_id", in="query", description="身份证号码", required=true, type="string"),
     *     @SWG\Parameter( name="legal_mobile", in="query", description="手机号码", required=true, type="string"),
     *     @SWG\Parameter( name="bank_acct_type", in="query", description="银行账户类型：1-对公；2-对私", required=true, type="string"),
     *     @SWG\Parameter( name="card_id_mask", in="query", description="结算银行卡号", required=true, type="string"),
     *     @SWG\Parameter( name="bank_name", in="query", description="结算银行。bank_acct_type=1时必填。", required=false, type="string"),
     *     @SWG\Parameter( name="bank_mobile", in="query", description="绑定手机号。bank_acct_type=2时必填。", required=false, type="string"),
     *     @SWG\Parameter( name="license_url", in="query", description="营业执照图片url", required=true, type="string"),
     *     @SWG\Parameter( name="legal_certid_front_url", in="query", description="手持身份证正面url", required=true, type="string"),
     *     @SWG\Parameter( name="legal_cert_id_back_url", in="query", description="手持身份证反面url", required=true, type="string"),
     *     @SWG\Parameter( name="bank_card_front_url", in="query", description="结算银行卡正面url", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function saveSettlementApply($step, Request $request)
    {
        switch ($step) {
            case '1':
                $params = $request->all('merchant_type_id', 'settled_type');
                $rules = [
                    'merchant_type_id' => ['required', trans('MerchantBundle.business_scope_required')],
                    'settled_type' => ['required|in:enterprise,soletrader', trans('MerchantBundle.settlement_type_required')],
                ];
                break;
            case '2':
                $params = $request->all('merchant_name', 'social_credit_code_id', 'regions_id', 'regions', 'address', 'legal_name', 'legal_cert_id', 'legal_mobile', 'bank_acct_type', 'card_id_mask', 'bank_name', 'bank_mobile');
                $rules = [
                    'merchant_name' => ['required', trans('MerchantBundle.merchant_name_required')],
                    'social_credit_code_id' => ['required|size:18', trans('MerchantBundle.social_credit_code_18_digits')],
                    'regions_id' => ['required', trans('MerchantBundle.region_required')],
                    'regions' => ['required', trans('MerchantBundle.region_required')],
                    'address' => ['required', trans('MerchantBundle.detailed_address_required')],
                    'legal_name' => ['required', trans('MerchantBundle.legal_name_required')],
                    'legal_cert_id' => ['required|size:18', trans('MerchantBundle.id_card_18_digits')],
                    'legal_mobile' => ['required', trans('MerchantBundle.mobile_number_required')],
                    // 'bank_acct_type' => ['required|in:1,2', '银行账户类型必填'],
                    // 'card_id_mask' => ['required', '结算银行卡号必填'],
                    // 'bank_name' => ['required_if:bank_acct_type,1', '结算银行必填'],
                    // 'bank_mobile' => ['required_if:bank_acct_type,2', '绑定手机号必须是11位'],
                ];
                break;
            case '3':
                $params = $request->all('license_url', 'legal_certid_front_url', 'legal_cert_id_back_url', 'bank_card_front_url');
                $rules = [
                    'license_url' => ['required', trans('MerchantBundle.business_license_required')],
                    'legal_certid_front_url' => ['required', trans('MerchantBundle.id_card_front_required')],
                    'legal_cert_id_back_url' => ['required', trans('MerchantBundle.id_card_back_holding_required')],
                    // 'bank_card_front_url' => ['required', '结算银行卡正面必填'],
                ];
                break;
            default:
                throw new ResourceException(trans('MerchantBundle.settlement_info_step_error'));
                break;
        }

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'];
        $settlementApplyService = new MerchantSettlementApplyService();
        if ($step == '2') {
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
        }
        $result = $settlementApplyService->saveSettlementApply($authInfo['account_id'], $step, $params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/merchant/settlementapply/detail",
     *     summary="获取商户入驻申请详情",
     *     tags={"商户"},
     *     description="获取商户入驻申请详情",
     *     operationId="getSettlementApplyDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="id", type="string", example="4", description="ID"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *               @SWG\Property(property="mobile", type="string", example="15901872216", description="手机号"),
     *               @SWG\Property(property="is_agree_agreement", type="boolean", example="1", description="是否同意入驻协议"),
     *               @SWG\Property(property="merchant_type_parent_id", type="string", example="2", description="商户类型ID"),
     *               @SWG\Property(property="merchant_type_parent_name", type="string", example="2", description="商户类型名称"),
     *               @SWG\Property(property="merchant_type_id", type="string", example="3", description="经营范围ID"),
     *               @SWG\Property(property="merchant_type_name", type="string", example="3", description="经营范围名称"),
     *               @SWG\Property(property="settled_type", type="string", example="soletrader", description="入驻类型。enterprise:企业;soletrader:个体户"),
     *               @SWG\Property(property="merchant_name", type="string", example="不重要的名称", description="商户名称"),
     *               @SWG\Property(property="social_credit_code_id", type="string", example="111111111111111111", description="统一社会信用代码"),
     *               @SWG\Property(property="province", type="string", example="上海", description="省"),
     *               @SWG\Property(property="city", type="string", example="上海市", description="市"),
     *               @SWG\Property(property="area", type="string", example="徐汇区", description="区"),
     *               @SWG\Property(property="address", type="string", example="详细地址详细地址详细地址", description="详细地址"),
     *               @SWG\Property(property="legal_name", type="string", example="张三", description="姓名"),
     *               @SWG\Property(property="legal_cert_id", type="string", example="341231231231331", description="身份证号码"),
     *               @SWG\Property(property="legal_mobile", type="string", example="13100000000", description="手机号码"),
     *               @SWG\Property(property="bank_acct_type", type="string", example="2", description="银行账户类型：1-对公；2-对私"),
     *               @SWG\Property(property="card_id_mask", type="string", example="1231", description="结算银行卡号"),
     *               @SWG\Property(property="bank_name", type="string", example="结算银行", description="银行名称"),
     *               @SWG\Property(property="bank_mobile", type="string", example="银行预留手机号", description="银行名称"),
     *               @SWG\Property(property="license_url", type="string", example="https://bbctest.aixue7.com/image/1/2021/10/21/1f9151ba8da08f53cab7d813bd415e954lRihcM2rxefG5t8AJzn1gW6gYR7s4M0", description="营业执照图片url"),
     *               @SWG\Property(property="legal_certid_front_url", type="string", example="https://bbctest.aixue7.com/image/1/2021/10/21/1f9151ba8da08f53cab7d813bd415e954lRihcM2rxefG5t8AJzn1gW6gYR7s4M0", description="手持身份证正面url"),
     *               @SWG\Property(property="legal_cert_id_back_url", type="string", example="https://bbctest.aixue7.com/image/1/2021/10/21/1f9151ba8da08f53cab7d813bd415e954lRihcM2rxefG5t8AJzn1gW6gYR7s4M0", description="手持身份证反面url"),
     *               @SWG\Property(property="bank_card_front_url", type="string", example="https://bbctest.aixue7.com/image/1/2021/10/21/1f9151ba8da08f53cab7d813bd415e954lRihcM2rxefG5t8AJzn1gW6gYR7s4M0", description="结算银行卡正面url"),
     *               @SWG\Property(property="audit_status", type="string", example="1", description="审核状态：1:审核中 2:审核成功 3:审核驳回"),
     *               @SWG\Property(property="audit_memo", type="string", example="", description="审核备注"),
     *               @SWG\Property(property="source", type="string", example="h5", description="来源 admin:平台管理员;h5:h5入驻;"),
     *               @SWG\Property(property="created", type="integer", example="1639638131", description="创建时间"),
     *               @SWG\Property(property="updated", type="integer", example="1639638144", description="更新时间"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function getSettlementApplyDetail(Request $request)
    {
        $authInfo = $request->get('auth');
        $settlementApplyService = new MerchantSettlementApplyService();
        $result = $settlementApplyService->getSettlementApplyDetail($authInfo['account_id']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/merchant/settlementapply/auditstatus",
     *     summary="获取商户入驻信息审核结果",
     *     tags={"商户"},
     *     description="获取商户入驻信息审核结果",
     *     operationId="getSettlementApplyAuditstatus",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="audit_status", type="string", example="1", description="审核状态：1:审核中 2:审核成功 3:审核驳回"),
     *               @SWG\Property(property="audit_memo", type="string", example="1", description="审批意见"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function getSettlementApplyAuditstatus(Request $request)
    {
        $authInfo = $request->get('auth');
        $settlementApplyService = new MerchantSettlementApplyService();
        $result = $settlementApplyService->getSettlementApplyDetail($authInfo['account_id']);

        $loginInfo = $settlementApplyService->getLoginInfo($authInfo['company_id'], $authInfo['account_id']);

        $return = [
            'audit_status' => $result['audit_status'],
            'audit_memo' => $result['audit_memo'] ?? '',
        ];
        $return = array_merge($return, $loginInfo);
        return $this->response->array($return);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/merchant/password/reset",
     *     summary="重置商户登录密码",
     *     tags={"商户"},
     *     description="重置商户登录密码",
     *     operationId="resetMerchantPassword",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="mobile", type="string", example="1", description="登录手机号"),
     *               @SWG\Property(property="password", type="string", example="1", description="登录密码"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function resetMerchantPassword(Request $request)
    {
        $authInfo = $request->get('auth');
        $settlementApplyService = new MerchantSettlementApplyService();

        $result = $settlementApplyService->resetPassword($authInfo['company_id'], $authInfo['account_id']);

        return $this->response->array($result);
    }
}
