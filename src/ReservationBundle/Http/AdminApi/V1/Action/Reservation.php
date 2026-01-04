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

namespace ReservationBundle\Http\AdminApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ReservationBundle\Services\ReservationManagementService as ReservationService;
use ReservationBundle\Services\ResourceLevelManagementService as ResourceLevelService;
use MembersBundle\Services\MemberService;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;

class Reservation extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/reservation/getdate",
     *     summary="获取可预约的日期列表及资源位列表(暂时弃用)",
     *     tags={"预约"},
     *     description="获取可预约的日期列表及资源位列表",
     *     operationId="getDateList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="shop_id", in="query", description="门店id", required=true, type="string" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string"),
     *                 )
     *             ),
     *         ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationWxaErrorRespones")))
     * )
     */
    public function getDateList(Request $request)
    {
        $weekDay = [
            'Mon' => '周一',
            'Tue' => '周二',
            'Wed' => '周三',
            'Thu' => '周四',
            'Fri' => '周五',
            'Sat' => '周六',
            'Sun' => '周日',
        ];
        $authInfo = $this->auth->user();
        if ($authInfo['salesperson_type'] != 'admin') {
            throw new ResourceException('您无此项的权限');
        }

        $shopId = $request->input('shop_id') ;
        if (!$shopId) {
            throw new ResourceException('门店信息有误');
        }
        if ($authInfo['shop_ids'] && $shopId && !in_array($shopId, $authInfo['shop_ids'])) {
            throw new ResourceException('门店信息有误');
        }
        $reservationService = new ReservationService();
        $dateDay = $reservationService->getReservationDate($authInfo['company_id']);
        foreach ($dateDay as $key => $value) {
            $week = date('D', $value);
            $dateList[] = [
                'date_week' => $weekDay[$week],
                'date_day' => date('m-d', $value),
                'timestamp' => $value,
                'date' => date('Y-m-d', $value),
            ];
        }
        $result['dateList'] = $dateList;

        $ResourceLevelService = new ResourceLevelService();
        $filter = [
            'shop_id' => $shopId,
            'company_id' => $authInfo['company_id'],
        ];
        $list = $ResourceLevelService->getListResourceLevel($filter);
        $result['resourceLevelList'] = $list['list'];

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/resourcelevel/list",
     *     summary="获取指定门店的所有资源位(暂时弃用)",
     *     tags={"预约"},
     *     description="获取指定门店的所有资源位",
     *     operationId="getResourceLevelList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="shop_id", in="query", description="门店id", required=true, type="string" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string"),
     *                 )
     *             ),
     *         ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationWxaErrorRespones")))
     * )
     */
    public function getResourceLevelList(Request $request)
    {
        $authInfo = $this->auth->user();
        if ($authInfo['salesperson_type'] != 'admin') {
            throw new ResourceException('您无此项的权限');
        }
        $shopId = $request->input('shop_id') ;
        if (!$shopId) {
            throw new ResourceException('门店信息有误');
        }
        if ($authInfo['shop_ids'] && $shopId && !in_array($shopId, $authInfo['shop_ids'])) {
            throw new ResourceException('门店信息有误');
        }
        $ResourceLevelService = new ResourceLevelService();
        $filter = [
            'shop_id' => $shopId,
            'company_id' => $authInfo['company_id'],
        ];
        $result = $ResourceLevelService->getListResourceLevel($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/reservation/list",
     *     summary="获取每天每时每刻的预约记录(暂时弃用)",
     *     tags={"预约"},
     *     description="获取每天每时每刻的预约记录",
     *     operationId="getReservationList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="shop_id", in="query", description="门店id", required=true, type="string" ),
     *     @SWG\Parameter( name="day_date", in="query", description="日期", required=true, type="string"),
     *     @SWG\Parameter( name="resource_level_id", in="query", description="资源位id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string"),
     *                 )
     *             ),
     *         ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationWxaErrorRespones")))
     * )
     */
    public function getReservationList(Request $request)
    {
        $authInfo = $this->auth->user();
        if ($authInfo['salesperson_type'] != 'admin') {
            throw new ResourceException('您无此项的权限');
        }
        $shopId = $request->input('shop_id') ;
        if (!$shopId) {
            throw new ResourceException('门店信息有误');
        }
        if ($authInfo['shop_ids'] && $shopId && !in_array($shopId, $authInfo['shop_ids'])) {
            throw new ResourceException('门店信息有误');
        }

        $input = $request->input();

        $ReservationService = new ReservationService();
        $filter = [
            'shop_id' => $shopId,
            'company_id' => $authInfo['company_id'],
            'agreement_date' => strtotime($input['day_date']),
        ];

        if (isset($input['resource_level_id']) && $input['resource_level_id']) {
            $filter['resource_level_id'] = $input['resource_level_id'];
        }

        $list = $ReservationService->getReservationRecord($filter);
        return $this->response->array($list);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/reservation/updateStatus",
     *     summary="修改预约记录状态(暂时弃用)",
     *     tags={"预约"},
     *     description="修改预约记录状态",
     *     operationId="updateRecordStatus",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="record_id", in="query", description="预约记录id", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string"),
     *                 )
     *             ),
     *         ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationWxaErrorRespones")))
     * )
     */
    public function updateRecordStatus(Request $request)
    {
        $authInfo = $this->auth->user();
        $input = $request->input();
        $filter = [
            'record_id' => $input['record_id'],
            'company_id' => $authInfo['company_id'],
        ];
        $ReservationService = new ReservationService();
        $result = $ReservationService->updateStatus($input['status'], $filter);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/reservation",
     *     summary="用户提交预约数据(暂时弃用)",
     *     tags={"预约"},
     *     description="用户提交预约数据",
     *     operationId="createReservation",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="shop_id", in="formData", description="门店id", required=true, type="string" ),
     *     @SWG\Parameter( name="labelId", in="formData", description="服务项目id", type="integer", ),
     *     @SWG\Parameter( name="labelName", in="formData", description="服务项目名称", type="string", ),
     *     @SWG\Parameter( name="rightsId", in="formData", description="权益id", type="integer", ),
     *     @SWG\Parameter( name="rightsName", in="formData", description="权益名称", type="string", ),
     *     @SWG\Parameter( name="dateDay", in="formData", description="日期", type="string", ),
     *     @SWG\Parameter( name="beginTime", in="formData", description="预约时段开始时间", type="string", ),
     *     @SWG\Parameter( name="endTime", in="formData", description="预约时段结束时间", type="string", ),
     *     @SWG\Parameter( name="userName", in="formData", description="客户姓名", type="string", ),
     *     @SWG\Parameter( name="mobile", in="formData", description="客户手机号", type="string", ),
     *     @SWG\Parameter( name="sex", in="formData", description="客户性别", type="integer", ),
     *     @SWG\Parameter( name="userId", in="formData", description="用户id", type="integer", ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string"),
     *                 )
     *             ),
     *         ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationWxaErrorRespones")))
     * )
     */
    public function createReservation(Request $request)
    {
        $authInfo = $this->auth->user();
        $input = $request->input();
        $validator = app('validator')->make($input, [
            'dateDay' => 'required',
            'beginTime' => 'required',
            'labelId' => 'required',
            'labelName' => 'required',
            'rightsId' => 'required',
            'rightsName' => 'required',
            'mobile' => 'required',
            'userId' => 'required',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException($errmsg);
        }

        $shopId = $request->input('shop_id') ;
        if (!$shopId) {
            throw new ResourceException('门店信息有误');
        }
        if ($authInfo['shop_ids'] && $shopId && !in_array($shopId, $authInfo['shop_ids'])) {
            throw new ResourceException('门店信息有误');
        }

        $shopsService = new ShopsService(new WxShopsService());
        $shopdata = $shopsService->getShopsDetail($shopId);
        if (!$shopdata) {
            throw new ResourceException('门店信息有误');
        }
        $reservationService = new ReservationService();
        $postData = [
            'shop_id' => $shopId,
            'shop_name' => $shopdata['store_name'],
            'begin_time' => $input['beginTime'],
            'end_time' => $input['endTime'],
            'date_day' => $input['dateDay'],
            'label_id' => $input['labelId'],
            'label_name' => $input['labelName'],
            'rights_id' => $input['rightsId'],
            'rights_name' => $input['rightsName'],
            'company_id' => $authInfo['company_id'],
            'user_id' => isset($input['userId']) ? $input['userId'] : '',
            'user_name' => isset($input['userName']) ? $input['userName'] : '',
            'mobile' => isset($input['mobile']) ? $input['mobile'] : '',
            'sex' => isset($input['sex']) ? $input['sex'] : 0,
            'status' => 'success',
            'num' => 1,
        ];
        if (isset($input['resourceLevelId']) && $input['resourceLevelId']) {
            $postData['resource_level_id'] = $input['resourceLevelId'];
        }
        $result = $reservationService->createReservation($postData);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/getRightsList",
     *     summary="根据手机号获取可被预约的项目(暂时弃用)",
     *     tags={"预约"},
     *     description="根据手机号获取可被预约的项目",
     *     operationId="getUserRightsListData",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter(name="mobile", in="query", description="预约记录id", required=true, type="string"),
     *     @SWG\Parameter(name="end_time", in="query", description="预约到店日期", required=true, type="string"),
     *     @SWG\Parameter(name="resource_level_id", in="query", description="预约资源位id", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string"),
     *                 )
     *             ),
     *         ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationWxaErrorRespones")))
     * )
     */
    public function getUserRightsListData(Request $request)
    {
        $page = 1;
        $pageSize = 100;
        $input = $request->input();
        $validator = app('validator')->make($input, [
            'mobile' => 'required',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException($errmsg);
        }

        $authInfo = $this->auth->user();

        //获取该手机号对应的用户
        $memberService = new MemberService();
        $filter = [
            'company_id' => $authInfo['company_id'],
            'mobile' => $input['mobile'],
        ];
        $userData = $memberService->getMemberInfo($filter);
        $result['userData'] = null;
        if ($userData && isset($userData['user_id'])) {
            //获取该用户可被预约的权益项目
            $reservationService = new ReservationService();
            $params['company_id'] = $authInfo['company_id'];
            $params['user_id'] = $userData['user_id'];
            if ($input['end_time']) {
                $params['end_time'] = strtotime($input['end_time']);
            }
            $result['rightsList'] = $reservationService->getCanReservationRightsList($params, $pageSize, $page);
            $result['userData'] = $userData;
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/reservation/getTimeList",
     *     summary="获取可被预约的时间段(暂时弃用)",
     *     tags={"预约"},
     *     description="获取可被预约的时间段",
     *     operationId="getTimeList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="shop_id", in="query", description="门店id", required=true, type="string" ),
     *     @SWG\Parameter( name="dateDay", in="query", description="日期", type="string", ),
     *     @SWG\Parameter( name="resource_level_id", in="query", description="预约资源位id", type="string", ),
     *     @SWG\Parameter( name="labelId", in="query", description="服务项目id", type="integer", ),
     *     @SWG\Parameter( name="rightsId", in="query", description="权益id", type="integer", ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string"),
     *                 )
     *             ),
     *         ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationWxaErrorRespones")))
     * )
     */
    public function getTimeList(Request $request)
    {
        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'];
        $shopId = $request->input('shop_id') ;
        if (!$shopId) {
            throw new ResourceException('门店信息有误');
        }
        if ($authInfo['shop_ids'] && $shopId && !in_array($shopId, $authInfo['shop_ids'])) {
            throw new ResourceException('门店信息有误');
        }

        $input = $request->input();

        //获取指定门店每天的时间切片
        $reservationService = new ReservationService();
        $timeData = $reservationService->getTimePeriod($companyId, $shopId, $input['dateDay'], $input['labelId'], $input['resource_level_id']);
        $result = [];
        foreach ($timeData as $key => $value) {
            if ($value['status'] == 1) {
                $result[] = $value;
            }
        }
        return $this->response->array($result);
    }
}
