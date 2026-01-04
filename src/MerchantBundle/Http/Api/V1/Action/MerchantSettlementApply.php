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

use MerchantBundle\Services\MerchantSettlementApplyService;

class MerchantSettlementApply extends Controller
{
    /**
     * @SWG\Get(
     *     path="/merchant/settlement/apply/list",
     *     summary="获取商户入驻申请列表",
     *     tags={"商户"},
     *     description="获取商户入驻申请列表",
     *     operationId="getList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="page", in="query", description="当前页数", required=true, type="string"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="string"),
     *     @SWG\Parameter( name="audit_status", in="query", description="审核状态：1:审核中 2:审核成功 3:审核驳回", required=false, type="string"),
     *     @SWG\Parameter( name="merchant_name", in="query", description="商户名称", required=false, type="string"),
     *     @SWG\Parameter( name="province", in="query", description="省份名称", required=false, type="string"),
     *     @SWG\Parameter( name="city", in="query", description="城市名称", required=false, type="string"),
     *     @SWG\Parameter( name="area", in="query", description="区县名称", required=false, type="string"),
     *     @SWG\Parameter( name="settled_type", in="query", description="入驻类型。enterprise:企业;soletrader:个体户", required=false, type="string"),
     *     @SWG\Parameter( name="time_start", in="query", description="入驻时间数组。时间格式", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="count", type="string", example="1", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="4", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
     *                          @SWG\Property( property="mobile", type="string", example="13000000000", description="注册手机号"),
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
     *                          @SWG\Property( property="legal_cert_id", type="string", example="", description="身份证号码"),
     *                          @SWG\Property( property="legal_mobile", type="string", example="13100000000", description="手机号码"),
     *                          @SWG\Property( property="bank_acct_type", type="string", example="2", description="银行账户类型：1-对公；2-对私"),
     *                          @SWG\Property( property="card_id_mask", type="string", example="", description="结算银行卡号"),
     *                          @SWG\Property( property="bank_name", type="string", example="", description="结算银行卡所属银行名称"),
     *                          @SWG\Property( property="bank_mobile", type="string", example="13000000000", description="银行预留手机号"),
     *                          @SWG\Property( property="license_url", type="string", example="", description="营业执照图片url"),
     *                          @SWG\Property( property="legal_certid_front_url", type="string", example="", description="手持身份证正面url"),
     *                          @SWG\Property( property="legal_cert_id_back_url", type="string", example="", description="手持身份证反面url"),
     *                          @SWG\Property( property="bank_card_front_url", type="string", example="", description="结算银行卡正面url"),
     *                          @SWG\Property( property="audit_status", type="string", example="1", description="审核状态：1:审核中 2:审核成功 3:审核驳回"),
     *                          @SWG\Property( property="audit_memo", type="string", example="1", description="审核备注"),
     *                          @SWG\Property(property="audit_goods", type="string", example="1", description="是否需要平台审核商品 0:不需要 1:需要"),
     *                          @SWG\Property( property="is_agree_agreement", type="string", example="1", description="是否同意入驻协议"),
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
        $params = $request->all('page', 'page_size', 'audit_status', 'merchant_name', 'province', 'city', 'area', 'settled_type', 'time_start');
        $rules = [
            'page' => ['required|integer|min:1', trans('MerchantBundle.current_page_positive_integer')],
            'page_size' => ['required|integer|min:1|max:50', trans('MerchantBundle.page_size_1_to_50_integer')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $filter = [
            'company_id' => app('auth')->user()->get('company_id'),
            // 'source' => 'h5',
            // 'disabled' => 0,
        ];
        if ($params['audit_status'] ?? '') {
            $filter['audit_status'] = $params['audit_status'];
        }
        if ($params['merchant_name'] ?? '') {
            $filter['merchant_name|contains'] = $params['merchant_name'];
        }
        if ($params['province'] ?? '') {
            $filter['province'] = $params['province'];
        }
        if ($params['city'] ?? '') {
            $filter['city'] = $params['city'];
        }
        if ($params['area'] ?? '') {
            $filter['area'] = $params['area'];
        }
        if ($params['settled_type'] ?? '') {
            $filter['settled_type'] = $params['settled_type'];
        }
        if ($params['time_start'] ?? []) {
            $filter['created|gte'] = strtotime($params['time_start'][0]);
            $filter['created|lte'] = strtotime($params['time_start'][1]);
        }

        $settlementApplyService = new MerchantSettlementApplyService();
        $result = $settlementApplyService->lists($filter, '*', $params['page'], $params['page_size'], ['created' => 'DESC']);
        return $this->response->array(['list' => $result['list'], 'count' => $result['total_count']]);
    }


    /**
     * @SWG\Get(
     *     path="/merchant/settlement/apply/{id}",
     *     summary="获取商户入驻申请详情",
     *     tags={"商户"},
     *     description="获取商户入驻申请详情",
     *     operationId="getDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="path", description="ID", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="id", type="string", example="4", description="ID"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *               @SWG\Property(property="mobile", type="string", example="13000000000", description="手机号"),
     *               @SWG\Property(property="is_agree_agreement", type="boolean", example="1", description="是否同意入驻协议"),
     *               @SWG\Property(property="merchant_type_parent_id", type="string", example="2", description="商户类型ID"),
     *               @SWG\Property(property="merchant_type_parent_name", type="string", example="2", description="商户类型名称"),
     *               @SWG\Property(property="merchant_type_id", type="string", example="3", description="经营范围ID"),
     *               @SWG\Property(property="merchant_type_name", type="string", example="3", description="经营范围名称"),
     *               @SWG\Property(property="settled_type", type="string", example="soletrader", description="入驻类型。enterprise:企业;soletrader:个体户"),
     *               @SWG\Property(property="merchant_name", type="string", example="不重要的名称", description="商户名称"),
     *               @SWG\Property(property="social_credit_code_id", type="string", example="111111111111111111", description="统一社会信用代码"),
     *               @SWG\Property( property="province", type="string", example="上海市", description="省"),
     *               @SWG\Property( property="city", type="string", example="上海市", description="市"),
     *               @SWG\Property( property="area", type="string", example="徐汇区", description="区"),
     *               @SWG\Property( property="regions_id", type="string", example="", description="地区编号集合(DC2Type:json_array)"),
     *               @SWG\Property( property="address", type="string", example="详细地址", description="详细地址"),
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
     *               @SWG\Property(property="audit_memo", type="string", example="1", description="审核备注"),
     *               @SWG\Property(property="audit_goods", type="string", example="1", description="是否需要平台审核商品 0:不需要 1:需要"),
     *               @SWG\Property(property="source", type="string", example="h5", description="来源 admin:平台管理员;h5:h5入驻;"),
     *               @SWG\Property(property="created", type="integer", example="1639638131", description="创建时间"),
     *               @SWG\Property(property="updated", type="integer", example="1639638144", description="更新时间"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function getDetail($id, Request $request)
    {
        $settlementApplyService = new MerchantSettlementApplyService();
        $result = $settlementApplyService->getSettlementApplyDetail($id);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $result['datapass_block'] = $datapassBlock;
        $result = $settlementApplyService->settlementApplyDataMasking($result, $datapassBlock);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/merchant/settlement/apply/audit",
     *     summary="审核商户入驻申请",
     *     tags={"商户"},
     *     description="审核商户入驻申请",
     *     operationId="auditData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="ID", required=true, type="string"),
     *     @SWG\Parameter( name="audit_status", in="query", description="审核状态：2:审核成功 3:审核驳回", required=true, type="string"),
     *     @SWG\Parameter( name="audit_memo", in="query", description="审核备注", required=false, type="string"),
     *     @SWG\Parameter( name="audit_goods", in="query", description="是否需要平台审核商品 0:不需要 1:需要", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MerchantErrorRespones") ) )
     * )
     */
    public function auditData(Request $request)
    {
        $params = $request->all('id', 'audit_status', 'audit_memo', 'audit_goods');
        $rules = [
            'id' => ['required', trans('MerchantBundle.id_required')],
            'audit_status' => ['required|in:2,3', trans('MerchantBundle.audit_result_required')],
            'audit_goods' => ['required|in:0,1', trans('MerchantBundle.platform_audit_goods_required')],
            'audit_memo' => ['max:300', trans('MerchantBundle.audit_opinion_max_300_chars')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['company_id'] = app('auth')->user()->get('company_id');
        $settlementApplyService = new MerchantSettlementApplyService();
        $settlementApplyService->settlementApplyAudit($params);
        return $this->response->array(['status' => true]);
    }
}
