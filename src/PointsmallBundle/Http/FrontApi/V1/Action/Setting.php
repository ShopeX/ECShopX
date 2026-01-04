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

namespace PointsmallBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use PointsmallBundle\Services\SettingService;

class Setting extends BaseController
{
    // 456353686f7058
    /**
     * @SWG\Get(
     *     path="wxapp/pointsmall/setting",
     *     summary="获取积分商城设置",
     *     tags={"积分商城"},
     *     description="获取积分商城设置",
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
     *                 @SWG\Property( property="pc_banner", type="array",
     *                      @SWG\Items( type="string", example="http://bbctest.aixue7.com/image/43/2020/12/07/66060a8f749f41a690913106fefb4c05AtMI85uxkHfLKPM8hrOJrMFvpJaxlMRL", description="pc端Banner图片地址"),
     *                  ),
     *                  @SWG\Property( property="screen", type="object",
     *                          @SWG\Property( property="brand_openstatus", type="boolean", example=true, description="筛选条件-品牌开启状态"),
     *                          @SWG\Property( property="cat_openstatus", type="boolean", example=true, description="筛选条件-分类开启状态"),
     *                          @SWG\Property( property="point_openstatus", type="boolean", example=true, description="筛选条件-积分开启状态"),
     *                          @SWG\Property( property="point_section", type="string", description="积分区间数组"
     *                          ),
     *                  ),
     *                  @SWG\Property( property="entrance", type="object",
     *                          @SWG\Property( property="mobile_openstatus", type="boolean", example=true, description="入口配置-移动端开启状态"),
     *                          @SWG\Property( property="pc_openstatus", type="boolean", example=true, description="入口配置-PC端开启状态"),
     *                  ),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getSetting(Request $request)
    {
        // 456353686f7058
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];
        $settingService = new SettingService($company_id);
        $result = $settingService->getTemplateSetting();
        $base_setting = $settingService->getSetting();
        $result['entrance'] = $base_setting['entrance'];
        return $this->response->array($result);
    }
}
