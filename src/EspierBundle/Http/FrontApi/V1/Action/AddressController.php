<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Http\FrontApi\V1\Action;

use EspierBundle\Services\AddressService;
use App\Http\Controllers\Controller as Controller;

class AddressController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/espier/address",
     *     summary="获取地址",
     *     tags={"系统"},
     *     description="获取地址",
     *     operationId="getAddress",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *      @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="id", type="string", example="110000", description=""),
     *                  @SWG\Property( property="value", type="string", example="110000", description="编码"),
     *                  @SWG\Property( property="label", type="string", example="北京市", description="地址"),
     *                  @SWG\Property( property="parent_id", type="string", example="0", description="父级id"),
     *                  @SWG\Property( property="path", type="string", example="110000", description="路径"),
     *                  @SWG\Property( property="children", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="110001", description="自行更改字段描述"),
     *                          @SWG\Property( property="value", type="string", example="110001", description="自行更改字段描述"),
     *                          @SWG\Property( property="label", type="string", example="北京市", description="地区名称"),
     *                          @SWG\Property( property="parent_id", type="string", example="110000", description="父级id, 0为顶级 | 父级id | 父分类id,顶级为0"),
     *                          @SWG\Property( property="path", type="string", example="110000,110001", description="路径"),
     *                          @SWG\Property( property="children", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="id", type="string", example="110101", description="自行更改字段描述"),
     *                                  @SWG\Property( property="value", type="string", example="110101", description="自行更改字段描述"),
     *                                  @SWG\Property( property="label", type="string", example="东城", description="地区名称"),
     *                                  @SWG\Property( property="parent_id", type="string", example="110001", description="父级id, 0为顶级 | 父级id | 父分类id,顶级为0"),
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
        $addressService = new AddressService();
        $address = $addressService->getAddressInfo();
        return $this->response->array($address);
    }
}
