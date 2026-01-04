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

use ReservationBundle\Services\WorkShift\TypeService;

use ReservationBundle\Services\WorkShiftManageService;
use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WorkShiftType extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/shifttype",
     *     summary="添加排班类型",
     *     tags={"预约"},
     *     description="添加工作排班的类型",
     *     operationId="createShiftType",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string", required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="typeName", in="query", description="类型名称", required=true, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="beginTime", in="query", description="开始时间 00:00", required=true, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="endTime", in="query", description="结束时间 23:59", required=true, type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *        @SWG\Property( property="data", type="object",
     *            @SWG\Property( property="status", type="object",
     *                @SWG\Property( property="type_id", type="string", example="80", description="id"),
     *                @SWG\Property( property="type_name", type="string", example="上半天", description="排班名称"),
     *                @SWG\Property( property="begin_time", type="string", example="09:00", description="开始时间"),
     *                @SWG\Property( property="end_time", type="string", example="12:00", description="结束时间"),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function createShiftType(Request $request)
    {
        $validator = app('validator')->make($request->input(), [
            'typeName' => 'required',
            'beginTime' => 'required',
            'endTime' => 'required'
        ], [
            'typeName.*' => '类型名称必填',
            'beginTime.*' => '开始时间必填',
            'endTime.*' => '结束时间必填',
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

        $authInfo = app('auth')->user()->get();
        $input = $request->input();
        $postData = [
            'companyId' => $authInfo['company_id'],
            'typeName' => $input['typeName'],
            'beginTime' => $input['beginTime'],
            'endTime' => $input['endTime'],
        ];
        $workShiftService = new WorkShiftManageService(new TypeService());
        $result = $workShiftService->createData($postData);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Patch(
     *     path="/shifttype",
     *     summary="编辑排班类型",
     *     tags={"预约"},
     *     description="编辑工作排班的类型",
     *     operationId="updateShiftType",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string",required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="typeName", in="query", description="类型名称", required=true, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="typeId", in="query", description="类型Id", required=true, type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *        @SWG\Property( property="data", type="object",
     *            @SWG\Property( property="status", type="object",
     *                @SWG\Property( property="type_id", type="string", example="80", description="id"),
     *                @SWG\Property( property="type_name", type="string", example="上半天", description="排班名称"),
     *                @SWG\Property( property="begin_time", type="string", example="09:00", description="开始时间"),
     *                @SWG\Property( property="end_time", type="string", example="12:00", description="结束时间"),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function updateShiftType(Request $request)
    {
        // KEY: U2hvcEV4
        $validator = app('validator')->make($request->input(), [
            'typeName' => 'required',
        ], [
            'typeName.*' => '类型名称必填',
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
        $authInfo = app('auth')->user()->get();
        $input = $request->input();
        $filter['type_id'] = $input['typeId'];
        $filter['company_id'] = $authInfo['company_id'];
        $postData = [
            'companyId' => $authInfo['company_id'],
            'typeName' => $input['typeName'],
            //'beginTime' => $input['beginTime'],
            //'endTime' => $input['endTime'],
        ];
        $workShiftService = new WorkShiftManageService(new TypeService());
        $result = $workShiftService->updateData($filter, $postData);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Delete(
     *     path="/shifttype/{typeId}",
     *     summary="删除排班类型",
     *     tags={"预约"},
     *     description="删除工作排班的类型",
     *     operationId="deleteShiftType",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string",required=true,
     *     ),
     *     @SWG\Parameter(
     *          name="typeId", in="path", description="id", type="string",required=true,
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
    public function deleteShiftType($tyepId)
    {
        $filter['type_id'] = $tyepId;

        $authInfo = app('auth')->user()->get();
        $filter['company_id'] = $authInfo['company_id'];

        $workShiftService = new WorkShiftManageService(new TypeService());
        $result = $workShiftService->deleteData($filter);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/shifttype",
     *     summary="排班类型列表",
     *     tags={"预约"},
     *     description="工作排班的类型列表",
     *     operationId="getListShiftType",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string",required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *        @SWG\Property( property="data", type="object",
     *            @SWG\Property( property="list", type="array",
     *                @SWG\Items( type="object",
     *                    @SWG\Property( property="typeName", type="string", example="休息", description="名称"),
     *                    @SWG\Property( property="beginTime", type="string", example="00:00", description="开始时间"),
     *                    @SWG\Property( property="endTime", type="string", example="23:59", description="结束时间"),
     *                    @SWG\Property( property="typeId", type="string", example="-1", description="id"),
     *                       ),
     *                  ),
     *            @SWG\Property( property="total_count", type="string", example="8", description="总条数"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function getListShiftType(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $filter['company_id'] = $authInfo['company_id'];

        $workShiftService = new WorkShiftManageService(new TypeService());
        $result = $workShiftService->getList($filter);
        return $this->response->array($result);
    }
}
