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

namespace PromotionsBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use PromotionsBundle\Services\RegisterPromotionsService;
use PromotionsBundle\Services\DistributorPromotionService;

use DistributionBundle\Services\DistributorService;

class RegisterController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/promotions/register/distributor",
     *     summary="创建/修改 注册促销(分销商)(废弃)",
     *     tags={"营销"},
     *     description="创建/修改 注册促销(分销商)",
     *     operationId="createRegister",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="is_open", in="query", description="是否开启", required=true, type="string"),
     *     @SWG\Parameter( name="ad_title", in="query", description="标题", required=true, type="string"),
     *     @SWG\Parameter( name="ad_pic", in="query", description="广告图片URL", required=true, type="string"),
     *     @SWG\Parameter( name="promotions_value", in="query", description="促销方案", required=true, type="string"),
     *     @SWG\Parameter( name="register_type", in="query", description="促销方式类型", type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="分销商id", type="string"),
     *     @SWG\Parameter( name="id", in="query", description="id", required=false, type="string"),
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function createRegister(Request $request)
    {
        $params = $request->input();
        $registerPromotionsService = new RegisterPromotionsService();
        $companyId = app('auth')->user()->get('company_id');
        $data = $request->all('id', 'is_open', 'ad_title', 'ad_pic', 'promotions_value', 'distributor_id', 'register_type');
        if (!isset($data['register_type']) || !$data['register_type']) {
            $data['register_type'] = 'distributor';
        }
        $registerPromotionsService->saveRegisterPromotionsConfig($companyId, $data);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/register/distributor",
     *     summary="获取注册促销(分销商)(废弃)",
     *     tags={"营销"},
     *     description="获取注册促销(分销商)",
     *     operationId="getRegisterList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页码", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="分页页数", required=true, type="string"),
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getRegisterList(Request $request)
    {
        $data = $request->all('page', 'pageSize');
        $page = $data['page'] ?: 1;
        $pageSize = $data['pageSize'] ?: 10;
        $filter['register_type'] = 'distributor';
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $distributorPromotionService = new DistributorPromotionService();
        $result = $distributorPromotionService->getDistributorPromotionList($filter, $page, $pageSize);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/register/distributor/{id}",
     *     summary="获取注册促销(分销商)(废弃)",
     *     tags={"营销"},
     *     description="获取注册促销(分销商)",
     *     operationId="getRegisterInfo",
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
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getRegisterInfo($id, Request $request)
    {
        $distributorPromotionService = new DistributorPromotionService();
        $filter['register_type'] = 'distributor';
        $filter['id'] = $id;
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $result = $distributorPromotionService->getInfo($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/promotions/register/distributor/{id}",
     *     summary="删除注册促销(分销商)(废弃)",
     *     tags={"营销"},
     *     description="删除注册促销(分销商)",
     *     operationId="deleteRegister",
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
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function deleteRegister($id, Request $request)
    {
        $distributorPromotionService = new DistributorPromotionService();
        $filter['register_type'] = 'distributor';
        $filter['id'] = $id;
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $result = $distributorPromotionService->deleteData($filter);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/distributor",
     *     summary="获取分销商(废弃)",
     *     tags={"营销"},
     *     description="获取分销商",
     *     operationId="getDistributorList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="id", required=false, type="string"),
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getDistributorList(Request $request)
    {
        //获取已经参加营销的分销商
        $distributorPromotionService = new DistributorPromotionService();
        $relfilter['company_id'] = $filter['company_id'] = app('auth')->user()->get('company_id');
        $relfilter['promotion_type'] = 'register';
        if ($id = $request->all('id')) {
            $relfilter['promotion_id|neq'] = $id;
        }
        $relData = $distributorPromotionService->lists($relfilter);
        if ($relData) {
            $filter['distributor_id|notIn'] = array_column($relData, 'distributor_id');
        }
        $filter['is_valid'] = 'true';

        //获取所有分销商
        $distributorService = new DistributorService();
        $distridutor = $distributorService->lists($filter);
        return $this->response->array($distridutor);
    }
}
