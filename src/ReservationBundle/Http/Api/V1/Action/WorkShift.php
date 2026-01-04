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

use ReservationBundle\Services\WorkShift\WorkShiftService;

use ReservationBundle\Services\WorkShiftManageService;
use ReservationBundle\Services\DateService;
use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WorkShift extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/workshift",
     *     summary="新增排班",
     *     tags={"预约"},
     *     description="工作排班新增",
     *     operationId="createWorkShift",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string",required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="shopId", in="formData", description="店铺Id", required=true, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="shiftTypeId", in="formData", description="排班类型id", required=true, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="dateDay", in="formData", description="日期，例：2017-09-09", required=true, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="resourceLevelId", in="formData", description="指定资源位", required=true, type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *        @SWG\Property( property="data", type="object",
     *            @SWG\Property( property="status", type="object",
     *                @SWG\Property( property="id", type="string", example="21933", description="id"),
     *                @SWG\Property( property="shop_id", type="string", example="466", description="门店id"),
     *                @SWG\Property( property="resource_level_id", type="string", example="141", description="资源位id"),
     *                @SWG\Property( property="work_date", type="string", example="1611504000", description="工作日期"),
     *                @SWG\Property( property="shift_type_id", type="string", example="70", description="工作班次类型id"),
     *             ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function createWorkShift(Request $request)
    {
        // Ref: 1996368445
        $authInfo = app('auth')->user()->get();
        $input = $request->input();
        if (strtotime($input['dateDay']) <= time()) {
            throw new ResourceException('只能对今天之后做排班');
        }

        $postData = [
            'companyId' => intval($authInfo['company_id']),
            'shopId' => $input['shopId'],
            'shiftTypeId' => $input['shiftTypeId'],
            'dateDay' => strtotime($input['dateDay']),
            'resourceLevelId' => $input['resourceLevelId'],
        ];
        $workShiftService = new WorkShiftManageService(new WorkShiftService());
        $result = $workShiftService->createData($postData);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Patch(
     *     path="/workshift",
     *     summary="编辑排班",
     *     tags={"预约"},
     *     description="工作排班编辑",
     *     operationId="updateWorkShift",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string",required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="shopId", in="query", description="店铺Id", required=true, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="shiftTypeId", in="query", description="排班类型id", required=true, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="dateDay", in="query", description="日期，例：2017-09-09", required=true, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="resourceLevelId", in="query", description="指定资源位", required=true, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="id", in="query", description="指定资源位", required=true, type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *        @SWG\Property( property="data", type="object",
     *            @SWG\Property( property="status", type="object",
     *                @SWG\Property( property="id", type="string", example="21933", description="id"),
     *                @SWG\Property( property="shop_id", type="string", example="466", description="门店id"),
     *                @SWG\Property( property="resource_level_id", type="string", example="141", description="资源位id"),
     *                @SWG\Property( property="work_date", type="string", example="1611504000", description="工作日期"),
     *                @SWG\Property( property="shift_type_id", type="string", example="70", description="工作班次类型id"),
     *             ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function updateWorkShift(Request $request)
    {
        // Ref: 1996368445
        $authInfo = app('auth')->user()->get();
        $input = $request->input();
        if (strtotime($input['dateDay']) <= time()) {
            throw new ResourceException('历史排班不可编辑');
        }
        $filter['work_date'] = strtotime($input['dateDay']);
        $filter['resource_level_id'] = $input['resourceLevelId'];
        $filter['company_id'] = $authInfo['company_id'];
        $filter['shop_id'] = $input['shopId'];
        $filter['id'] = $input['id'];

        $postData['shiftTypeId'] = $input['shiftTypeId'];

        $workShiftService = new WorkShiftManageService(new WorkShiftService());
        $result = $workShiftService->updateData($filter, $postData);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Delete(
     *     path="/workshift/{id}",
     *     summary="删除排班",
     *     tags={"预约"},
     *     description="某个资源位工作排班删除",
     *     operationId="deleteWorkShift",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string",required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="id", in="path", description="排班自增id", required=true, type="string",
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
    public function deleteWorkShift(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $input = $request->input();
        $filter['company_id'] = $authInfo['company_id'];
        $filter['id'] = $input['id'];

        $workShiftService = new WorkShiftManageService(new WorkShiftService());
        $result = $workShiftService->deleteData($filter);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/workshift",
     *     summary="排班列表",
     *     tags={"预约"},
     *     description="工作排班列表",
     *     operationId="getListWorkShift",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string",required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="shopId", in="query", description="店铺Id", required=true, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="dateData", in="query", description="日期,例：2017-09-09", type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property(
     *                     property="weekDay",
     *                     type="object",
     *                     @SWG\Property(
     *                         property="monday",
     *                         type="object",
     *                         @SWG\Property( property="ymd", type="string", example="2021-01-25", description="日期"),
     *                         @SWG\Property( property="md", type="string", example="周一(01-25)", description="星期"),
     *                     ),
     *                     @SWG\Property( property="tuesday", type="object",
     *                         @SWG\Property( property="ymd", type="string", example="2021-01-26", description="日期"),
     *                         @SWG\Property( property="md", type="string", example="周二(01-26)", description="星期"),
     *                     ),
     *                     @SWG\Property( property="wednesday", type="object",
     *                         @SWG\Property( property="ymd", type="string", example="2021-01-27", description="日期"),
     *                         @SWG\Property( property="md", type="string", example="周三(01-27)", description="星期"),
     *                     ),
     *                     @SWG\Property( property="thursday", type="object",
     *                         @SWG\Property( property="ymd", type="string", example="2021-01-28", description="日期"),
     *                         @SWG\Property( property="md", type="string", example="周四(01-28)", description="星期"),
     *                     ),
     *                     @SWG\Property( property="friday", type="object",
     *                         @SWG\Property( property="ymd", type="string", example="2021-01-29", description="日期"),
     *                         @SWG\Property( property="md", type="string", example="周五(01-29)", description="星期"),
     *                     ),
     *                     @SWG\Property( property="saturday", type="object",
     *                         @SWG\Property( property="ymd", type="string", example="2021-01-30", description="日期"),
     *                         @SWG\Property( property="md", type="string", example="周六(01-30)", description="星期"),
     *                     ),
     *                     @SWG\Property( property="sunday", type="object",
     *                         @SWG\Property( property="ymd", type="string", example="2021-01-31", description="日期"),
     *                         @SWG\Property( property="md", type="string", example="周日(01-31)", description="星期"),
     *                     ),
     *                  ),
     *                @SWG\Property( property="resourceLevel", type="array",
     *                    @SWG\Items( type="object",
     *                        @SWG\Property( property="resourceLevelId", type="string", example="141", description="资源位id"),
     *                        @SWG\Property( property="shopId", type="string", example="466", description="门店id"),
     *                        @SWG\Property( property="shopName", type="string", example="宾阳路21号小区", description="门店名称"),
     *                        @SWG\Property( property="name", type="string", example="资源位1", description="资源位名称"),
     *                        @SWG\Property( property="description", type="string", example="111", description="资源位描述"),
     *                        @SWG\Property( property="status", type="string", example="active", description="状态,active:有效，invalid: 失效"),
     *                        @SWG\Property( property="imageUrl", type="string", example="", description="图片url"),
     *                        @SWG\Property( property="quantity", type="string", example="1", description="数量"),
     *                        @SWG\Property( property="created", type="string", example="1611307658", description="创建时间"),
     *                        @SWG\Property( property="updated", type="string", example="null", description="更新时间"),
     *                        @SWG\Property( property="monday", type="object",
     *                            @SWG\Property( property="id", type="string", example="21933", description="id"),
     *                            @SWG\Property( property="shopId", type="string", example="466", description="门店id"),
     *                            @SWG\Property( property="workDate", type="string", example="1611504000", description="工作日期"),
     *                            @SWG\Property( property="shiftTypeId", type="string", example="72", description="工作班次类型id"),
     *                            @SWG\Property( property="resourceLevelId", type="string", example="141", description="资源位id"),
     *                            @SWG\Property( property="typeId", type="string", example="72", description="排班类型id"),
     *                            @SWG\Property( property="typeName", type="string", example="白班", description="排班类型名称"),
     *                            @SWG\Property( property="beginTime", type="string", example="08:00", description="排班类型开始时间"),
     *                            @SWG\Property( property="endTime", type="string", example="18:00", description="排班类型结束时间"),
     *                            @SWG\Property( property="status", type="string", example="valid", description="类型状态invalid/valid"),
     *                          ),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function getListWorkShift(Request $request)
    {
        $input = $request->input();
        $validator = app('validator')->make($request->all(), [
            'shopId' => 'required',
            'dateData' => 'required',
        ], [
            'shopId.*' => '店铺id必填',
            'dateData.*' => '周期时间必填',
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

        $result = [];
        $authInfo = app('auth')->user()->get();
        $companyId = $authInfo['company_id'];
        $filter['company_id'] = $companyId;

        if (isset($input['shopId']) && $input['shopId']) {
            $filter['shop_id'] = $input['shopId'];
        }

        if (isset($input['dateData']) && $input['dateData']) {
            list($begin, $end) = explode('-', $input['dateData']);
            $dateData['begin_date'] = $begin;
            $dateData['end_date'] = $end;
        } else {
            //获取今天所在周的开始日期和结束日期
            $dateService = new DateService();
            $dateData = $dateService->getWeek();
        }
        $result['weekDay'] = $this->getWeek($dateData);

        //获取指定门店所有资源位的排班
        $filter['begin_date'] = $dateData['begin_date'];
        $filter['end_date'] = $dateData['end_date'];
        $workShiftService = new WorkShiftManageService(new WorkShiftService());
        $workShiftLists = $workShiftService->getList($filter);
        foreach ($workShiftLists as $list) {
            $result['resourceLevel'][] = $list;
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/getweekday",
     *     summary="获取每年的周日期",
     *     tags={"预约"},
     *     description="获取每年的周日期",
     *     operationId="getEveryYearWeeks",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string",required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="year", in="query", description="年份", type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                     property="list",
     *                     type="array",
     *                     description="时间列表",
     *                     @SWG\Items(
     *                         type="object",
     *                         @SWG\Property(property="label", type="string", description="时间段(时间戳)", example="1609084800-1609603200"),
     *                         @SWG\Property(property="name", type="string", description="时间段", example="12月28日-01月03日"),
     *                         @SWG\Property(property="value", type="string", description="id", example="1"),
     *                     )
     *                 ),
     *                 @SWG\Property(
     *                     property="week",
     *                     @SWG\Items(
     *                         type="object",
     *                         @SWG\Property(property="label", type="string", description="时间段(时间戳)", example="1609084800-1609603200"),
     *                         @SWG\Property(property="name", type="string", description="时间段", example="12月28日-01月03日"),
     *                         @SWG\Property(property="value", type="string", description="id", example="1"),
     *                         )
     *                 ),
     *
     *             ),
     *          ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function getEveryYearWeeks(Request $request)
    {
        $year = date('Y');
        if ($request->input('year')) {
            $year = $request->input('year');
        }
        $dateService = new DateService();
        $weekDay = $dateService->getWeeks($year);
        if (date('Y') === $year) {
            $W = Date('W');
        } else {
            $W = 1;
        }
        $i = 0;
        $nowtime = strtotime(date("{$year}-m-d"));
        foreach ($weekDay as $key=>$value) {
            $data[$i] = [
                'value' => $key,
                'label' => $value['begin_date']. '-'. $value['end_date'],
                'name' => date('m月d日', $value['begin_date']) . '-'. date('m月d日', $value['end_date']),
            ];
            if ($value['begin_date'] <= $nowtime && $nowtime <= $value['end_date']) {
                $week['label'] = $value['begin_date']. '-'. $value['end_date'];
                $week['value'] = $i;
                $week['name'] = date('m月d日', $value['begin_date']) . '-'. date('m月d日', $value['end_date']);
            }
            $i++;
        }

        $result['list'] = $data;
        $result['week'] = $week;
        return $this->response->array($result);
    }

    private function getWeek($today)
    {
        $oneDay = 24 * 60 * 60;
        $weekTable = [
            'monday' => [
                'ymd' => date('Y-m-d', $today['begin_date']),
                'md' => "周一(".date('m-d', $today['begin_date']).")",
            ],
            'tuesday' => [
                'ymd' => date('Y-m-d', $today['begin_date'] + $oneDay),
                'md' => "周二(".date('m-d', $today['begin_date'] + $oneDay).")",
            ],
            'wednesday' => [
                'ymd' => date('Y-m-d', $today['begin_date'] + $oneDay * 2),
                'md' => "周三(".date('m-d', $today['begin_date'] + $oneDay * 2).")",
            ],
            'thursday' => [
                'ymd' => date('Y-m-d', $today['begin_date'] + $oneDay * 3),
                'md' => "周四(".date('m-d', $today['begin_date'] + $oneDay * 3).")"
            ],
            'friday' => [
                'ymd' => date('Y-m-d', $today['begin_date'] + $oneDay * 4),
                'md' => "周五(".date('m-d', $today['begin_date'] + $oneDay * 4).")",
            ],
            'saturday' => [
                'ymd' => date('Y-m-d', $today['begin_date'] + $oneDay * 5),
                'md' => "周六(".date('m-d', $today['begin_date'] + $oneDay * 5).")",
            ],
            'sunday' => [
                'ymd' => date('Y-m-d', $today['end_date']),
                'md' => "周日(".date('m-d', $today['end_date']).")",
            ],
        ];
        return $weekTable;
    }
}
