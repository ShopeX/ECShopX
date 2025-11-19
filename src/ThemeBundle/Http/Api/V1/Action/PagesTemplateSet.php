<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThemeBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use ThemeBundle\Services\PagesTemplateSetServices;

class PagesTemplateSet extends Controller
{
    /**
     * @SWG\Post(
     *     path="/pagestemplate/set",
     *     summary="模板设置",
     *     tags={"模版"},
     *     description="模板设置",
     *     operationId="getPreAuthUrl",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="index_type",
     *         in="query",
     *         description="模板显示类型 1总部首页 2店铺首页",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="is_enforce_sync",
     *         in="query",
     *         description="店铺首页强制同步",
     *         required=false,
     *         type="integer",
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
     *                     @SWG\Property(property="id", type="int"),
     *                     @SWG\Property(property="company_id", type="int"),
     *                     @SWG\Property(property="index_type", type="int"),
     *                     @SWG\Property(property="is_enforce_sync", type="int"),
     *                     @SWG\Property(property="is_open_official_account", type="int"),
     *                     @SWG\Property(property="is_open_recommend", type="int"),
     *                     @SWG\Property(property="is_open_scan_qrcode", type="int"),
     *                     @SWG\Property(property="is_open_wechatapp_location", type="int"),
     *                     @SWG\Property(property="tab_bar", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function set(Request $request)
    {
        // ShopEx EcShopX Service Component
        $company_id = app('auth')->user()->get('company_id');
        $params['index_type'] = $request->input('index_type');
        $params['is_enforce_sync'] = $request->input('is_enforce_sync');
        $params['is_open_recommend'] = $request->input('is_open_recommend');
        $params['is_open_wechatapp_location'] = $request->input('is_open_wechatapp_location');
        $params['is_open_scan_qrcode'] = $request->input('is_open_scan_qrcode');
        $params['is_open_official_account'] = $request->input('is_open_official_account');
        $params['tab_bar'] = $request->input('tab_bar');
        $params['company_id'] = $company_id;
        $params['pages_template_id'] = $request->input('pages_template_id', 0);

        $pages = new PagesTemplateSetServices();
        $result = $pages->saveData($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/pagestemplate/setInfo",
     *     summary="获取设置信息",
     *     tags={"模版"},
     *     description="获取设置信息",
     *     operationId="getPreAuthUrl",
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
     *                     @SWG\Property(property="id", type="int"),
     *                     @SWG\Property(property="company_id", type="int"),
     *                     @SWG\Property(property="index_type", type="int"),
     *                     @SWG\Property(property="is_enforce_sync", type="int"),
     *                     @SWG\Property(property="is_open_official_account", type="int"),
     *                     @SWG\Property(property="is_open_recommend", type="int"),
     *                     @SWG\Property(property="is_open_scan_qrcode", type="int"),
     *                     @SWG\Property(property="is_open_wechatapp_location", type="int"),
     *                     @SWG\Property(property="tab_bar", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getInfo(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $params['company_id'] = $company_id;
        $params['pages_template_id'] = $request->input('pages_template_id', 0);

        $pages = new PagesTemplateSetServices();
        $result = $pages->getInfo($params);

        return $this->response->array($result);
    }
}
