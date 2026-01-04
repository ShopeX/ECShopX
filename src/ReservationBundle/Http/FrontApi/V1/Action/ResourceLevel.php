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
use ReservationBundle\Services\ResourceLevelManagementService as ResourceLevelService;
use ReservationBundle\Services\WorkShiftManageService;
use ReservationBundle\Services\WorkShift\WorkShiftService;
use ReservationBundle\Services\ReservationManagementService as ReservationService;

use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;

class ResourceLevel extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/resource/level",
     *     summary="获取资源位列表信息",
     *     tags={"预约"},
     *     description="获取资源位列表信息",
     *     operationId="getResourceLevelList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
     *     @SWG\Parameter(
     *          name="shop_id", in="query", description="门店id", type="integer",
     *     ),
     *     @SWG\Parameter(
     *          name="material_id", in="query", description="服务项目id", type="integer",
     *     ),
     *     @SWG\Parameter(
     *          name="work_date", in="query", description="具体日期", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="page", in="query", description="页码", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="pageSize", in="query", description="每页数据量", type="string",
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
    public function getResourceLevelList(Request $request)
    {
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];

        $input = $request->input();

        $page = $request->input('page', 1);
        $pageLimit = $request->input('pageSize', 20);

        if (isset($input['shop_id']) && $input['shop_id']) {
            $filter['shop_id'] = $input['shop_id'];
        }
        if (isset($input['work_date']) && $input['work_date']) {
            $filter['work_date'] = strtotime($input['work_date']);
        }
        $WorkShift = new WorkShiftManageService(new WorkShiftService($filter['company_id']));
        $levelWorkShift = $WorkShift->getList($filter);

        $levelIds = array_keys($levelWorkShift);

        $levelFilter['company_id'] = $authInfo['company_id'];
        if (isset($input['material_id']) && $input['material_id']) {
            $levelFilter['material_id'] = $input['material_id'];
        }
        $levelFilter['resource_level_id'] = $levelIds;
        $resourceLevelService = new ResourceLevelService();
        $MaterialList = $resourceLevelService->getListMaterial($levelFilter);
        foreach ($levelWorkShift as $levelId => &$workshifdata) {
            if (isset($MaterialList[$levelId])) {
                $workshifdata['serviceData'] = $MaterialList[$levelId];
            } else {
                unset($levelWorkShift[$levelId]);
            }
        }
        $levelIds = array_keys($levelWorkShift);

        if (isset($input['work_date']) && $input['work_date']) {
            $recordFilter['resource_level_id'] = $levelIds;
            $recordFilter['agreement_date'] = strtotime($input['work_date']);
            $ReservationService = new ReservationService();
            $recordList = $ReservationService->getLevelRecordCount($recordFilter);
            $result = [];
            foreach ($levelWorkShift as $key => $value) {
                $weekday = strtolower(date('l', $recordFilter['agreement_date']));
                if (isset($value[$weekday])) {
                    $workShift = $value[$weekday];
                    if ($workShift['typeId'] == '-1') {
                        continue;
                    }
                    unset($value[$weekday]);
                    if (isset($recordList[$key]) && $workShift['total_num'] >= $recordList[$key]) {
                        $workShift['reservationStatus'] = false;
                    } else {
                        $workShift['reservationStatus'] = true;
                    }
                    $result[] = array_merge($value, $workShift);
                }
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/resource/timeList",
     *     summary="获取资源位详情",
     *     tags={"预约"},
     *     description="获取资源位详情",
     *     operationId="getReservationDetail",
     *     @SWG\Parameter(
     *          name="shop_id", in="query", description="门店id", type="integer",
     *     ),
     *     @SWG\Parameter(
     *          name="label_id", in="query", description="服务项目id", type="integer",
     *     ),
     *     @SWG\Parameter(
     *          name="date_day", in="query", description="具体日期", type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="resource_level_id", in="query", description="资源位Id", type="string",
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
    public function getReservationDetail(Request $request)
    {
        $authInfo = $request->get('auth');

        $companyId = $authInfo['company_id'];

        if (!$request->input('shop_id')) {
            throw new ResourceException('门店必选');
        }
        if (!$request->input('resource_level_id')) {
            throw new ResourceException('资源位数据必填');
        }
        if (!$request->input('date_day')) {
            throw new ResourceException('日期必填');
        }
        if (!$request->input('label_id')) {
            throw new ResourceException('服务项目id必填');
        }
        $result = [];
        $input = $request->input();

        //获取资源位详情
        $ResourceLevelService = new ResourceLevelService();
        $filter['resource_level_id'] = $input['resource_level_id'];
        $result['levelData'] = $ResourceLevelService->getResourceLevel($filter, false);

        //获取店铺详情
        $shopsService = new ShopsService(new WxShopsService());
        $result['storeData'] = $shopsService->getShopsDetail($input['shop_id']);

        //获取指定门店每天的时间切片
        $reservationService = new ReservationService();
        $result['timeData'] = $reservationService->getTimePeriod($companyId, $input['shop_id'], $input['date_day'], $input['label_id'], $input['resource_level_id']);
        return $this->response->array($result);
    }
}
