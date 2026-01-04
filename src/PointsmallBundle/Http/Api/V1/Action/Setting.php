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

namespace PointsmallBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Response;
use PointsmallBundle\Services\SettingService;

class Setting extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/pointsmall/setting",
     *     summary="保存基础设置",
     *     tags={"积分商城"},
     *     description="保存基础设置",
     *     operationId="saveSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="freight_type",
     *         in="query",
     *         description="物流费用类型 cash:现金 point:积分",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="proportion",
     *         in="query",
     *         description="积分商城汇率设置 freight_type='point'时必填",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="rounding_mode",
     *         in="query",
     *         description="积分取整 up:向上 down:向下 freight_type='point'时必填",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="entrance.mobile_openstatus",
     *         in="query",
     *         description="入口设置 移动端包含小程序/H5/APP 开启状态 true:开启 false:关闭",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="entrance.pc_openstatus",
     *         in="query",
     *         description="入口设置 pc端开启状态 true:开启 false:关闭",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="status", type="boolean", description="状态"),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function saveSetting(Request $request)
    {
        $params = $request->all('freight_type', 'proportion', 'rounding_mode', 'entrance');
        // freight_type cash:现金 point:积分
        $rules = [
            'freight_type' => ['in:cash,point', '物流费用必选'],
            'proportion' => ['required_if: freight_type,point | numeric | min:1', '积分商城汇率设置必填'],
            'rounding_mode' => ['required_if: freight_type,point', '积分取整设置必填'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $companyId = app('auth')->user()->get('company_id');
        $settingService = new SettingService($companyId);

        $result = $settingService->saveSetting($params);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/pointsmall/setting",
     *     summary="获取基础设置",
     *     tags={"积分商城"},
     *     description="获取基础设置",
     *     operationId="getSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="freight_type", type="string", description="物流费用类型 cash:现金 point:积分"),
     *                 @SWG\Property(property="proportion", type="string", description="积分商城汇率设置"),
     *                 @SWG\Property(property="rounding_mode", type="string", description="积分取整 up:向上 down:向下"),
     *                 @SWG\Property(
     *                     property="entrance",
     *                     type="object",
     *                     description="入口设置数据",
     *                     @SWG\Property(property="mobile_openstatus", type="boolean", description="移动端开启状态"),
     *                     @SWG\Property(property="pc_openstatus", type="boolean", description="PC端开启状态"),
     *                 ),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $settingService = new SettingService($companyId);
        $result = $settingService->getSetting();
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/pointsmall/template/setting",
     *     summary="保存模板设置",
     *     tags={"积分商城"},
     *     description="保存模板设置",
     *     operationId="saveTemplateSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="pc_banner",
     *         in="query",
     *         description="图片地址数组",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="screen.brand_openstatus",
     *         in="query",
     *         description="筛选条件-品牌 开启状态 true:开启 false:关闭",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="screen.cat_openstatus",
     *         in="query",
     *         description="筛选条件-分类 开启状态 true:开启 false:关闭",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="screen.point_openstatus",
     *         in="query",
     *         description="筛选条件-积分 开启状态 true:开启 false:关闭",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="screen.point_section",
     *         in="query",
     *         description="筛选条件-积分区间数组 最多5组",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="status", type="boolean", description="状态"),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function saveTemplateSetting(Request $request)
    {
        $params = $request->all('pc_banner', 'screen');

        $companyId = app('auth')->user()->get('company_id');
        $settingService = new SettingService($companyId);

        $result = $settingService->saveTemplateSetting($params);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/pointsmall/template/setting",
     *     summary="获取模板设置",
     *     tags={"积分商城"},
     *     description="获取模板设置",
     *     operationId="getTemplateSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="pc_banner", type="string", description="pc端banner数组"),
     *                 @SWG\Property(
     *                     property="screen",
     *                     type="object",
     *                     description="筛选条件数据",
     *                     @SWG\Property(property="brand_openstatus", type="boolean", description="品牌开启状态"),
     *                     @SWG\Property(property="cat_openstatus", type="boolean", description="分类开启状态"),
     *                     @SWG\Property(property="point_openstatus", type="boolean", description="积分开启状态"),
     *                     @SWG\Property(property="point_section", type="string", description="积分区间数组"),
     *                 ),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getTemplateSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $settingService = new SettingService($companyId);
        $result = $settingService->getTemplateSetting();
        return $this->response->array($result);
    }
}
