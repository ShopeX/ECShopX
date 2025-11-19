<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Http\Api\V1\Action;

use EspierBundle\Services\AddressService;
use App\Http\Controllers\Controller as Controller;

class AddressController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/espier/address",
     *     summary="获取地址",
     *     tags={"系统"},
     *     description="获取地址",
     *     operationId="get",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="id", type="string", example="110000", description=""),
     *                  @SWG\Property( property="value", type="string", example="110000", description=""),
     *                  @SWG\Property( property="label", type="string", example="北京市", description="地区名称"),
     *                  @SWG\Property( property="parent_id", type="string", example="0", description="父级id, 0为顶级0"),
     *                  @SWG\Property( property="path", type="string", example="110000", description="路径"),
     *                  @SWG\Property( property="children", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="110001", description=""),
     *                          @SWG\Property( property="value", type="string", example="110001", description=""),
     *                          @SWG\Property( property="label", type="string", example="北京市", description="地区名称"),
     *                          @SWG\Property( property="parent_id", type="string", example="110000", description="父级id, 0为顶级"),
     *                          @SWG\Property( property="path", type="string", example="110000,110001", description="路径"),
     *                          @SWG\Property( property="children", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="id", type="string", example="110101", description=""),
     *                                  @SWG\Property( property="value", type="string", example="110101", description=""),
     *                                  @SWG\Property( property="label", type="string", example="东城", description="地区名称"),
     *                                  @SWG\Property( property="parent_id", type="string", example="110001", description="父级id, 0为顶级"),
     *                                  @SWG\Property( property="path", type="string", example="110000,110001,110101", description="路径"),
     *                               ),
     *                          ),
     *                       ),
     *                  ),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function get()
    {
        // Ref: 1996368445
        $addressService = new AddressService();
        $address = $addressService->getAddressInfo();
        return $this->response->array($address);
    }
}
