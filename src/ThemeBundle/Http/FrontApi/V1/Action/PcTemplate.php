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

namespace ThemeBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use ThemeBundle\Services\ThemePcTemplateContentServices;

class PcTemplate extends Controller
{
    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/pctemplate/getTemplateContent",
     *     summary="获取pc模板页面内容",
     *     tags={"模板"},
     *     description="获取pc模板页面内容",
     *     operationId="getTemplateContent",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page_type",
     *         in="query",
     *         description="页面类型 home/header/footer/custom/product_list",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page_id",
     *         in="query",
     *         description="页面ID，自定义页传模板ID",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="company_id",
     *         in="query",
     *         description="公司编号",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="id", type="integer"),
     *                 @SWG\Property(property="name", type="string"),
     *                 @SWG\Property(property="config", type="string")
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones") ) )
     * )
     */
    public function getTemplateContent(Request $request)
    {
        $authInfo = $request->get('auth');
        $company_id = $request->get('company_id');
        $page_type = $request->get('page_type', 'home');
        $page_id = $request->input('page_id', '');

        $params = [
            'company_id' => $company_id,
            'page_type' => $page_type,
            'page_id' => $page_id,
            'user_id' => $authInfo['user_id'] ?? 0,
        ];
        $rules = [
            'company_id' => ['required', '缺少company_id'],
            'page_type' => ['required|in:home,header,footer,custom,product_list', '缺少或错误的page_type'],
            'page_id' => ['required_if:page_type,custom', '缺少page_id'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $service = new ThemePcTemplateContentServices();
        $result = $service->decorationContent($params);

        return $this->response->array($result);
    }

    public function getLoginPageSetting(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $result = app('redis')->connection('companys')->get('pc_login_page:'.$companyId);
        if (!$result) {
            $result['logo'] = '';
            $result['logo_light'] = '';
            $result['logo_dark'] = '';
            $result['background'] = '';
        } else {
            $result = json_decode($result, true);
            $result['logo_light'] = $result['logo_light'] ?? ($result['logo'] ?? '');
            $result['logo_dark'] = $result['logo_dark'] ?? ($result['logo'] ?? '');
            $result['logo'] = $result['logo'] ?? ($result['logo_light'] ?? '');
            $result['background'] = $result['background'] ?? '';
        }
        return $this->response->array($result);
    }
}
