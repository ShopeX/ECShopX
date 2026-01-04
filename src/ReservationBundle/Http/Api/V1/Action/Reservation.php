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

namespace ReservationBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ReservationBundle\Services\ReservationManagementService as ReservationService;

class Reservation extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/reservation",
     *     summary="商家主动占用资源位",
     *     tags={"预约"},
     *     description="设置某个时段的资源位不可用",
     *     operationId="create",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="shopId", in="query", description="门店id", type="integer",
     *     ),
     *     @SWG\Parameter(
     *          name="shopName", in="query", description="门店名称", type="integer",
     *     ),
     *     @SWG\Parameter(
     *          name="resourceLevelId", in="query", description="资源位id", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="resourceLevelName", in="query", description="资源位名称", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="labelId", in="query", description="预约项目id", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="labelName", in="query", description="预约项目名称", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="rightsId", in="query", description="权益id", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="rightsName", in="query", description="权益id", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="userName", in="query", description="客户姓名", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="mobile", in="query", description="客户手机号", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="sex", in="query", description="客户性别", type="integer",
     *     ),
     *     @SWG\Parameter(
     *          name="userId", in="query", description="用户id", type="integer",
     *     ),
     *     @SWG\Parameter(
     *          name="dateDay", in="query", description="日期", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="beginTime", in="query", description="开始时间", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="endTime", in="query", description="结束时间", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="instead", in="query", description="操作类型（代客预约 or 系统占位）", type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", example=true),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function create(Request $request)
    {
        $input = $request->input();

        $validator = app('validator')->make($input, [
            'shopId' => 'required',
            'resourceLevelId' => 'required',
            'dateDay' => 'required',
            'beginTime' => 'required',
            'instead' => 'required',
            'mobile' => 'required_if:instead,user',
            'rightsId' => 'required_if:instead,user'
        ], [
            'shopId.*' => '门店必选',
            'resourceLevelId.*' => '资源位数据必填',
            'dateDay.*' => '日期必填',
            'beginTime.*' => '时段必选',
            'instead.*' => '操作类型必填',
            'mobile.*' => '代客预约时，手机号必填',
            'rightsId.*' => '代客预约时,预约项目必填'
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

        $dateDay = strtotime($input['dateDay']);
        $authInfo = app('auth')->user()->get();
        $ReservationService = new ReservationService();
        $paramsData = [
            'company_id' => $authInfo['company_id'],
            'shop_id' => $input['shopId'],
            'shop_name' => $input['shopName'],
            'resource_level_id' => $input['resourceLevelId'],
            'resource_level_name' => $input['resourceLevelName'],
            'label_id' => $input['labelId'],
            'label_name' => $input['labelName'],
            'rights_id' => $input['rightsId'],
            'rights_name' => $input['rightsName'],
            'date_day' => date('Y-m-d', strtotime($input['dateDay'])),
            'begin_time' => $input['beginTime'],
            'end_time' => $input['endTime'],
            'num' => 1,
            'status' => 'system',
            'user_name' => isset($input['userName']) ? $input['userName'] : '',
            'mobile' => isset($input['mobile']) ? $input['mobile'] : '',
            'sex' => isset($input['sex']) ? $input['sex'] : 0,
            'user_id' => isset($input['userId']) ? $input['userId'] : '',
        ];
        if (isset($input['instead']) && $input['instead'] == "user") {
            $paramsData['status'] = 'success';
        }
        $paramsData = array_filter($paramsData);
        $result = $ReservationService->createReservation($paramsData);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/reservation",
     *     summary="查看预约记录",
     *     tags={"预约"},
     *     description="查看指定日期的预约记录",
     *     operationId="getList",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="shopId", in="query", description="门店id", type="integer",
     *     ),
     *     @SWG\Parameter(
     *          name="dateDay", in="query", description="日期 2021-01-22", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="page", in="query", description="当前页面,获取列表的初始偏移位置，从1开始计数", type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize", in="query", description="每页数量,最大不能超过100", type="integer",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *        @SWG\Property( property="data", type="object",
     *            @SWG\Property( property="list", type="array",
     *                @SWG\Items( type="object",
     *                    @SWG\Property( property="resourceLevelId", type="string", example="141", description="资源位id"),
     *                    @SWG\Property( property="shopId", type="string", example="466", description="门店id"),
     *                    @SWG\Property( property="shopName", type="string", example="宾阳路21号小区", description="门店名称"),
     *                    @SWG\Property( property="name", type="string", example="资源位1", description="资源位名称"),
     *                    @SWG\Property( property="description", type="string", example="111", description="资源位描述"),
     *                    @SWG\Property( property="status", type="string", example="active", description="状态,active:有效，invalid: 失效"),
     *                    @SWG\Property( property="imageUrl", type="string", example="", description="资源位图片url"),
     *                    @SWG\Property( property="quantity", type="string", example="1", description="数量"),
     *                    @SWG\Property( property="created", type="string", example="1611307658", description="创建时间"),
     *                    @SWG\Property( property="updated", type="string", example="null", description="更新时间"),
     *                       ),
     *                  ),
     *            @SWG\Property( property="total_count", type="string", example="1", description="总条数"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function getList(Request $request)
    {
        // 0x456353686f7058
        $input = $request->input();
        $validator = app('validator')->make($request->all(), [
            'page' => 'integer|min:1',
            'pageSize' => 'integer|min:1|max:100',
            'dateDay' => 'required',
        ], [
            'page.*' => '分页页码为整数|最小为1',
            'pageSize.*' => '分页每页数据为整数|最小为1|最大100',
            'dateDay.*' => '日期必填',
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

        $dateDay = strtotime($input['dateDay']);
        $authInfo = app('auth')->user()->get();

        $ReservationService = new ReservationService();
        $result = $ReservationService->getReservationList($authInfo['company_id'], $input['shopId'], $dateDay, $input);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="reservation/period",
     *     summary="获取每天预约时间段",
     *     tags={"预约"},
     *     description="获取每天预约时间段",
     *     operationId="getEveryDayTimePeriod",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string",required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="shopId", in="query", description="门店id", type="integer", required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="dateDay", in="query", description="日期时间 2021-01-22", type="string", required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *        @SWG\Property( property="data", type="object",
     *            @SWG\Property( property="tableTitle", type="array",
     *                @SWG\Items( type="object",
     *                    @SWG\Property( property="begin", type="string", example="资源位", description="开始时间"),
     *                ),
     *                @SWG\Items( type="object",
     *                    @SWG\Property( property="begin", type="string", example="00:36", description="开始时间"),
     *                    @SWG\Property( property="end", type="string", example="01:06", description="结束时间"),
     *                ),
     *            ),
     *            @SWG\Property( property="maxLimitDay", type="string", example="1", description="可提前预约天数"),
     *            @SWG\Property( property="minLimitHour", type="string", example="30", description="可提前预约分钟数"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function getEveryDayTimePeriod(Request $request)
    {
        $input = $request->input();
        $validator = app('validator')->make($input, [
            'shopId' => 'required',
            'dateDay' => 'required',
        ], [
            'shopId.*' => '门店必填',
            'dateDay.*' => '日期必填',
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

        $resultArray = [];
        $authInfo = app('auth')->user()->get();
        $ReservationService = new ReservationService();

        $result = $ReservationService->getTimePeriod($authInfo['company_id'], $input['shopId'], strtotime($input['dateDay']));
        if (!$result) {
            return $this->response->array($resultArray);
        }

        foreach ($result['timeData'] as $timeData) {
            $tableTitle[] = [
                'begin' => date('H:i', $timeData['begin']),
                'end' => date('H:i', $timeData['end']),
            ];
        }
        $name['begin'] = $result['resourceName'];
        array_unshift($tableTitle, $name);

        $resultArray['tableTitle'] = $tableTitle;
        $resultArray['maxLimitDay'] = $result['maxLimitDay'];
        $resultArray['minLimitHour'] = $result['minLimitHour'];

        return $this->response->array($resultArray);
    }

    /**
     * @SWG\Get(
     *     path="/reservation/getData",
     *     summary="获取一天中每个时段的预约记录(弃用)",
     *     tags={"预约"},
     *     description="获取一天中每个时段的预约记录(弃用)",
     *     operationId="getReservationData",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="shopId", in="query", description="门店id", type="integer",
     *     ),
     *     @SWG\Parameter(
     *          name="dateDay", in="query", description="日期", type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="boolean", example=true),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function getReservationData(Request $request)
    {
        $input = $request->input();
        $validator = app('validator')->make($request->all(), [
            'shopId' => 'integer',
            'dateDay' => 'required',
        ], [
            'shopId.*' => '门店id必填',
            'dateDay.*' => '日期必填',
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

        $dateDay = strtotime($input['dateDay']);
        $authInfo = app('auth')->user()->get();

        $ReservationService = new ReservationService();
        $result = $ReservationService->getReservationList($authInfo['company_id'], $input['shopId'], $dateDay, $input);
        return $this->response->array($result);
    }
}
