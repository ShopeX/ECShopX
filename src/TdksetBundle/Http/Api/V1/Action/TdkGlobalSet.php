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

namespace TdksetBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use TdksetBundle\Services\TdkGlobalService;
use Illuminate\Http\Request;

class TdkGlobalSet extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/pcdecoration/tdkglobalset",
     *     summary="查询TDK全局设置内容",
     *     tags={"SEO"},
     *     description="查询TDK全局设置内容",
     *     operationId="tdkglobalget",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="title", type="string", example="{goods_name}_{goods_brand}_{goods_category}", description="页面标题"),
     *                  @SWG\Property( property="mate_description", type="string", example="{goods_name},{goods_brand},{goods_category},{goods_price},{goods_brief}", description="页面描述"),
     *                  @SWG\Property( property="mate_keywords", type="string", example="{goods_name}_{goods_brand}_{goods_category}", description="关键词"),
     *                  @SWG\Property( property="update_time", type="string", example="1613632360", description="更新时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/TdksetErrorResponse") ) )
     * )
     */
    public function getInfo()
    {
        $companyId = app('auth')->user()->get('company_id');
        $TdkGlobal = new TdkGlobalService();
        $data_list = $TdkGlobal->getInfo($companyId);

        return $this->response->array($data_list);
    }

    /**
     * @SWG\Post(
     *     path="/pcdecoration/tdkglobalset",
     *     summary="TDK全局设置保存",
     *     tags={"SEO"},
     *     description="TDK全局设置保存",
     *     operationId="tdkglobalsave",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="title", in="query", description="TITLE(页面标题）", type="string" ),
     *     @SWG\Parameter( name="mate_description", in="query", description="MATE_DESCRIPTION(页面描述）", type="string" ),
     *     @SWG\Parameter( name="mate_keywords", in="query", description="MATE_KEYWORDS(关键词）", type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="string", example="", description="保存成功"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/TdksetErrorResponse") ) )
     * )
     */
    public function Save(Request $request)
    {
        $reason_data = $request->all('title', 'mate_description', 'mate_keywords');
        $reason_data['mate_keywords'] = str_replace('，', ',', $reason_data['mate_keywords']);
        $reason_data['update_time'] = time();

        $companyId = app('auth')->user()->get('company_id');
        $TdkGlobal = new TdkGlobalService();
        $data = $TdkGlobal->saveSet($companyId, $reason_data);

        return $this->response->array($data);
    }
}
