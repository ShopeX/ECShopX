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

namespace PopularizeBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use PopularizeBundle\Services\PromoterIdentityService;
use Dingo\Api\Exception\ResourceException;

/**
 * 推广员身份
 */
class PromoterIdentityController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/popularize/promoter/identity/list",
     *     summary="获取推广员身份列表",
     *     tags={"分销推广"},
     *     description="获取推广员身份列表",
     *     operationId="getPromoteridentityList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="页码", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *               @SWG\Property(property="total_count", type="integer", example="147", description="总记录条数"),
     *               @SWG\Property(property="list", type="array", description="数据列表",
     *                 @SWG\Items(
     *                           @SWG\Property(property="id", type="string", example="5", description="ID"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                           @SWG\Property(property="name", type="string", example="", description="推广员身份名称"),
     *                           @SWG\Property(property="is_subordinates", type="string", example="", description="是否可发展下级分销员"),
     *                           @SWG\Property(property="created", type="integer", example="1593669929", description="创建时间"),
     *                           @SWG\Property(property="updated", type="integer", example="1593669929", description="更新时间"),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function getPromoteridentityList(Request $request)
    {
        $service = new PromoterIdentityService();

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;

        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 20);

        $data = $service->lists($filter, '*', $page, $limit);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/popularize/promoter/identity/info",
     *     summary="获取推广员身份详情",
     *     tags={"分销推广"},
     *     description="获取推广员身份详情",
     *     operationId="getPromoteridentityInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="推广员身份id", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *               @SWG\Property(property="id", type="integer", example="1", description=""),
     *               @SWG\Property(property="company_id", type="integer", example="36", description=""),
     *               @SWG\Property(property="name", type="integer", example="2", description=""),
     *               @SWG\Property(property="is_subordinates", type="integer", example="1", description=""),
     *               @SWG\Property(property="created", type="integer", example="1598493367", description=""),
     *               @SWG\Property(property="updated", type="integer", example="1598493367", description=""),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function getPromoteridentityInfo(Request $request)
    {
        $params = $request->all('id');
        $rules = [
            'id' => ['required|integer|min:1','ID错误'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $service = new PromoterIdentityService();
        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;

        $data = $service->getInfo($params);
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/popularize/promoter/identity",
     *     summary="保存推广员身份",
     *     tags={"分销推广"},
     *     description="保存推广员身份",
     *     operationId="savePromoteridentity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="", required=false, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="推广员身份名称", required=true, type="string"),
     *     @SWG\Parameter( name="is_subordinates", in="query", description="是否可发展下级分销员", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function savePromoteridentity(Request $request)
    {
        $params = $request->all('id', 'name', 'is_subordinates');
        $rules = [
            'name' => ['required','推广员身份名称错误'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $service = new PromoterIdentityService();
        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;
        $data = $service->save($params);
        return $this->response->array(['status' => true]);
    }

    public function deletePromoteridentity(Request $request)
    {
        $params = $request->all('id');
        $rules = [
            'id' => ['required|integer|min:1','ID错误'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $companyId = app('auth')->user()->get('company_id');
        $service = new PromoterIdentityService();
        $service->deletePromoterIdentity($companyId, $params['id']);
        return $this->response->array(['status' => true]);
    }

    public function defaultPromoteridentity(Request $request)
    {
        $params = $request->all('id');
        $rules = [
            'id' => ['required|integer|min:1','ID错误'],
        ];

        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $companyId = app('auth')->user()->get('company_id');
        $service = new PromoterIdentityService();
        $service->defaultPromoterIdentity($companyId, $params['id']);
        return $this->response->array(['status' => true]);
    }
}
