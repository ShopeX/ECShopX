<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DistributionBundle\Http\FrontApi\V1\Action;

use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorCategoryService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Swagger\Annotations as SWG;

class DistributorCategory extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/distributor/category/list",
     *     summary="获取店铺分类列表",
     *     tags={"店铺"},
     *     description="获取店铺分类列表",
     *     operationId="getFrontDistributorCategoryList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=false, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=false, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页长度", required=false, type="integer"),
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
        $authInfo = $request->get('auth');
        $companyId = (int)($authInfo['company_id'] ?? $request->get('company_id', 0));
        if ($companyId <= 0) {
            throw new ResourceException('公司ID不能为空');
        }

        $page = (int)$request->input('page', 1);
        $pageSize = (int)$request->input('pageSize', 20);
        $categoryName = $request->input('category_name', '');

        $filter = ['company_id' => $companyId];
        if (!empty($categoryName)) {
            $filter['category_name|contains'] = $categoryName;
        }

        $service = new DistributorCategoryService();
        $result = $service->lists($filter, $page, $pageSize);
        return $this->response->array($result);
    }
}
