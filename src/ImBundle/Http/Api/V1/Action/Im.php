<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ImBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use Dingo\Api\Exception\ResourceException;
use ImBundle\Services\ImService;

class Im extends Controller
{
    /**
     * @SWG\Get(
     *     path="/im/meiqia",
     *     summary="获取美洽配置",
     *     tags={"IM"},
     *     description="获取美洽配置",
     *     operationId="meiqiaInfo",
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
     *             @SWG\Items(ref="#/definitions/Im")
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ImErrorRespones") ) )
     * )
     */
    public function meiqiaInfo(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $imService = new ImService();
        $result = $imService->getImInfo($companyId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/im/meiqia",
     *     summary="保存美洽配置",
     *     tags={"IM"},
     *     description="保存美洽配置",
     *     operationId="meiqiaInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="channel",
     *         in="formData",
     *         description="渠道",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="common",
     *         in="formData",
     *         description="店铺客服链接",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="wxapp",
     *         in="query",
     *         description="微信小程序客服链接",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="h5",
     *         in="query",
     *         description="h5商城客服链接",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="app",
     *         in="query",
     *         description="APP商城客服链接",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="aliapp",
     *         in="query",
     *         description="支付宝小程序客服链接",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="pc",
     *         in="query",
     *         description="PC网页版商城客服链接",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="is_distributor_open",
     *         in="formData",
     *         description="是否开启店铺独立客服",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Items(ref="#/definitions/Im")
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ImErrorRespones") ) )
     * )
     */
    public function meiqiaUpdate(Request $request)
    {
        // fe10e2f6 module
        $companyId = app('auth')->user()->get('company_id');

        $data = $request->all('channel', 'common', 'wxapp', 'h5', 'app', 'aliapp', 'pc', 'is_open', 'is_distributor_open');

        $rules = [
            'channel' => ['required|in:single,multi', '客服渠道必填'],
            'common' => ['required_if:channel,single', '客服链接必填'],
            'is_distributor_open' => ['required', '是否开启店铺独立客服必填'],
        ];
        $errorMessage = validator_params($data, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $data['is_open'] = 'true' == $data['is_open'] ? true : false;
        $data['is_distributor_open'] = 'true' == $data['is_distributor_open'] ? true : false;
        $imService = new ImService();
        $result = $imService->saveImInfo($companyId, $data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/im/meiqia/distributor/{distributor_id}",
     *     summary="保存店铺美洽配置",
     *     tags={"IM"},
     *     description="保存店铺美洽配置",
     *     operationId="setDistributorMeiQia",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="path",
     *         description="店铺id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="channel",
     *         in="formData",
     *         description="渠道",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="common",
     *         in="formData",
     *         description="店铺客服链接",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="wxapp",
     *         in="query",
     *         description="微信小程序客服链接",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="h5",
     *         in="query",
     *         description="h5商城客服链接",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="app",
     *         in="query",
     *         description="APP商城客服链接",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="aliapp",
     *         in="query",
     *         description="支付宝小程序客服链接",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="pc",
     *         in="query",
     *         description="PC网页版商城客服链接",
     *         required=false,
     *         type="string"
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
     *                     @SWG\Property(property="channel", type="string", example="", description="渠道"),
     *                     @SWG\Property(property="meiqia_url", type="object", example="", description="美洽客服链接"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ImErrorRespones") ) )
     * )
     */
    public function setDistributorMeiQia($distributor_id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $imService = new ImService();
        $imInfo = $imService->getImInfo($companyId);
        if ($imInfo['is_distributor_open'] === false || $imInfo['is_distributor_open'] === 'false') {
            throw new ResourceException('未开启店铺独立客服'); 
        }

        $data = $request->all('channel', 'common', 'wxapp', 'h5', 'app', 'aliapp', 'pc');
        $data['distributor_id'] = $distributor_id;

        $rules = [
            'channel' => ['required|in:single,multi', '店铺客服渠道必填'],
            'common' => ['required_if:channel,single', '店铺客服链接必填'],
        ];
        $errorMessage = validator_params($data, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $result = $imService->saveDistributorMeiQia($companyId, $distributor_id, $data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/im/meiqia/distributor/{distributor_id}",
     *     summary="获取店铺美洽配置",
     *     tags={"IM"},
     *     description="获取店铺美洽配置",
     *     operationId="getDistributorMeiQiaSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="path",
     *         description="店铺id",
     *         required=true,
     *         type="integer"
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
     *                     @SWG\Property(property="channel", type="string", example="", description="渠道"),
     *                     @SWG\Property(property="meiqia_url", type="string", example="", description="美洽客服链接"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ImErrorRespones") ) )
     * )
     */
    public function getDistributorMeiQiaSetting($distributor_id, Request $request)
    {
        if (!$distributor_id) {
            throw new ResourceException('店铺id必填');
        }
        $companyId = app('auth')->user()->get('company_id');
        $imService = new ImService();

        $imInfo = $imService->getImInfo($companyId);
        if ($imInfo['is_distributor_open'] === false || $imInfo['is_distributor_open'] === 'false') {
            throw new ResourceException('未开启店铺独立客服'); 
        }

        $result = $imService->getDistributorMeiQia($companyId, intval($distributor_id));
        return $this->response->array($result);
    }
}
