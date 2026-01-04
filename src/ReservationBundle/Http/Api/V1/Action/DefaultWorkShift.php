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

use ReservationBundle\Services\WorkShift\DefaultService;

use ReservationBundle\Services\WorkShiftManageService;
use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DefaultWorkShift extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/workshift/default",
     *     summary="新增门店默认排班",
     *     tags={"预约"},
     *     description="新增门店默认排班",
     *     operationId="createDefaultWorkShift",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string",required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="shop_id", in="query", description="店铺Id", required=true, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="defaultData", in="query", description="默认排班数据数组 包含字段:label-周一,name-monday,value-排班类型id", required=true, type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                @SWG\Property( property="status", type="object",
     *                    @SWG\Property( property="monday", type="object",
     *                        @SWG\Property( property="typeId", type="string", example="-1", description="班次类型id"),
     *                    ),
     *                    @SWG\Property( property="tuesday", type="object",
     *                        @SWG\Property( property="typeId", type="string", example="75", description="班次类型id"),
     *                    ),
     *                    @SWG\Property( property="wednesday", type="object",
     *                        @SWG\Property( property="typeId", type="string", example="-1", description="班次类型id"),
     *                    ),
     *                    @SWG\Property( property="thursday", type="object",
     *                        @SWG\Property( property="typeId", type="string", example="-1", description="班次类型id"),
     *                    ),
     *                    @SWG\Property( property="friday", type="object",
     *                        @SWG\Property( property="typeId", type="string", example="-1", description="班次类型id"),
     *                    ),
     *                    @SWG\Property( property="saturday", type="object",
     *                        @SWG\Property( property="typeId", type="string", example="-1", description="班次类型id"),
     *                    ),
     *                    @SWG\Property( property="sunday", type="object",
     *                        @SWG\Property( property="typeId", type="string", example="-1", description="班次类型id"),
     *                    ),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function createDefaultWorkShift(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $input = $request->input();
        $validator = app('validator')->make($input, [
            'shop_id' => 'required',
        ], [
            'shop_id.*' => '门店id必填',
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
        $postdata['shop_id'] = $input['shop_id'];
        $postdata['company_id'] = $authInfo['company_id'];
        if (!isset($input['defaultData'])) {
            throw new ResourceException('排班数据不能为空');
        }
        foreach ($input['defaultData'] as $key => $value) {
            $paramsdata[$value['name']] = ['typeId' => $value['value']];
        }
        $workShiftService = new WorkShiftManageService(new DefaultService());
        $result = $workShiftService->updateData($postdata, $paramsdata);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Delete(
     *     path="/workshift/default",
     *     summary="删除门店默认排班",
     *     tags={"预约"},
     *     description="删除门店默认排班",
     *     operationId="deleteDefaultWorkShift",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string",required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="shopId", in="query", description="店铺Id", required=true, type="string",
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function deleteDefaultWorkShift(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $input = $request->input();
        $validator = app('validator')->make($input, [
            'shop_id' => 'required',
        ], [
            'shop_id.*' => '门店id必填',
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
        $filter = [
            'shop_id' => $input['shop_id'],
            'company_id' => $authInfo['company_id'],
        ];
        $workShiftService = new WorkShiftManageService(new DefaultService());
        $result = $workShiftService->deleteData($filter);
        return $this->response->noContent();
    }

    /**
     * @SWG\Get(
     *     path="/workshift/default",
     *     summary="获取门店默认排班",
     *     tags={"预约"},
     *     description="获取门店默认排班",
     *     operationId="getDefaultWorkShift",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string",required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="shopId", in="query", description="店铺Id", required=true, type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                @SWG\Property( property="status", type="object",
     *                    @SWG\Property( property="monday", type="object",
     *                        @SWG\Property( property="typeId", type="string", example="-1", description="班次类型id"),
     *                    ),
     *                    @SWG\Property( property="tuesday", type="object",
     *                        @SWG\Property( property="typeId", type="string", example="75", description="班次类型id"),
     *                    ),
     *                    @SWG\Property( property="wednesday", type="object",
     *                        @SWG\Property( property="typeId", type="string", example="-1", description="班次类型id"),
     *                    ),
     *                    @SWG\Property( property="thursday", type="object",
     *                        @SWG\Property( property="typeId", type="string", example="-1", description="班次类型id"),
     *                    ),
     *                    @SWG\Property( property="friday", type="object",
     *                        @SWG\Property( property="typeId", type="string", example="-1", description="班次类型id"),
     *                    ),
     *                    @SWG\Property( property="saturday", type="object",
     *                        @SWG\Property( property="typeId", type="string", example="-1", description="班次类型id"),
     *                    ),
     *                    @SWG\Property( property="sunday", type="object",
     *                        @SWG\Property( property="typeId", type="string", example="-1", description="班次类型id"),
     *                    ),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function getDefaultWorkShift(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $input = $request->input();
        $validator = app('validator')->make($input, [
            'shop_id' => 'required',
        ], [
            'shop_id.*' => '门店id',
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
        $filter = [
            'shop_id' => $input['shop_id'],
            'company_id' => $authInfo['company_id'],
        ];
        $workShiftService = new WorkShiftManageService(new DefaultService());
        $result = $workShiftService->get($filter);
        return $this->response->array($result);
    }
}
