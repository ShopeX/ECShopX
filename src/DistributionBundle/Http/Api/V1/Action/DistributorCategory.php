<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DistributionBundle\Http\Api\V1\Action;

use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorCategoryService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Swagger\Annotations as SWG;

class DistributorCategory extends Controller
{
    /**
     * @SWG\Post(
     *     path="/distributor/category",
     *     summary="新增店铺分类",
     *     tags={"店铺"},
     *     description="新增店铺分类",
     *     operationId="createDistributorCategory",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="category_name", in="query", description="店铺分类名称", required=true, type="string"),
     *     @SWG\Parameter( name="category_code", in="query", description="分类编号（可选，创建时系统自动生成）", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="category_id", type="integer", example="1", description="分类ID"),
     *              @SWG\Property( property="company_id", type="integer", example="1", description="公司ID"),
     *              @SWG\Property( property="category_name", type="string", example="旗舰店", description="店铺分类名称"),
     *              @SWG\Property( property="category_code", type="string", example="A001", description="分类编号"),
     *              @SWG\Property( property="created", type="string", example="1712143771", description="创建时间"),
     *              @SWG\Property( property="updated", type="string", example="1712143771", description="更新时间")
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function createCategory(Request $request)
    {
        $params = $request->all('category_name', 'category_code');
        $rules = [
            'category_name' => ['required', '请填写店铺分类名称'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;
        // 创建时系统生成8位大写编号
        $params['category_code'] = strtoupper(bin2hex(random_bytes(4)));

        $service = new DistributorCategoryService();
        $result = $service->create($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/distributor/category/{categoryId}",
     *     summary="更新店铺分类",
     *     tags={"店铺"},
     *     description="更新店铺分类",
     *     operationId="updateDistributorCategory",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="categoryId", in="path", description="分类ID", required=true, type="integer"),
     *     @SWG\Parameter( name="category_name", in="query", description="店铺分类名称", required=true, type="string"),
     *     @SWG\Parameter( name="category_code", in="query", description="分类编号", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="category_id", type="integer", example="1", description="分类ID"),
     *              @SWG\Property( property="company_id", type="integer", example="1", description="公司ID"),
     *              @SWG\Property( property="category_name", type="string", example="旗舰店", description="店铺分类名称"),
     *              @SWG\Property( property="category_code", type="string", example="A001", description="分类编号"),
     *              @SWG\Property( property="created", type="string", example="1712143771", description="创建时间"),
     *              @SWG\Property( property="updated", type="string", example="1712143771", description="更新时间")
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function updateCategory($categoryId, Request $request)
    {
        $params = $request->all('category_name', 'category_code');
        $rules = [
            'category_name' => ['required', '请填写店铺分类名称'],
            'category_code' => ['required', '请填写分类编号'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter = [
            'category_id' => $categoryId,
            'company_id' => $companyId,
        ];

        $service = new DistributorCategoryService();
        $result = $service->updateOneBy($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/distributor/category/{categoryId}",
     *     summary="删除店铺分类",
     *     tags={"店铺"},
     *     description="删除店铺分类",
     *     operationId="deleteDistributorCategory",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="categoryId", in="path", description="分类ID", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="status", type="boolean", example=true, description="操作状态")
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function deleteCategory($categoryId)
    {
        $companyId = app('auth')->user()->get('company_id');
        $service = new DistributorCategoryService();
        $info = $service->getInfo([
            'category_id' => $categoryId,
            'company_id' => $companyId,
        ]);
        if (empty($info)) {
            throw new ResourceException('分类不存在');
        }
        $service->deleteById((int)$categoryId);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/distributor/category",
     *     summary="获取店铺分类列表",
     *     tags={"店铺"},
     *     description="获取店铺分类列表",
     *     operationId="getDistributorCategoryList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页长度", required=true, type="integer"),
     *     @SWG\Parameter( name="category_name", in="query", description="店铺分类名称", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="total_count", type="string", example="2", description="总数"),
     *              @SWG\Property( property="list", type="array",
     *                  @SWG\Items( type="object",
     *                      @SWG\Property( property="category_id", type="integer", example="1", description="分类ID"),
     *                      @SWG\Property( property="company_id", type="integer", example="1", description="公司ID"),
     *                      @SWG\Property( property="category_name", type="string", example="旗舰店", description="店铺分类名称"),
     *                      @SWG\Property( property="category_code", type="string", example="A001", description="分类编号"),
     *                      @SWG\Property( property="created", type="string", example="1712143771", description="创建时间"),
     *                      @SWG\Property( property="updated", type="string", example="1712143771", description="更新时间")
     *                  ),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getCategoryList(Request $request)
    {
        $params = $request->all('page', 'pageSize', 'category_name');
        $rules = [
            'page' => ['required', '请填写页码'],
            'pageSize' => ['required', '请填写每页长度'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $page = $params['page'] ? intval($params['page']) : 1;
        $pageSize = $params['pageSize'] ? intval($params['pageSize']) : 20;

        $companyId = app('auth')->user()->get('company_id');
        $filter = ['company_id' => $companyId];
        if (!empty($params['category_name'])) {
            $filter['category_name|contains'] = $params['category_name'];
        }

        $service = new DistributorCategoryService();
        $result = $service->lists($filter, $page, $pageSize);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/distributor/category/{categoryId}",
     *     summary="获取店铺分类详情",
     *     tags={"店铺"},
     *     description="获取店铺分类详情",
     *     operationId="getDistributorCategoryInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="categoryId", in="path", description="分类ID", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="category_id", type="integer", example="1", description="分类ID"),
     *              @SWG\Property( property="company_id", type="integer", example="1", description="公司ID"),
     *              @SWG\Property( property="category_name", type="string", example="旗舰店", description="店铺分类名称"),
     *              @SWG\Property( property="category_code", type="string", example="A001", description="分类编号"),
     *              @SWG\Property( property="created", type="string", example="1712143771", description="创建时间"),
     *              @SWG\Property( property="updated", type="string", example="1712143771", description="更新时间")
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getCategoryInfo($categoryId)
    {
        $companyId = app('auth')->user()->get('company_id');
        $service = new DistributorCategoryService();
        $result = $service->getInfo([
            'category_id' => $categoryId,
            'company_id' => $companyId,
        ]);
        return $this->response->array($result);
    }
}
