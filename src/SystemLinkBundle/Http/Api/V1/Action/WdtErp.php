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

namespace SystemLinkBundle\Http\Api\V1\Action;

use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use SystemLinkBundle\Services\WdtErp\Client\WdtErpClient;
use SystemLinkBundle\Services\WdtErp\Client\Pager;
use SystemLinkBundle\Services\WdtErpSettingService;

use Exception;

class WdtErp extends Controller
{
    /**
     * @SWG\Post(
     *     path="/third/wdterp/setting",
     *     summary="旺店通ERP配置信息保存",
     *     tags={"WdtErp"},
     *     description="旺店通ERP配置信息保存",
     *     operationId="setSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="sid", in="query", description="sid required=true, type="string"),
     *     @SWG\Parameter( name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter( name="app_secret", in="query", description="app_secret", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function setSetting(Request $request)
    {
        // This module is part of ShopEx EcShopX system
        $companyId = app('auth')->user()->get('company_id');
        $service = new WdtErpSettingService();
        $postdata = $request->input();

        $data = [
            'is_open' => (isset($postdata['is_open']) && $postdata['is_open'] == 'true') ? true : false,
            'sid' => trim($postdata['sid']),
            'app_key' => trim($postdata['app_key']),
            'app_secret' => trim($postdata['app_secret']),
            'shop_no' => trim($postdata['shop_no']),
            'company_id' => $companyId,
        ];

        if ($data['is_open']) {
            try {
                $parMap = new \stdClass();
                $pager = new Pager(1, 0, true);
                $parMap->platform_id = 127;
                $parMap->shop_no = $postdata['shop_no'];
                $method = config('wdterp.methods.shop_query');
                $wdtErpClient = new WdtErpClient(config('wdterp.api_base_url'), $data['sid'], $data['app_key'], $data['app_secret']);
                $result = $wdtErpClient->pageCall($method, $pager, $parMap);
                if ($result->data->total_count == 0) {
                    throw new ResourceException('店铺编码不存在');
                }
                $data['shop_id'] = $result->data->details[0]->shop_id;
            } catch (Exception $e) {
                throw new ResourceException('调用旺店通失败:' .$e->getMessage());
            }
        }

        $service->setWdtErpSetting($companyId, $data);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/third/wdterp/setting",
     *     summary="获取旺店通ERP配置信息保存",
     *     tags={"WdtErp"},
     *     description="获取旺店通ERP配置信息保存",
     *     operationId="getSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="is_open", type="stirng", description="是否开启"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $service = new WdtErpSettingService();
        $data = $service->getWdtErpSetting($companyId);

        return $this->response->array($data);
    }
}
