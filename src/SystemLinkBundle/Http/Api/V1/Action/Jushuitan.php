<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use SystemLinkBundle\Services\JushuitanSettingService;

use SystemLinkBundle\Services\Jushuitan\Request as JushuitanRequest;
class Jushuitan extends Controller
{
    /**
     * @SWG\Post(
     *     path="/third/jushuitan/setting",
     *     summary="聚水潭ERP配置信息保存",
     *     tags={"jushuitan"},
     *     description="聚水潭ERP配置信息保存",
     *     operationId="setSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="is_open", in="query", description="是否开启", required=true, type="string"),
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
        $companyId = app('auth')->user()->get('company_id');

        $service = new JushuitanSettingService();
        $postdata = $request->input();
        $data = [
            'is_open' => (isset($postdata['is_open']) && $postdata['is_open'] == 'true') ? true : false,
            'shop_id' => intval(trim($postdata['shop_id']))
        ];
        $setting = $service->getJushuitanSetting($companyId);
        unset($setting['is_open'], $setting['shop_id']);
        $data = array_merge($data, $setting);
        $service->setJushuitanSetting($companyId, $data);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/third/jushuitan/setting",
     *     summary="获取聚水潭ERP配置信息保存",
     *     tags={"jushuitan"},
     *     description="获取聚水潭ERPp配置信息保存",
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

        $service = new JushuitanSettingService();
        $data = $service->getJushuitanSetting($companyId);
        $jushuitanRequest = new JushuitanRequest($companyId);
        $data['oauth_url'] = $jushuitanRequest->getOauthUrl();
        $data['shop_callback_url'] = env('APP_URL').'/api/systemlink/jushuitan/'.$companyId;
        return $this->response->array($data);
    }
}
