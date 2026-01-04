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

namespace EmployeePurchaseBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as BaseController;
use EmployeePurchaseBundle\Services\CartService;

class Cart extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/wxapp/employeepurchase/cart",
     *     summary="内购购物车新增",
     *     tags={"内购"},
     *     description="内购购物车新增",
     *     operationId="cartDataAdd",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="enterprise_id", in="formData", description="企业ID", required=true, type="integer"),
     *     @SWG\Parameter( name="activity_id", in="formData", description="活动ID", required=true, type="integer"),
     *     @SWG\Parameter( name="item_id", in="formData", description="商品ID", required=true, type="integer"),
     *     @SWG\Parameter( name="num", in="formData", description="商品数量", required=true, type="integer"),
     *     @SWG\Parameter( name="is_accumulate", in="formData", description="购物车数量更改方式，true:类增， false:覆盖", required=true, type="integer"),
     *     @SWG\Parameter( name="is_checked", in="formData", description="是否选中，true:是， false:否", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="cart_id", type="string", example="359", description="购物车ID"),
     *                 @SWG\Property( property="company_id", type="string", example="45", description="公司ID"),
     *                 @SWG\Property( property="enterprise_id", type="string", example="45", description="企业ID"),
     *                 @SWG\Property( property="activity_id", type="string", example="45", description="活动ID"),
     *                 @SWG\Property( property="user_id", type="string", example="45", description="用户ID"),
     *                 @SWG\Property( property="item_id", type="string", example="5471", description="商品ID"),
     *                 @SWG\Property( property="num", type="string", example="1", description="商品数量"),
     *                 @SWG\Property( property="is_checked", type="string", example="true", description="购物车是否选中"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones")))
     * )
     */
    public function cartDataAdd(Request $request)
    {
        $authInfo = $request->get('auth');
        $distributorId = $request->get('distributor_id', 0);
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];
        $filter['enterprise_id'] = $request->get('enterprise_id');
        $filter['activity_id'] = $request->get('activity_id');
        $filter['item_id'] = $request->get('item_id');
        $filter['shop_id'] = $distributorId;
        $params['cart_type'] = $request->get('cart_type', 'cart');
        $params['num'] = $request->get('num', 0);
        $params['is_checked'] = $request->get('is_checked', true);
        $params['shop_type'] = $request->get('shop_type', 'distributor');
        $params['shop_id'] = $distributorId;
        $isAccumulate = $request->get('is_accumulate', true);
        $cartService = new CartService();
        $result = $cartService->addCartdata($filter, $params, $isAccumulate);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/wxapp/employeepurchase/cart",
     *     summary="内购购物车更新",
     *     tags={"内购"},
     *     description="内购购物车更新",
     *     operationId="updateCartData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="cart_id", in="formData", description="购物车id", required=true, type="integer"),
     *     @SWG\Parameter( name="enterprise_id", in="formData", description="企业ID", required=true, type="integer"),
     *     @SWG\Parameter( name="activity_id", in="formData", description="活动ID", required=true, type="integer"),
     *     @SWG\Parameter( name="item_id", in="formData", description="商品ID", required=true, type="integer"),
     *     @SWG\Parameter( name="num", in="formData", description="商品数量", required=true, type="integer"),
     *     @SWG\Parameter( name="is_accumulate", in="formData", description="购物车数量更改方式，true:类增， false:覆盖", required=true, type="integer"),
     *     @SWG\Parameter( name="is_checked", in="formData", description="是否选中，true:是， false:否", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="cart_id", type="string", example="359", description="购物车ID"),
     *                 @SWG\Property( property="company_id", type="string", example="45", description="公司ID"),
     *                 @SWG\Property( property="enterprise_id", type="string", example="45", description="企业ID"),
     *                 @SWG\Property( property="activity_id", type="string", example="45", description="活动ID"),
     *                 @SWG\Property( property="user_id", type="string", example="45", description="用户ID"),
     *                 @SWG\Property( property="item_id", type="string", example="5471", description="商品ID"),
     *                 @SWG\Property( property="num", type="string", example="1", description="商品数量"),
     *                 @SWG\Property( property="is_checked", type="string", example="true", description="购物车是否选中"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones")))
     * )
     */
    public function updateCartData(Request $request)
    {
        $authInfo = $request->get('auth');

        if ($cartId = $request->get('cart_id', 0)) {
            $filter['cart_id'] = $cartId;
        }
        if ($itemId = $request->get('item_id', 0)) {
            $filter['item_id'] = $itemId;
        }
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];
        $filter['enterprise_id'] = $request->get('enterprise_id');
        $filter['activity_id'] = $request->get('activity_id');
        $params['num'] = $request->get('num', 0);
        $params['is_checked'] = $request->get('is_checked', true);

        $cartService = new CartService();
        $result = $cartService->updateCartdata($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/wxapp/employeepurchase/cart/checkstatus",
     *     summary="修改内购购物车选中状态",
     *     tags={"内购"},
     *     description="修改内购购物车选中状态",
     *     operationId="updateCartCheckStatus",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="enterprise_id", in="query", description="企业ID", required=true, type="integer"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=true, type="integer"),
     *     @SWG\Parameter( name="cart_id", in="query", description="购物车ID", required=true, type="string"),
     *     @SWG\Parameter( name="is_checked", in="query", description="是否选中", required=true, type="string"),
     *     @SWG\Response(response=200, description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="status", type="string", example="true", description="更新结果"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function updateCartCheckStatus(Request $request)
    {
        $authInfo = $request->get('auth');

        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];
        $filter['enterprise_id'] = $request->get('enterprise_id');
        $filter['activity_id'] = $request->get('activity_id');
        $filter['cart_id'] = $request->get('cart_id');

        $rules = [
            'cart_id' => ['required', '购物车ID必填'],
            'enterprise_id' => ['required', '企业ID必填'],
            'activity_id' => ['required', '活动ID必填'],
        ];
        $error = validator_params($filter, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        if (!$request->input('is_checked') || $request->input('is_checked') === 'false') {
            $params['is_checked'] = 0;
        } else {
            $params['is_checked'] = 1;
        }
        $cartService = new CartService();
        $result = $cartService->updateBy($filter, $params);
    
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/employeepurchase/cartcount",
     *     summary="内购购物车商品数量",
     *     tags={"内购"},
     *     description="内购购物车商品数量",
     *     operationId="getCartItemCount",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="enterprise_id", in="query", description="企业ID", required=true, type="integer"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=true, type="integer"),
     *     @SWG\Response(
     *       response=200,
     *       description="",
     *       @SWG\Schema(
     *         @SWG\Property( property="data", type="object",
     *           @SWG\Property(property="cart_count", description="购物车数量", type="integer"),
     *           @SWG\Property(property="item_count", description="商品数量", type="integer"),
     *         )
     *       )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getCartItemCount(Request $request)
    {
        $authInfo = $request->get('auth');

        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];
        $filter['enterprise_id'] = $request->get('enterprise_id');
        $filter['activity_id'] = $request->get('activity_id');

        $rules = [
            'enterprise_id' => ['required', '企业ID必填'],
            'activity_id' => ['required', '活动ID必填'],
        ];
        $error = validator_params($filter, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        
        $cartService = new CartService();
        $result = $cartService->countCart($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/employeepurchase/cart",
     *     summary="获取内购购物车",
     *     tags={"内购"},
     *     description="获取内购购物车",
     *     operationId="getCartDataList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="enterprise_id", in="query", description="企业ID", required=true, type="integer"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object"),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones")))
     * )
     */
    public function getCartDataList(Request $request)
    {
        $authInfo = $request->get('auth');

        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];
        $filter['enterprise_id'] = $request->get('enterprise_id');
        $filter['activity_id'] = $request->get('activity_id');

        $rules = [
            'enterprise_id' => ['required', '企业ID必填'],
            'activity_id' => ['required', '活动ID必填'],
        ];
        $error = validator_params($filter, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        
        $cartService = new CartService();
        $cartData = $cartService->getCartdataList($filter);
        return $this->response->array($cartData);
    }

    /**
     * @SWG\Delete(
     *     path="/wxapp/employeepurchase/cart",
     *     summary="内购购物车删除",
     *     tags={"内购"},
     *     description="内购购物车删除",
     *     operationId="delCartData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="enterprise_id", in="query", description="企业ID", required=true, type="integer"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=true, type="integer"),
     *     @SWG\Parameter( name="item_id", in="query", description="商品item_id", required=false, type="integer"),
     *     @SWG\Parameter( name="cart_id", in="query", description="购物车id", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 @SWG\Property( property="status", type="string", example="true", description="删除结果"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones")))
     * )
     */
    public function delCartData(Request $request)
    {
        $authInfo = $request->get('auth');

        if ($cartId = $request->get('cart_id', 0)) {
            $filter['cart_id'] = $cartId;
        }
        if ($itemId = $request->get('item_id', 0)) {
            $filter['item_id'] = $itemId;
        }
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];
        $filter['enterprise_id'] = $request->get('enterprise_id');
        $filter['activity_id'] = $request->get('activity_id');

        $rules = [
            'enterprise_id' => ['required', '企业ID必填'],
            'activity_id' => ['required', '活动ID必填'],
        ];
        $error = validator_params($filter, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $cartService = new CartService();
        $result = $cartService->deleteBy($filter);
        return $this->response->array(['status' => $result]);
    }
}
