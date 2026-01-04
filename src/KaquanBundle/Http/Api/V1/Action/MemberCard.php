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

namespace KaquanBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use KaquanBundle\Services\MemberCardService;

use Dingo\Api\Exception\ResourceException;

class MemberCard extends BaseController
{
    public $limit = 50;

    /**
     * @SWG\Put(
     *     path="/membercard",
     *     summary="更新会员卡设置",
     *     tags={"卡券"},
     *     description="更新会员卡设置",
     *     operationId="setMemberCard",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="brand_name",
     *         in="query",
     *         description="商户名称",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="logo_url",
     *         in="query",
     *         description="商户logo",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="title",
     *         in="query",
     *         description="会员卡名称",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="color",
     *         in="query",
     *         description="会员卡颜色",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="background_pic_url",
     *         in="query",
     *         description="会员卡背景图",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="code_type",
     *         in="query",
     *         description="会员卡code类型(CODE_TYPE_TEXT CODE_TYPE_BARCODE CODE_TYPE_QRCODE CODE_TYPE_ONLY_QRCODE CODE_TYPE_ONLY_BARCODE CODE_TYPE_NONE)",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              ref="#/definitions/MemberCard"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function setMemberCard(Request $request)
    {
        $params = $request->input();
        $validator = app('validator')->make(
            $params,
            [
                'brand_name' => 'required',
                'logo_url' => 'required',
                'title' => 'required',
                'background_pic_url' => '',
                'color' => 'required',
                'code_type' => 'required',
            ],
            [
                'brand_name' => trans('KaquanBundle.brand_name_required'),
                'logo_url' => trans('KaquanBundle.logo_url_required'),
                'title' => trans('KaquanBundle.member_card_title_required'),
                'background_pic_url' => '',
                'color' => trans('KaquanBundle.member_card_color_required'),
                'code_type' => trans('KaquanBundle.code_type_required'),
            ]
        );
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException($errmsg);
        }

        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;
        $memberCardService = new MemberCardService();
        $result = $memberCardService->setMemberCard($companyId, $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/membercard",
     *     summary="获取会员卡信息",
     *     tags={"卡券"},
     *     description="获取会员卡信息",
     *     operationId="getMemberCard",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              ref="#/definitions/MemberCard"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getMemberCard(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $memberCardService = new MemberCardService();
        $result = $memberCardService->getMemberCard($companyId);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/membercard/grade",
     *     summary="更新会员卡等级",
     *     tags={"卡券"},
     *     description="更新会员卡等级",
     *     operationId="updateMembercardGrade",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="card_id",
     *         in="query",
     *         description="卡券ID",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="grade_info",
     *         in="query",
     *         description="等级名称",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function updateMembercardGrade(Request $request)
    {
        $postdata = $request->all('grade_info');
        $companyId = app('auth')->user()->get('company_id');
        if (is_string($postdata['grade_info'])) {
            $postdata['grade_info'] = json_decode($postdata['grade_info'], true);
        }
        $gradeInfo = $postdata['grade_info'];
        $memberCardService = new MemberCardService();
        $result = $memberCardService->updateGrade($companyId, $gradeInfo);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/membercard/grades",
     *     summary="获取等级列表",
     *     tags={"卡券"},
     *     description="获取等级列表",
     *     operationId="getGradeList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  ref="#/definitions/MemberGrade"
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getGradeList()
    {
        $companyId = app('auth')->user()->get('company_id');
        $kaquanService = new MemberCardService();
        $isMemberCount = true;
        $result = $kaquanService->getGradeListByCompanyId($companyId, $isMemberCount);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/membercard/defaultGrade",
     *     summary="获取会员卡默认等级",
     *     tags={"卡券"},
     *     description="获取会员卡默认等级",
     *     operationId="getDefaultGrade",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              ref="#/definitions/MemberGrade"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getDefaultGrade()
    {
        $companyId = app('auth')->user()->get('company_id');
        $kaquanService = new MemberCardService();
        $result = $kaquanService->getDefaultGradeByCompanyId($companyId);

        return $this->response->array($result);
    }
}
