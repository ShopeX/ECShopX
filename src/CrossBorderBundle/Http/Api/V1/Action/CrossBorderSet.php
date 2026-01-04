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

namespace CrossBorderBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use CrossBorderBundle\Services\Set;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;

class CrossBorderSet extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/crossborder/set",
     *     summary="获取跨境设置信息",
     *     tags={"跨境"},
     *     description="获取跨境设置信息",
     *     operationId="getInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property(property="crossborder_show", type="integer", example="1", description="是否显示跨境购物车：0不显示,1显示"),
     *              @SWG\Property(property="logistics", type="string", example="SF", description="跨境物流编码"),
     *              @SWG\Property(property="quota_tip", type="string", example="依据《关于跨境电子商务零售进口税收政策的通知》个人单次...", description="提示信息"),
     *              @SWG\Property(property="tax_rate", type="string", example="5", description="全局跨境税费"),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CrossBorderErrorRespones") ) )
     * )
     */
    public function getInfo()
    {
        $userinfo = app('auth')->user()->get();

        $Set = new Set();
        $data = $Set->getInfo($userinfo['company_id']);
        if (empty($data)) {
            $response = [];
        } else {
            $response['tax_rate'] = $data['tax_rate'];
            $response['quota_tip'] = $data['quota_tip'];
            $response['crossborder_show'] = $data['crossborder_show'];
            $response['logistics'] = $data['logistics'];
        }


        return $this->response->array($response);
    }

    /**
     * @SWG\Post(
     *     path="/crossborder/set",
     *     summary="跨境设置保存",
     *     tags={"跨境"},
     *     description="跨境设置保存",
     *     operationId="Save",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="crossborder_show", in="formData", description="跨境购物车是否显示：1显示，0不显示", required=true, type="integer" ),
     *     @SWG\Parameter( name="tax_rate", in="formData", description="全局税率", required=true, type="integer" ),
     *     @SWG\Parameter( name="quota_tip", in="formData", description="提示信息", required=true, type="string" ),
     *     @SWG\Parameter( name="logistics", in="formData", description="跨境物流编码", required=true, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构",  @SWG\Schema(
     *          @SWG\Property(  property="data", type="object",
     *              @SWG\Property(property="status", type="boolean"),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CrossBorderErrorRespones") ) )
     * )
     */
    public function Save(Request $request)
    {
        // 请求参数
        $params = $request->all('tax_rate', 'quota_tip', 'crossborder_show', 'logistics');

        // 验证数据
        $rules = [
            'tax_rate' => ['required', '税率不能为空'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        // 用户信息
        $userinfo = app('auth')->user()->get();
        // 操作数据
        $Set = new Set();
        $id = $Set->Save($userinfo['company_id'], $params);

        // 新增，添加
        if ($id) {
            $response['status'] = true;
            return $this->response->array($response);
        } else {
            throw new StoreResourceFailedException('操作失败');
        }
    }
}
