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

namespace AdaPayBundle\Http\FrontApi\V1\Action;

use AdaPayBundle\Services\AdapayPromoterService;
use AdaPayBundle\Services\MemberService;
use App\Http\Controllers\Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;

/**
 * adapayC端分销员相关控制器
 */
class AdapayPromoter extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/adapay/popularize/cert",
     *     summary="获取分销员认证信息",
     *     tags={"Adapay"},
     *     description="获取分销员认证信息",
     *     operationId="getCertInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="is_data_masking",
     *         in="query",
     *         description="是否脱敏 1:是  0:否",
     *         type="string",
     *         required=false,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="member_id", type="string", example="0", description="认证会员ID 0为未创建过认证信息，1为创建过认证信息"),
     *                  @SWG\Property( property="tel_no", type="string", example="", description="银行预留手机号"),
     *                  @SWG\Property( property="card_id", type="string", example="", description="银行账号"),
     *                  @SWG\Property( property="cert_id", type="string", example="", description="开户证件号码"),
     *                  @SWG\Property( property="card_name", type="string", example="", description="开户人姓名"),
     *                  @SWG\Property( property="cert_status", type="object",
     *                      @SWG\Property( property="audit_state", type="string", example="", description="审核状态 A 待审核；B 审核失败；C 开户失败； D 开户成功但未创建结算账户； E 开户和创建结算账户成功；"),
     *                      @SWG\Property( property="audit_value", type="string", example="", description="审核状态语义化值 AUDIT_WAIT 待审核；AUDIT_FAIL 审核失败；AUDIT_MEMBER_FAIL 开户失败； AUDIT_ACCOUNT_FAIL 开户成功但未创建结算账户； AUDIT_SUCCESS 开户和创建结算账户成功；"),
     *                      @SWG\Property( property="audit_desc", type="string", example="", description="审核描述"),
     *                      @SWG\Property( property="error_info", type="string", example="", description="错误信息"),
     *                      @SWG\Property( property="create_time", type="string", example="", description="创建时间"),
     *                      @SWG\Property( property="update_time", type="string", example="", description="更新时间"),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构" )
     * )
     */
    public function getCertInfo(Request $request)
    {
        $authInfo = app('auth')->user()->get();

        $isDataMasking = $request->input('is_data_masking', 1);
        $userId = $authInfo['user_id'];
        $companyId = $authInfo['company_id'];

        $result = (new AdapayPromoterService())->getCertInfo($companyId, $userId, $isDataMasking);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/adapay/popularize/create_cert",
     *     summary="新建分销员认证信息",
     *     tags={"Adapay"},
     *     description="新建分销员认证信息",
     *     operationId="createCert",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="tel_no",
     *         in="query",
     *         description="手机号码",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="card_id",
     *         in="query",
     *         description="银行账号",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="cert_id",
     *         in="query",
     *         description="身份证号",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="card_name",
     *         in="query",
     *         description="开户人姓名",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="bool", example="", description="操作结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构" )
     * )
     */
    public function createCert(Request $request)
    {
        $inputData = $request->all();

        $auth = app('auth')->user()->get();
        $rules = [
            'tel_no' => ['required|size:11', '手机号码必须是11位'],
            'card_id' => ['required', '银行账号必填'],
            'cert_id' => ['required|size:18', '身份证号必须是18位'],
            'card_name' => ['required|max:15', '开户人姓名必填且不能超过15个字符'],
        ];
        $error = validator_params($inputData, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $memberService = new MemberService($auth['company_id']);
        $inputData = $memberService->checkParams($inputData, true);

        (new MemberService())->createPromoter($auth['company_id'], $auth['user_id'], $inputData);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/adapay/popularize/update_cert",
     *     summary="更新分销员认证信息",
     *     tags={"Adapay"},
     *     description="更新分销员认证信息",
     *     operationId="updateCert",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="member_id",
     *         in="query",
     *         description="member_id",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="tel_no",
     *         in="query",
     *         description="手机号码",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="card_id",
     *         in="query",
     *         description="银行账号",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="cert_id",
     *         in="query",
     *         description="身份证号",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="card_name",
     *         in="query",
     *         description="开户人姓名",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="bool", example="", description="操作结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构" )
     * )
     */
    public function updateCert(Request $request)
    {
        $inputData = $request->all();

        $auth = app('auth')->user()->get();
        $rules = [
            'member_id' => ['required|min:1', '认证ID必传'],
            'tel_no' => ['required|size:11', '手机号码必须是11位'],
            'card_id' => ['required', '银行账号必填'],
            'cert_id' => ['required|size:18', '身份证号必须是18位'],
            'card_name' => ['required|max:15', '开户人姓名必填且不能超过15个字符'],
        ];
        $error = validator_params($inputData, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $memberService = new MemberService($auth['company_id']);
        $inputData = $memberService->checkParams($inputData, true);

        (new MemberService())->updatePromoter($auth['company_id'], $inputData);

        return $this->response->array(['status' => true]);
    }
}
