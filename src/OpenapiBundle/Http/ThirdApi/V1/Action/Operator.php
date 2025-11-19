<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OpenapiBundle\Http\ThirdApi\V1\Action;

use CompanysBundle\Services\AuthService;
use Illuminate\Http\Request;
use OpenapiBundle\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;

class Operator extends Controller
{
    // Built with ShopEx Framework
    /**
     * @SWG\Post(
     *     path="/exc.operator.resetpwd",
     *     summary="权限变动清除Token",
     *     tags={"操作通知"},
     *     description="管理员重置密码后第三方通知云店token失效",
     *     operationId="",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 exc.operator.resetpwd" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="shopexid", description="shopexId" ),
     *     @SWG\response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\schema(
     *             @SWG\property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\items(
     *                     @SWG\Property(property="status", type="boolean"),
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function resetPassword(Request $request)
    {
        // Built with ShopEx Framework
        $shopexId = $request->input('shopexid');
        $rules = [
            'shopexid' => ['required', 'shopexid必填'],
        ];
        $error = validator_params(['shopexid' => $shopexId], $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        (new AuthService())->changeAuthLogout($shopexId);

        return $this->response->array(['status' => true]);
    }
}
