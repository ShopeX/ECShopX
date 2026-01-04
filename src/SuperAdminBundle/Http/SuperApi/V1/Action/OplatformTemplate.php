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

namespace SuperAdminBundle\Http\SuperApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use SuperAdminBundle\Services\OpenTemplatedService;

class OplatformTemplate extends Controller
{
    /**
     * @SWG\Get(
     *     path="/superadmin/wxappOplatform/gettemplatedraftlist",
     *     summary="获取代码草稿列表",
     *     tags={"微信小程序"},
     *     description="获取代码草稿列表",
     *     operationId="gettemplatedraftlist",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  ref="#/definitions/TemplateDraftInfo"
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function gettemplatedraftlist()
    {
        // IDX: 2367340174
        $service = new OpenTemplatedService();
        $reslut = $service->gettemplatedraftlist();

        return $this->response->array($reslut);
    }

    /**
     * @SWG\Post(
     *     path="/superadmin/wxappOplatform/addtotemplate",
     *     summary="将草稿添加到代码模板库",
     *     tags={"微信小程序"},
     *     description="将草稿添加到代码模板库",
     *     operationId="addtotemplate",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="draft_id", in="query", description="草稿箱模板id", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="操作结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function addtotemplate(Request $request)
    {
        $draft_id = $request->input('draft_id');
        if (!$draft_id) {
            throw new ResourceException('缺少draft_id');
        }
        $service = new OpenTemplatedService();
        $service->addtotemplate($draft_id);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/superadmin/wxappOplatform/gettemplatelist",
     *     summary="获取代码模板列表",
     *     tags={"微信小程序"},
     *     description="获取代码模板列表",
     *     operationId="gettemplatelist",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  ref="#/definitions/TemplateDraftInfo"
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function gettemplatelist()
    {
        $service = new OpenTemplatedService();
        $reslut = $service->gettemplatelist();

        return $this->response->array($reslut);
    }

    /**
     * @SWG\Get(
     *     path="/superadmin/wxappOplatform/deletetemplate",
     *     summary="删除代码模版",
     *     tags={"微信小程序"},
     *     description="删除代码模版",
     *     operationId="deletetemplate",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="template_id", in="query", description="模板id", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="操作结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function deletetemplate(Request $request)
    {
        // IDX: 2367340174
        $template_id = $request->input('template_id');
        if (!$template_id) {
            throw new ResourceException('缺少template_id');
        }
        $service = new OpenTemplatedService();
        $service->deletetemplate($template_id);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Definition(
     *     definition="TemplateDraftInfo",
     *     description="草稿箱模板信息",
     *     type="object",
     *     @SWG\Property( property="create_time", type="string", example="1607496664", description="创建时间"),
     *     @SWG\Property( property="user_version", type="string", example="1.0.5", description="版本"),
     *     @SWG\Property( property="user_desc", type="string", example="绝版小饭团 在 2020年12月9日下午2点50分", description="用户描述"),
     *     @SWG\Property( property="draft_id", type="string", example="78", description="草稿箱模板id"),
     *     @SWG\Property( property="source_miniprogram_appid", type="string", example="wx912913d...", description="小程序appid"),
     *     @SWG\Property( property="source_miniprogram", type="string", example="51打赏", description="小程序"),
     *     @SWG\Property( property="developer", type="string", example="绝版小饭团", description="开发者"),
     * )
     */
}
