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

namespace WechatBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use SuperAdminBundle\Services\WxappTemplateService;
use Dingo\Api\Exception\DeleteResourceFailedException;

class wxappTemplate extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxappTemplate/domain",
     *     summary="获取小程序需要用到的域名",
     *     tags={"微信"},
     *     description="获取小程序需要用到的域名",
     *     operationId="getDomain",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
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
     *                     @SWG\Property(property="requestdomain", type="string"),
     *                     @SWG\Property(property="wsrequestdomain", type="string"),
     *                     @SWG\Property(property="uploaddomain", type="string"),
     *                     @SWG\Property(property="downloaddomain", type="string"),
     *                     @SWG\Property(property="webviewdomain", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getDomain()
    {
        // KEY: U2hvcEV4
        $wxappTempService = new WxappTemplateService();
        return $wxappTempService->getDomain();
    }

    /**
     * @SWG\Put(
     *     path="/wxappTemplate/domain",
     *     summary="设置小程序需要用到的域名(全局)",
     *     tags={"微信"},
     *     description="设置小程序需要用到的域名(全局)",
     *     operationId="setDomain",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="domain[requestdomain]", description="request合法域名" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="domain[wsrequestdomain]", description="socket合法域名" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="domain[uploaddomain]", description="uploadFile合法域名" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="domain[downloaddomain]", description="downloadFile合法域名" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="domain[webviewdomain]", description="业务合法域名" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones")))
     * )
     */
    public function setDomain(Request $request)
    {
        // 判断是否为saas，如果是 无权限
        if (config('common.system_is_saas')) {
            throw new DeleteResourceFailedException("当前系统无权限");
        }

        $postdata = $request->input();

        if (!isset($postdata['domain']['requestdomain']) || !$postdata['domain']['requestdomain']) {
            throw new DeleteResourceFailedException("request合法域名必填");
        }

        $wxappTempService = new WxappTemplateService();
        $wxappTempService->setDomain($postdata['domain']);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/wxappTemplate/wxapp",
     *     summary="微信模板编辑",
     *     tags={"微信"},
     *     description="微信模板编辑",
     *     operationId="updateWxappTemplate",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="id", description="id" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="template_id", description="模版id" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example=""),
     *                  @SWG\Property( property="key_name", type="string", example=""),
     *                  @SWG\Property( property="name", type="string", example=""),
     *                  @SWG\Property( property="tag", type="string", example=""),
     *                  @SWG\Property( property="template_id", type="string", example=""),
     *                  @SWG\Property( property="template_id_2", type="string", example=""),
     *                  @SWG\Property( property="version", type="string", example=""),
     *                  @SWG\Property( property="is_only", type="string", example=""),
     *                  @SWG\Property( property="description", type="string", example=""),
     *                  @SWG\Property( property="domain", type="string", example=""),
     *                  @SWG\Property( property="is_disabled", type="string", example=""),
     *                  @SWG\Property( property="created", type="string", example=""),
     *                  @SWG\Property( property="updated", type="string", example=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones")))
     * )
     */
    public function updateWxappTemplate(Request $request)
    {
        // 判断是否为saas，如果是 无权限
        if (config('common.system_is_saas')) {
            throw new DeleteResourceFailedException("当前系统无权限");
        }

        $wxappTempService = new WxappTemplateService();
        $postdata = $request->input();

        if (!$postdata['id']) {
            throw new DeleteResourceFailedException("参数错误");
        }

        $filter['id'] = $postdata['id'];

        unset($postdata['id']);
        $data = $wxappTempService->updateOneBy($filter, $postdata);
        return $this->response->array($data);
    }
}
