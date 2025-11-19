<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace TdksetBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use TdksetBundle\Services\TdkGivenService;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;

class TdkGivenSet extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/pcdecoration/tdkgivenset/{type}",
     *     summary="查询TDK指定页面设置内容",
     *     tags={"SEO"},
     *     description="查询TDK指定页面设置内容",
     *     operationId="tdkgetInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="type", in="path", description="类型(details, list)", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="title", type="string", example="{search_keywords}_{category}_{category_path}_{shop_name}", description="页面标题"),
     *                  @SWG\Property( property="mate_description", type="string", example="{search_keywords}_{category}_{category_path}_{shop_name},{search_keywords},{category},{category_path},{shop_name}", description="页面描述"),
     *                  @SWG\Property( property="mate_keywords", type="string", example="{search_keywords},{category},{category_path},{shop_name}", description="关键词"),
     *                  @SWG\Property( property="update_time", type="string", example="1606292294", description="更新时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/TdksetErrorResponse") ) )
     * )
     */
    public function getInfo($type, Request $request)
    {
        if ($type != 'details' and $type != 'list') {
            throw new StoreResourceFailedException('类型错误');
        }

        $companyId = app('auth')->user()->get('company_id');
        $TdkGiven = new TdkGivenService();
        $data_list = $TdkGiven->getInfo($type, $companyId);

        return $this->response->array($data_list);
    }

    /**
     * @SWG\Post(
     *     path="/pcdecoration/tdkgivenset/{type}",
     *     summary="TDK指定页面设置保存",
     *     tags={"SEO"},
     *     description="TDK指定页面设置保存",
     *     operationId="tdkSave",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="type", in="path", description="类型(details, list)", required=true, type="string"),
     *     @SWG\Parameter( name="title", in="query", description="TITLE(页面标题）", required=true, type="string" ),
     *     @SWG\Parameter( name="mate_description", in="query", description="MATE_DESCRIPTION(页面描述）", required=true, type="string" ),
     *     @SWG\Parameter( name="mate_keywords", in="query", description="MATE_KEYWORDS(关键词）", required=true, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="string", example="", description="保存成功"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/TdksetErrorResponse") ) )
     * )
     */
    public function Save($type, Request $request)
    {
        if ($type != 'details' and $type != 'list') {
            throw new StoreResourceFailedException('类型错误');
        }

        $reason_data = $request->all('title', 'mate_description', 'mate_keywords');
        $reason_data['mate_keywords'] = str_replace('，', ',', $reason_data['mate_keywords']);
        $reason_data['update_time'] = time();

        $companyId = app('auth')->user()->get('company_id');
        $TdkGiven = new TdkGivenService();
        $data = $TdkGiven->saveSet($type, $companyId, $reason_data);

        return $this->response->array($data);
    }
}
