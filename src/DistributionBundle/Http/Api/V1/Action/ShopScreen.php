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

namespace DistributionBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;


use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\SliderService;
use DistributionBundle\Services\AdvertisementService;

class ShopScreen extends Controller
{
    /**
     * @SWG\Post(
     *     path="/shopScreen/slider",
     *     summary="大屏端轮播图设置",
     *     tags={"店铺"},
     *     description="大屏轮播图设置",
     *     operationId="saveSlider",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true,  type="string"),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="query",
     *         description="分销商id",
     *         type="number",
     *     ),
     *     @SWG\Parameter(
     *         name="title",
     *         in="query",
     *         description="标题",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="sub_title",
     *         in="query",
     *         description="副标题",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="style_params",
     *         in="query",
     *         description="选项参数",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="image_list",
     *         in="body",
     *         description="轮播项",
     *         type="array",
     *         @SWG\Schema(
     *             @SWG\Items(
     *                 @SWG\Property(
     *                    property="url",
     *                    description="图片路径",
     *                    type="string"
     *                 ),
     *                 @SWG\Property(
     *                     property="desc",
     *                     description="文字描述",
     *                    type="string"
     *                 )
     *             )
     *         )
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
     *                     @SWG\Property(property="slide_id", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
    */
    public function saveSlider(Request $request)
    {
        $params = $request->all();
        $rules = [
            'title' => ['required', trans('DistributionBundle/Controllers/ShopScreen.title_required')],
            'sub_title' => ['required', trans('DistributionBundle/Controllers/ShopScreen.sub_title_required')],
            'style_params' => ['required', trans('DistributionBundle/Controllers/ShopScreen.style_params_required')],
            'image_list' => ['required', trans('DistributionBundle/Controllers/ShopScreen.image_list_required')]
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }
        $params['distributor_id'] = $request->input('distributor_id', 0);
        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;
        $sliderService = new SliderService();
        $data = $sliderService->save($companyId, $params);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/shopScreen/slider",
     *     summary="获取轮播图接口",
     *     tags={"店铺"},
     *     description="大屏端获取轮播接口",
     *     operationId="getSlider",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="分销商id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string", example="true"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getSlider(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $validator = app('validator')->make($request->all(), [
            'distributor_id' => 'required|integer|min:0',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('DistributionBundle/Controllers/ShopScreen.params_error'), $validator->errors());
        }
        $distributor_id = $request->input('distributor_id');
        $sliderService = new SliderService();
        $filter['company_id'] = $companyId;
        $filter['distributor_id'] = $distributor_id;
        $data = $sliderService->getInfo($filter);

        if (!$data) {
            $data['company_id'] = $companyId;
            $data['distributor_id'] = $distributor_id;
            $data['desc_status'] = 'true';
            $data['image_list'] = [];
            $data['style_params']['content'] = 'true';
            $data['style_params']['current'] = 'false';
            $data['style_params']['dot'] = 'true';
            $data['style_params']['dotColor'] = 'dark';
            $data['style_params']['dotCover'] = 'false';
            $data['style_params']['dotLocation'] = 'center';
            $data['style_params']['interval'] = 'false';
            $data['style_params']['numNavShape'] = 'false';
            $data['style_params']['padded'] = 'false';
            $data['style_params']['rounded'] = 'false';
            $data['style_params']['shape'] = 'circle';
            $data['style_params']['spacing'] = 'false';
            $data['sub_title'] = '';
            $data['title'] = '';
        }

        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/shopScreen/advertisement",
     *     summary="首屏广告设置",
     *     tags={"店铺"},
     *     description="首屏广告",
     *     operationId="addAdvertisement",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="title", in="query", description="广告标题", required=true, type="string"),
     *     @SWG\Parameter( name="thumb_img", in="query", description="缩略图", required=true, type="string"),
     *     @SWG\Parameter( name="media_type", in="query", description="类型", required=true, type="string"),
     *     @SWG\Parameter( name="media_url", in="query", description="广告图/视频", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string", example="true"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */

    public function addAdvertisement(Request $request)
    {
        $params = $request->all();
        if (!array_filter($params)) {
            throw new StoreResourceFailedException(trans('DistributionBundle/Controllers/ShopScreen.ads_params_error'));
        }
        if (!$request->get('title')) {
            throw new StoreResourceFailedException(trans('DistributionBundle/Controllers/ShopScreen.ads_required'));
        }
        $validator = app('validator')->make($request->all(), [
            'title' => 'required|string',
            'media_url' => 'required|string',
            'media_type' => 'required|in:image,video',
            'thumb_img' => 'required|string',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('DistributionBundle/Controllers/ShopScreen.params_error'), $validator->errors());
        }

        $auth = app('auth')->user()->get();
        $params['company_id'] = $auth['company_id'];
        $params['operator_id'] = $auth['operator_id'];
        $params['distributor_id'] = $request->input('distributor_id', 0);
        if (isset($params['release_status']) && (!$params['release_status'] || $params['release_status'] === 'false')) {
            $params['release_status'] = false;
            $params['release_time'] = 0;
        } elseif (isset($params['release_status'])) {
            $params['release_status'] = true;
            $params['release_time'] = time();
        }
        $advertisementService = new AdvertisementService();
        $result = $advertisementService->create($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/shopScreen/advertisement/{id}",
     *     summary="首屏广告删除",
     *     tags={"店铺"},
     *     description="description",
     *     operationId="deleteAdvertisement",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="广告ID", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string", example="true"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function deleteAdvertisement($id, Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $id;
        $advertisementService = new AdvertisementService();
        $result = $advertisementService->deleteBy($filter);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Put(
     *     path="/shopScreen/advertisement",
     *     summary="发布/排序 首页广告",
     *     tags={"店铺"},
     *     description="description",
     *     operationId="updateAdvertisement",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="inputdata", in="body", description="参数组", required=true, type="array",
     *          @SWG\Schema(
    *             @SWG\Items(
    *                 @SWG\Property(
    *                    property="id",
    *                    description="id",
    *                    type="integer"
    *                 ),
    *                 @SWG\Property(
    *                     property="release_status",
    *                     description="是否发布",
    *                    type="string"
    *                 ),
    *                 @SWG\Property(
    *                     property="sort",
    *                     description="排序",
    *                    type="string"
    *                 )
    *             )
    *          )
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
     *                     @SWG\Property(property="status", type="string", example="true"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function updateAdvertisement(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $inputdata = $request->get('inputdata');
        $advertisementService = new AdvertisementService();
        $result = $advertisementService->updateStatusOrSort($companyId, $inputdata);
        return $this->response->array(['status' => $result]);
    }

    /**
    * @SWG\Get(
    *     path="/shopScreen/advertisement",
    *     summary="开屏广告列表接口",
    *     tags={"店铺"},
    *     description="开屏广告列表",
    *     operationId="getAdvertisements",
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
    *                 )
    *             ),
    *          ),
    *     ),
    *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
    * )
    */
    public function getAdvertisement(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $advertisementService = new AdvertisementService();
        $filter['company_id'] = $companyId;
        $distributor_id = $request->input('distributor_id', 0);
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $orderBy = ["sort" => "DESC","created" => "DESC"];
        $filter['distributor_id'] = $distributor_id;
        $data = $advertisementService->lists($filter, $orderBy, $pageSize, $page);
        return $this->response->array($data);
    }
}
