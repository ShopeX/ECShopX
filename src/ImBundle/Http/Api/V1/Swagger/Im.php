<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ImBundle\Http\Api\V1\Swagger;

/**
 * @SWG\Definition(type="object")
 */
class Im
{
    /**
     *@SWG\Property(
     *    property="data",
     *    type="array",
     *    @SWG\Items(
     *        type="object",
     *        @SWG\Property(property="meiqia_url", type="boolean", example="false", description="美洽客服链接"),
     *        @SWG\Property(property="is_distributor_open", type="boolean", example="false", description="店铺客服状态"),
     *    )
     *)
     */
}
