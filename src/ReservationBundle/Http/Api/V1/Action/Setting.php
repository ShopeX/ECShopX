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

use ReservationBundle\Services\SettingService;
use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class Setting extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/reservation/setting",
     *     summary="配置预约",
     *     tags={"预约"},
     *     description="保存预约的详细配置",
     *     operationId="setSetting",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="reservationMode", in="formData", description="预约模式", required=true, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="condition", in="formData", description="预约条件", required=true, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="interval", in="formData", description="预约时间间隔", required=true, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="maxLimitDay", in="formData", description="预约周期最大值(天)", required=true, type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="minLimitHour", in="formData", description="预约周期最小值(分钟)", required=true, type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="cancelMinute", in="formData", description="取消预约最少提前(分钟)", required=true, type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="resourceName", in="formData", description="资源位名称", required=true, type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="limitType", in="formData", description="资源位限制类型", required=true, type="string",
     *     ),
     *     @SWG\Parameter(name="limit", in="formData", description="资源为限制数量", required=true, type="string"),
     *     @SWG\Parameter(name="sms_delay", in="formData", description="预约提醒通知时间（小时）", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *         @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="status", type="object",
     *                  @SWG\Property( property="id", type="string", example="1", description="id"),
     *                  @SWG\Property( property="time_interval", type="string", example="30", description="预约时间间隔"),
     *                  @SWG\Property( property="resource_name", type="string", example="到店服务", description="资源位名称"),
     *                  @SWG\Property( property="max_limit_day", type="string", example="28", description="可提前预约天数"),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function setSetting(Request $request)
    {
        $validator = app('validator')->make($request->input(), [
            'reservationMode' => 'required',
            'condition' => 'required',
            'interval' => 'required',
            'maxLimitDay' => 'required|integer|min:1',
            'sms_delay' => 'required|integer|min:1',
            'minLimitHour' => 'required|integer|min:30',
            'resourceName' => 'required',
            'cancelMinute' => 'required',
            'limitType' => 'in:not_open,limit_days,limit_nums',
        ], [
            'reservationMode.*' => '预约模式必填',
            'condition.*' => '预约条件必填',
            'interval.*' => '预约时间间隔必填',
            'sms_delay.*' => '预约短信通知提醒必须为整数',
            'maxLimitDay.*' => '最多可以提前的天数必填|必是整数|最小为1',
            'minLimitHour.*' => '最少可以提前的分钟数|必为整数|最小为30',
            'resourceName.*' => '资源位名称必填',
            'cancelMinute.*' => '取消预约最少提前n分钟',
            'limitType.*' => '预约限制必填',
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
        $postData = $request->input();

        if ($postData['minLimitHour'] >= $postData['cancelMinute']) {
            throw new ResourceException('取消预约提前分钟数必须大于最小可预约周期值');
        }
        if (in_array($postData['limitType'], ['limit_days','limit_nums'])) {
            if (!is_numeric($postData['limit'])) {
                throw new ResourceException('预约限制数据必须为整数');
            }
        }

        if (isset($postData['limitType']) && $postData['limitType'] != 'not_open' && $postData['limit']) {
            $postData['reservationNumLimit']['limit_type'] = $postData['limitType'];
            $postData['reservationNumLimit'][$postData['limitType']] = intval($postData['limit']);
        } elseif (isset($postData['limitType']) && $postData['limitType'] == 'not_open') {
            $postData['reservationNumLimit'] = array();
        }

        $authInfo = app('auth')->user()->get();
        $postData['companyId'] = $authInfo['company_id'];

        $settingService = new SettingService();
        $result = $settingService->create($postData);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/reservation/setting",
     *     summary="预约配置详细信息",
     *     tags={"预约"},
     *     description="获取预约详细的配置信息",
     *     operationId="getSetting",
     *     @SWG\Parameter(
     *          name="Authorization", in="header", description="JWT验证token", type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="id", type="string", example="1", description="id"),
     *              @SWG\Property( property="timeInterval", type="string", example="30", description="预约时间间隔(分钟)"),
     *              @SWG\Property( property="resourceName", type="string", example="到店服务", description="资源位名称"),
     *              @SWG\Property( property="maxLimitDay", type="string", example="28", description="资源位名称"),
     *              @SWG\Property( property="minLimitHour", type="string", example="60", description="可提前预约分钟数"),
     *              @SWG\Property( property="reservationCondition", type="string", example="1", description="预约条件"),
     *              @SWG\Property( property="reservationMode", type="string", example="1", description="预约模式 1:门店＋时间+资源位＋服务项目"),
     *              @SWG\Property( property="created", type="string", example="1561146298", description="创建时间"),
     *              @SWG\Property( property="updated", type="string", example="1611302365", description="更新时间"),
     *              @SWG\Property( property="cancelMinute", type="string", example="360", description="取消预约最少提前分钟数"),
     *              @SWG\Property( property="reservationNumLimit", type="string", example="a:0:{}", description="预约限制 天数或次数限制"),
     *              @SWG\Property( property="smsDelay", type="string", example="1", description="预约提醒通知(分钟)"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ReservationErrorResponse")))
     * )
     */
    public function getSetting(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $filter['company_id'] = $authInfo['company_id'];
        $settingService = new SettingService();
        $result = $settingService->get($filter);
        return $this->response->array($result);
    }
}
