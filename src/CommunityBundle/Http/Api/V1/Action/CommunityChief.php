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

namespace CommunityBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use CommunityBundle\Services\CommunityChiefService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use CommunityBundle\Services\CommunityChiefApplyInfoService;
use WechatBundle\Services\WeappService;
use WechatBundle\Services\OpenPlatform;
use CompanysBundle\Ego\CompanysActivationEgo;

class CommunityChief extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/chief/list",
     *     summary="团长列表",
     *     tags={"社区团管理端"},
     *     description="团长列表",
     *     operationId="getChiefList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="用户user_id", required=false, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="手机号", required=false, type="integer"),
     *     @SWG\Parameter( name="approve_status", in="query", description="审批状态", required=false, type="integer"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", required=false, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="姓名", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getChiefList(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');

        $company = (new CompanysActivationEgo())->check($companyId);
        if ($company['product_model'] == 'platform') {
            $distributorId = 0; // 只看总部的
        } else {
            $distributorId = null; // 查看所有的团长
        }
        if ($operatorType == 'distributor') { //店铺端
            $distributorId = $request->get('distributor_id');
        }

        $pageSize = $request->input('pageSize', 10);
        $page = $request->input('page', 1);

        $filter['company_id'] = $companyId;

        if ($name = $request->input('name')) {
            $filter['chief_name'] = $name;
        }

        if ($mobile = $request->input('mobile')) {
            $filter['chief_mobile'] = $mobile;
        }

        $communityChiefService = new CommunityChiefService();
        $result = $communityChiefService->getListByDistributorId($distributorId, $filter, $page, $pageSize, ['created_at' => 'desc']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/chief/{chief_id}",
     *     summary="团长详情",
     *     tags={"社区团管理端"},
     *     description="团长详情",
     *     operationId="getChiefInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", required=false, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getChiefInfo($chief_id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');

        $distributorId = $request->input('distributor_id');
        if ($operatorType == 'distributor') { //店铺端
            $distributorId = $request->get('distributor_id');
        }
        if (is_null($distributorId)) {
            throw new ResourceException('所属店铺ID必填');
        }

        $filter['company_id'] = $companyId;
        $filter['chief_id'] = $chief_id;

        $communityChiefService = new CommunityChiefService();
        $result = $communityChiefService->getListByDistributorId($distributorId, $filter, 1, 1);
        return $this->response->array($result['list'] ? $result['list'][0] : []);
    }

    /**
     * @SWG\Get(
     *     path="/chief/apply/wxaCode",
     *     summary="小程序码base64图片",
     *     tags={"社区团管理端"},
     *     description="小程序码base64图片",
     *     operationId="getWxaCode",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="query", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Parameter( name="path", in="query", description="小程序路径", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="base64Image", type="string", example="data:image/jpg;base64..."),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getWxaCode(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $distributorId = 0;
        if ($operatorType == 'distributor') { //店铺端
            $distributorId = $request->get('distributor_id');
        }

        $templateName = 'yykweishop';
        $weappService = new WeappService();
        $wxaAppid = $weappService->getWxappidByTemplateName($companyId, $templateName);
        if (!$wxaAppid) {
            throw new ResourceException('没有绑定小程序');
        }

        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($wxaAppid);

        $scene = 'did=' . $distributorId;
        $page = $request->input('path', 'pages/index');
        if (substr($page, 0, 1) == '/') {
            $page = substr($page, 1);
        }
        $data['page'] = $page;

        try {
            $response = $app->app_code->getUnlimit($scene, $data);
            if (is_array($response) && $response['errcode'] > 0) {
                throw new \Exception($response['errmsg']);
            }
        } catch (\Exception $e) {
            throw new ResourceException('小程序还从未通过审核，无法生成小程序码，请查看体验二维码');
        }
        $base64 = 'data:image/jpg;base64,' . base64_encode($response);
        return $this->response->array(['base64Image' => $base64]);
    }

    /**
     * @SWG\Get(
     *     path="/chief/apply/list",
     *     summary="团长申请列表",
     *     tags={"社区团管理端"},
     *     description="团长申请列表",
     *     operationId="getApplyList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="用户user_id", required=false, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="手机号", required=false, type="integer"),
     *     @SWG\Parameter( name="approve_status", in="query", description="审批状态", required=false, type="integer"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", required=false, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="姓名", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getApplyList(Request $request) {
        $companyId = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $distributorId = 0;
        if ($operatorType == 'distributor') { //店铺端
            $distributorId = $request->get('distributor_id');
        }
        $pageSize = $request->input('pageSize', 10);
        $page = $request->input('page', 1);

        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
        ];

        if (($approveStatus = $request->input('approve_status')) === '0') {
            $filter['approve_status'] = $approveStatus;
        }

        if ($name = $request->input('name')) {
            $filter['chief_name'] = $name;
        }

        if ($mobile = $request->input('mobile')) {
            $filter['chief_mobile'] = $mobile;
        }

        $chiefApplyInfoService = new CommunityChiefApplyInfoService();
        $cols = 'apply_id,user_id,chief_name,chief_mobile,approve_status,created_at';
        $result = $chiefApplyInfoService->lists($filter, $cols, $page, $pageSize, ['created_at' => 'DESC']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/chief/apply/info/{apply_id}",
     *     summary="团长申请详情",
     *     tags={"社区团管理端"},
     *     description="团长申请详情",
     *     operationId="getApplyInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="apply_id", in="path", description="申请id", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getApplyInfo($apply_id, Request $request) {
        $companyId = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $distributorId = 0;
        if ($operatorType == 'distributor') { //店铺端
            $distributorId = $request->get('distributor_id');
        }

        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'apply_id' => $apply_id,
        ];
        $chiefApplyInfoService = new CommunityChiefApplyInfoService();
        $result = $chiefApplyInfoService->getInfo($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/chief/approve/{apply_id}",
     *     summary="团长申请审批",
     *     tags={"社区团管理端"},
     *     description="团长申请审批",
     *     operationId="approve",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="apply_id", in="path", description="申请id", required=true, type="integer"),
     *     @SWG\Parameter( name="approve_status", in="query", description="审批状态", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function approve($apply_id, Request $request) {
        $companyId = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $distributorId = 0;
        if ($operatorType == 'distributor') { //店铺端
            $distributorId = $request->get('distributor_id');
        }

        $params = $request->all();
        $rule = [
            'approve_status' => ['required|in:1,2', '审批状态必填'],
            'refuse_reason' => ['required_if:approve_status,2', '拒绝原因必填']
        ];
        $error = validator_params($params, $rule);
        if ($error) {
            throw new ResourceException($error);
        }

        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'apply_id' => $apply_id,
            'approve_status' => 0,
        ];
        $chiefApplyInfoService = new CommunityChiefApplyInfoService();
        $applyInfo = $chiefApplyInfoService->getInfo($filter);
        if (!$applyInfo) {
            throw new ResourceException('找不到申请信息');
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $data = [
                'approve_status' => $params['approve_status']
            ];
            if ($params['approve_status'] == '2') {
                $data['refuse_reason'] = $params['refuse_reason'];
            }
            $chiefApplyInfoService->updateBy($filter, $data);

            if ($params['approve_status'] == '1') {
                $data = [
                    'user_id' => $applyInfo['user_id'],
                    'company_id' => $applyInfo['company_id'],
                    'distributor_ids'=> [$applyInfo['distributor_id']],
                    'chief_mobile' => $applyInfo['chief_mobile'],
                    'chief_name' => $applyInfo['chief_name'],
                ];
                $chiefService = new CommunityChiefService();
                $chiefService->createChief($data);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/chief/setMemberCommunity",
     *     summary="设置某个会员成为团长",
     *     tags={"社区团管理端"},
     *     description="设置某个会员成为团长",
     *     operationId="setMemberCommunity",
     *     @SWG\Parameter( name="user_id", in="query", description="用户user_id", required=false, type="integer"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", required=false, type="string"),
     *     @SWG\Parameter( name="distributor_ids", in="query", description="店铺ID集合[1,2]", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function setMemberCommunity(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $distributorId = 0;
        if ($operatorType == 'distributor') { //店铺端
            $distributorId = $request->get('distributor_id');
        }
        $params = $request->all();
        $params['company_id'] = $companyId;
        $params['distributor_ids'] = [$distributorId];
        $chiefService = new CommunityChiefService();
        $result = $chiefService->createChief($params);
        return $this->response->array(['status' => $result]);
    }
}
