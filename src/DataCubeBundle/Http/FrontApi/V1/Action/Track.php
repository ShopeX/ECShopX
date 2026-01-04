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

namespace DataCubeBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use DataCubeBundle\Services\TrackService;

class Track extends Controller
{
    /**
     * @SWG\Post(
     *     path="/wxapp/track/viewnum",
     *     summary="统计浏览人数",
     *     tags={"统计"},
     *     description="统计浏览人数",
     *     operationId="addViewNum",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="monitor_id", in="query", description="门店ID", required=true, type="string"),
     *     @SWG\Parameter( name="source_id", in="query", description="交易商品ID", required=true, type="string"),
     *     @SWG\Parameter( name="open_id", in="query", description="openid", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DataCubeErrorRespones") ) )
     * )
     */
    public function addViewNum(Request $request)
    {
        // 暂时隐藏掉了，不记录了
        
        $authInfo = $request->get('auth');

        $trackService = new TrackService();

        $params = $request->all("monitor_id", "source_id",'open_id');
        $params['company_id'] = $authInfo['company_id'];
        $params['source_id'] = trim($params['source_id']);
        $params['monitor_id'] = trim($params['monitor_id']);
        $params['open_id'] = trim($params['open_id']);
        $trackService->addViewNum($params);
        
        return $this->response->array(['status' => true]);
    }
}
