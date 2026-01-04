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

namespace CompanysBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as BaseController;
use CompanysBundle\Services\OperatorCartService;
use CompanysBundle\Services\OperatorPendingOrderService;
use DistributionBundle\Services\DistributorService;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Entities\ItemsBarcode;

class OperatorCartController extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/operator/scancodeAddcart",
     *     summary="扫条形码加入购物车",
     *     tags={"企业"},
     *     description="扫条形码加入购物车",
     *     operationId="scanCodeSales",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="barcode", in="formData", description="条形码", required=true, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *                  @SWG\Property( property="msg", type="string", example="加入购物车成功", description="加购结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function scanCodeSales(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        // 获取条形码信息
        $barcode_ifilter['barcode'] = $request->get('barcode', 0);
        $barcode_ifilter['company_id'] = $authInfo['company_id'];
        $ItemsBarcode = app('registry')->getManager('default')->getRepository(ItemsBarcode::class);
        $barcode_info = $ItemsBarcode->getInfo($barcode_ifilter);
        if (!$barcode_info) {
            throw new ResourceException('商品找不到');
        }

        $ifilter['item_id'] = $barcode_info['item_id'];
        $ifilter['company_id'] = $authInfo['company_id'];
        $itemsService = new ItemsService();
        $tempItemInfo = $itemsService->getInfo($ifilter);
        if (!$tempItemInfo) {
            throw new ResourceException('商品找不到');
        }
        $filter['distributor_id'] = $request->get('distributor_id', 0);
        $filter['company_id'] = $authInfo['company_id'];
        $filter['item_id'] = $tempItemInfo['item_id'];
        $filter['operator_id'] = $authInfo['operator_id'];
        $params['num'] = 1;
        $params['is_checked'] = true;
        $isAccumulate = true;
        $operatorCartService = new OperatorCartService();
        $result = $operatorCartService->addCartdata($filter, $params, $isAccumulate);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/operator/cartdataadd",
     *     summary="管理员购物车新增",
     *     tags={"企业"},
     *     description="管理员购物车新增",
     *     operationId="cartDataAdd",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="item_id", in="formData", description="商品item_id", required=true, type="integer"),
     *     @SWG\Parameter( name="num", in="formData", description="商品数量", required=true, type="integer"),
     *     @SWG\Parameter( name="is_accumulate", in="formData", description="购物车数量更改方式，true:类增， false:覆盖", required=true, type="integer"),
     *     @SWG\Parameter( name="is_checked", in="formData", description="是否选中，true:是， false:否", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="cart_id", type="string", example="359", description="购物车ID"),
     *                  @SWG\Property( property="operator_id", type="string", example="45", description="管理员id"),
     *                  @SWG\Property( property="item_id", type="string", example="5471", description="商品id"),
     *                  @SWG\Property( property="package_items", type="string", example="", description="关联商品id集合"),
     *                  @SWG\Property( property="num", type="string", example="1", description="商品数量"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="企业id"),
     *                  @SWG\Property( property="is_checked", type="string", example="true", description="购物车是否选中"),
     *                  @SWG\Property( property="distributor_id", type="string", example="33", description="店铺id"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones")))
     * )
     */
    public function cartDataAdd(Request $request)
    {
        $authInfo = app('auth')->user()->get();

        $inputParams = $request->all('item_id', 'num', 'is_checked', 'is_accumulate');

        $filter['item_id'] = $request->get('item_id');
        $filter['operator_id'] = $authInfo['operator_id'];
        $filter['distributor_id'] = $request->get('distributor_id', 0);
        $filter['company_id'] = $authInfo['company_id'];
        $params['num'] = $request->get('num', 0);
        $params['is_checked'] = $request->get('is_checked', true);
        $isAccumulate = $request->get('is_accumulate', true);
        $operatorCartService = new OperatorCartService();
        $result = $operatorCartService->addCartdata($filter, $params, $isAccumulate);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/operator/cartdataupdate",
     *     summary="管理员购物车更新",
     *     tags={"企业"},
     *     description="管理员购物车更新",
     *     operationId="updateCartData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="cart_id", in="formData", description="购物车id", required=true, type="integer"),
     *     @SWG\Parameter( name="item_id", in="formData", description="商品item_id", required=true, type="integer"),
     *     @SWG\Parameter( name="num", in="formData", description="商品数量", required=true, type="integer"),
     *     @SWG\Parameter( name="is_checked", in="formData", description="是否选中", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="cart_id", type="string", example="359", description="购物车ID"),
     *                  @SWG\Property( property="operator_id", type="string", example="45", description="管理员id"),     *                  @SWG\Property( property="item_id", type="string", example="5471", description="商品id"),
     *                  @SWG\Property( property="package_items", type="string", example="", description="关联商品id集合"),
     *                  @SWG\Property( property="num", type="string", example="1", description="商品数量"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="企业id"),
     *                  @SWG\Property( property="is_checked", type="string", example="true", description="购物车是否选中"),
     *                  @SWG\Property( property="distributor_id", type="string", example="33", description="店铺id"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones")))
     * )
     */
    public function updateCartData(Request $request)
    {
        $authInfo = app('auth')->user()->get();

        $inputParams = $request->all('item_id', 'cart_id', 'num', 'is_checked');

        $filter['cart_id'] = $request->get('cart_id');
        $filter['item_id'] = $request->get('item_id');
        $filter['operator_id'] = $authInfo['operator_id'];
        $filter['distributor_id'] = $request->get('distributor_id', 0);
        $filter['company_id'] = $authInfo['company_id'];
        $params['num'] = $request->get('num');
        $params['is_checked'] = $request->get('is_checked', true);
        $operatorCartService = new OperatorCartService();
        $result = $operatorCartService->updateCartdata($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/operator/cartdatalist",
     *     summary="获取管理员购物车",
     *     tags={"企业"},
     *     description="获取管理员购物车",
     *     operationId="getCartDataList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="会员id", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object"),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones")))
     * )
     */
    public function getCartDataList(Request $request)
    {
        // XXX: review this code
        $authInfo = app('auth')->user()->get();

        $userId = $request->get('user_id', 0);
        $filter['operator_id'] = $authInfo['operator_id'];
        $filter['distributor_id'] = $request->get('distributor_id', 0);
        $filter['company_id'] = $authInfo['company_id'];
        $operatorCartService = new OperatorCartService();
        $cartData = $operatorCartService->getCartdataList($filter, $userId);
        return $this->response->array($cartData);
    }

    /**
     * @SWG\Delete(
     *     path="/operator/cartdatadel",
     *     summary="管理员购物车删除",
     *     tags={"企业"},
     *     description="管理员购物车删除",
     *     operationId="delCartData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="item_id", in="formData", description="商品item_id", required=false, type="integer"),
     *     @SWG\Parameter( name="cart_id", in="formData", description="购物车id", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="删除结果"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones")))
     * )
     */
    public function delCartData(Request $request)
    {
        $authInfo = app('auth')->user()->get();

        if ($cartId = $request->get('cart_id', 0)) {
            $filter['cart_id'] = $cartId;
        }
        if ($itemId = $request->get('item_id', 0)) {
            $filter['item_id'] = $itemId;
        }
        $filter['operator_id'] = $authInfo['operator_id'];
        // $filter['distributor_id'] = $request->get('distributor_id', 0);
        $filter['company_id'] = $authInfo['company_id'];
        $operatorCartService = new OperatorCartService();
        $result = $operatorCartService->deleteBy($filter);
        return $this->response->array(['status' => $result]);
    }
}
