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

namespace ReservationBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ReservationBundle\Services\ReservationManagementService as ReservationService;
use ReservationBundle\Services\ResourceLevelManagementService as ResourceLevelService;

//以下为获取可被预约的权益

class Reservation extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/wxapp/reservation",
     *     summary="用户提交预约数据",
     *     tags={"预约"},
     *     description="用户提交预约数据",
     *     operationId="createReservation",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter(
     *          name="shopId", in="formData", description="门店id", type="integer",
     *     ),
     *     @SWG\Parameter(
     *          name="shopName", in="formData", description="门店名称", type="integer",
     *     ),
     *     @SWG\Parameter(
     *          name="labelId", in="formData", description="服务项目id", type="integer",
     *     ),
     *     @SWG\Parameter(
     *          name="labelName", in="formData", description="服务项目名称", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="rightsId", in="formData", description="权益id", type="integer",
     *     ),
     *     @SWG\Parameter(
     *          name="rightsName", in="formData", description="权益名称", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="dateDay", in="formData", description="日期", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="beginTime", in="formData", description="预约时段开始时间", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="endTime", in="formData", description="预约时段结束时间", type="string",
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
     *                     @SWG\Property(property="status", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationWxaErrorRespones")))
     * )
     */
    public function createReservation(Request $request)
    {
        $authInfo = $request->get('auth');
        $input = $request->input();
        $reservationService = new ReservationService();
        $postData = [
            'begin_time' => $input['beginTime'],
            'end_time' => $input['endTime'],
            'date_day' => $input['dateDay'],
            'label_id' => $input['labelId'],
            'label_name' => $input['labelName'],
            'rights_id' => $input['rightsId'],
            'rights_name' => $input['rightsName'],
            'shop_id' => $input['shopId'],
            'shop_name' => $input['shopName'],
            'company_id' => $authInfo['company_id'],
            'status' => 'success',
            'num' => 1,
            'user_id' => $authInfo['user_id'],
            'user_name' => isset($authInfo['username']) ? $authInfo['username'] : (isset($authInfo['nickname']) ? $authInfo['nickname'] : '会员'),
            'sex' => isset($authInfo['sex']) ? $authInfo['sex'] : 0,
            'mobile' => $authInfo['mobile'],
        ];
        if (isset($input['resourceLevelId'])) {
            $postData['resource_level_id'] = $input['resourceLevelId'];
        }

        //发送小程序模版使用
        $postData['open_id'] = isset($authInfo['open_id']) ? $authInfo['open_id'] : '';
        $postData['wxapp_appid'] = isset($authInfo['wxapp_appid']) ? $authInfo['wxapp_appid'] : '';

        $reservationService->checkLimitData($postData);
        $result = $reservationService->createReservation($postData);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/reservation/dateDay",
     *     summary="获取可预约的日期",
     *     tags={"预约"},
     *     description="获取可预约的具体日期",
     *     operationId="getReservationDate",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter(
     *          name="endDate", in="query", description="权益截止日期 时间戳", type="string",
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
     *                     @SWG\Property(property="date", type="string", description="日期", example="2021-01-19"),
     *                     @SWG\Property(property="date_day", type="string", description="日期", example="01月19日"),
     *                     @SWG\Property(property="date_week", type="string", description="星期", example="周二"),
     *                     @SWG\Property(property="timestamp", type="string", description="时间戳", example="1611042893"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationWxaErrorRespones")))
     * )
     */
    public function getReservationDate(Request $request)
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
        $authInfo = $request->get('auth');
        $endDate = $request->input('endDate');
        $reservationService = new ReservationService();
        $dateDay = $reservationService->getReservationDate($authInfo['company_id'], $endDate);
        $result = [];
        foreach ($dateDay as $key => $value) {
            $week = date('D', $value);
            $result[] = [
                'date_week' => $weekDay[$week],
                'date_day' => date('m月d日', $value),
                'timestamp' => $value,
                'date' => date('Y-m-d', $value),
            ];
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/reservation/timelist",
     *     summary="获取可预约的时段",
     *     tags={"预约"},
     *     description="获取可预约的具体时段,排班情况和每个时段的预约状态",
     *     operationId="getTimelist",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter(
     *          name="shopId", in="query", description="门店id", type="integer", required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="dateDay", in="query", description="日期", type="string", required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="labelId", in="query", description="服务项目id", type="integer", required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="rightsId", in="query", description="权益ID", type="integer",
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
     *                     @SWG\Property(property="begin_time", type="string", description="开始时间", example="09:30"),
     *                     @SWG\Property(property="end_time", type="string", description="结束时间", example="10:30"),
     *                     @SWG\Property(property="status", type="integer", description="状态 0:不可预约 1:可预约", example="10:30"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationWxaErrorRespones")))
     * )
     */
    public function getTimelist(Request $request)
    {
        $authInfo = $request->get('auth');

        $companyId = $authInfo['company_id'];

        $input = $request->input();
        $levelId = '';
        if (isset($input['levelId'])) {
            $levelId = $input['levelId'];
        }
        $labelId = isset($input['labelId']) ? $input['labelId'] : null;
        $timeData = [];
        if ($input['shopId'] && $input['dateDay']) {
            //获取指定门店每天的时间切片
            $reservationService = new ReservationService();
            $timeData = $reservationService->getTimePeriod($companyId, $input['shopId'], $input['dateDay'], $labelId, $levelId);
        }
        return $this->response->array($timeData);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/reservation/recordlist",
     *     summary="获取预约记录列表",
     *     tags={"预约"},
     *     description="获取该用户的预约记录列表",
     *     operationId="getRecordList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter(
     *         name="page", in="query", description="当前页面,获取商品列表的初始偏移位置，从1开始计数", type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize", in="query", description="每页数量,最大不能超过50，并且如果传入的pageSize参数是0，那么按默认值20处理", type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="total_count", type="integer", description="总数"),
     *                 @SWG\Property(
     *                     property="list",
     *                     type="array",
     *                     @SWG\Items(
     *                     type="object",
     *                         @SWG\Property(property="recordId", type="integer", description="Id", example="1"),
     *                         @SWG\Property(property="shopId", type="integer", description="门店ID", example="1"),
     *                         @SWG\Property(property="shopName", type="string", description="门店名称", example="商派"),
     *                         @SWG\Property(property="agreementDate", type="string", description="约定日期", example="1610985600"),
     *                         @SWG\Property(property="toShopTime", type="integer", description="到店时间(时间戳)", example="1610985600"),
     *                         @SWG\Property(property="beginTime", type="string", description="到店时间(时刻字符串)", example="19:00"),
     *                         @SWG\Property(property="endTime", type="string", description="约定结束时刻(时刻字符串)", example="19:30"),
     *                         @SWG\Property(property="status", type="string", description="预约状态。可选值有 cancel-取消;-to_the_shop-已到店;-not_to_shop-未到店;-success-预约成功;-system-系统占位;", example="success"),
     *                         @SWG\Property(property="num", type="integer", description="预约数量", example="1"),
     *                         @SWG\Property(property="user_name", type="string", description="用户名", example="1"),
     *                         @SWG\Property(property="sex", type="integer", description="用户性别 1:男 2:女", example="2"),
     *                         @SWG\Property(property="mobile", type="string", description="预约人手机号", example="19:30"),
     *                         @SWG\Property(property="resourceLevelId", type="integer", description="资源位id", example="1"),
     *                         @SWG\Property(property="resourceLevelName", type="integer", description="资源位名称", example="1"),
     *                         @SWG\Property(property="rightsId", type="integer", description="服务商品id", example="1"),
     *                         @SWG\Property(property="rightsName", type="string", description="服务商品名称", example="健身次卡"),
     *                         @SWG\Property(property="labelId", type="string", description="物料Id", example="1"),
     *                         @SWG\Property(property="labelName", type="string", description="物料名称", example="瑜伽"),
     *                     )
     *                 )
     *
     *             ),
     *          ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationWxaErrorRespones")))
     * )
     */
    public function getRecordList(Request $request)
    {
        $reservationService = new ReservationService();
        $authInfo = $request->get('auth');
        $filter = [
            'user_id' => $authInfo['user_id'],
            'company_id' => $authInfo['company_id'],
        ];
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 1);

        $result = $reservationService->getRecordList($filter, $pageSize, $page);

        $levelIds = array_column($result['list'], 'resourceLevelId');
        //获取资源位详情
        $ResourceLevelService = new ResourceLevelService();
        $filterLevel['resource_level_id'] = $levelIds;
        $levelData = $ResourceLevelService->getListResourceLevel($filterLevel, false);
        foreach ($levelData['list'] as $value) {
            $data[$value['resourceLevelId']] = $value;
        }

        foreach ($result['list'] as $key => &$value) {
            if (isset($data[$value['resourceLevelId']])) {
                $value['imageUrl'] = $data[$value['resourceLevelId']]['imageUrl'];
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/reservation/getCount",
     *     summary="获取指定项目的预约量",
     *     tags={"预约"},
     *     description="获取指定项目的预约量",
     *     operationId="getRecordCount",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter(
     *          name="rights_id", in="query", description="权益id", type="string",
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
     *                     @SWG\Property(property="total_count", type="string", example="1"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationWxaErrorRespones")))
     * )
     */
    public function getRecordCount(Request $request)
    {
        $inputData = $request->all('rights_id');
        $authInfo = $request->get('auth');
        $filter = [
            'user_id' => $authInfo['user_id'],
            'rights_id' => $inputData['rights_id'],
            'company_id' => $authInfo['company_id'],
            'status' => ['success','to_the_shop'],
        ];
        $reservationService = new ReservationService();
        $result = $reservationService->getRecordCount($filter);
        return $this->response->array(['total_count' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/can/reservation/rights",
     *     summary="获取可用预约项目",
     *     tags={"预约"},
     *     description="获取可用预约项目",
     *     operationId="getCanReservationRights",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取商品列表的初始偏移位置，从1开始计数",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的pageSize参数是0，那么按默认值20处理",
     *         type="integer"
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
     *                     @SWG\Items(
     *                         @SWG\Property(property="rights_id", type="integer", description="权益Id", example="1"),
     *                         @SWG\Property(property="can_reservation", type="string", description="是否可预约 0:不可预约 1:可预约", example="1"),
     *                         @SWG\Property(property="rights_name", type="string", description="权益标题", example="汽车福利"),
     *                         @SWG\Property(property="rights_subname", type="string", description="权益子标题", example="汽车福利"),
     *                         @SWG\Property(property="total_num", type="string", description="服务商品原始总次数,0标示无限制", example="1"),
     *                         @SWG\Property(property="total_consum_num", type="string", description="总消耗次数", example="1"),
     *                         @SWG\Property(property="start_time", type="string", description="权益开始时间", example="1610985600"),
     *                         @SWG\Property(property="end_time", type="string", description="权益结束时间", example="1610985600"),
     *                         @SWG\Property(property="order_id", type="string", description="订单编号", example="3306611000097138"),
     *                         @SWG\Property(property="label_infos", type="array", description="权益的物料信息json结构",
     *                         @SWG\Items(
     *                         @SWG\Property(property="label_id", type="integer", description="服务商品id", example="1"),
     *                         @SWG\Property(property="label_name", type="string", description="服务商品名称", example="洗车大礼包"),
     *                         )
     *                         ),
     *                         @SWG\Property(property="rights_from", type="string", description="权益来源", example="购买获取"),
     *                         @SWG\Property(property="mobile", type="string", description="会员手机号", example="13000000000"),
     *                         @SWG\Property(property="status", type="string", description="权益状态; valid:有效的, expire:过期的; invalid:失效的", example="valid"),
     *                         @SWG\Property(property="is_not_limit_num", type="integer", description="限制核销次数,1:不限制；2:限制", example="2"),
     *                     )
     *
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationWxaErrorRespones")))
     * )
     */
    public function getCanReservationRights(Request $request)
    {
        $inputData = $request->all('page', 'pageSize');
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('获取权益列表出错.', $validator->errors());
        }

        //获取权益列表
        $authUser = $request->get('auth');
        $params['company_id'] = $authUser['company_id'];
        $params['user_id'] = $authUser['user_id'];
        $params['valid'] = 1;
        $params['start_time'] = time();
        $params['end_time'] = time();
        $page = $inputData['page'];
        $pageSize = $inputData['pageSize'];

        $reservationService = new ReservationService();
        $result = $reservationService->getCanReservationRightsList($params, $pageSize, $page);

        return $this->response->array($result);
    }
}
