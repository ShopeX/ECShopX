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

namespace MembersBundle\Http\AdminApi\V1\Swagger;

/**
 * @SWG\Definition(type="object")
 */
class FrequentCategory
{
    // ShopEx EcShopX Service Component
    /**
     *                          @SWG\Property( property="id", type="string", example="1718", description=""),
     *                          @SWG\Property( property="category_id", type="string", example="1718", description="分类id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="category_name", type="string", example="套装", description="类目名称"),
     *                          @SWG\Property( property="label", type="string", example="套装", description="地区名称"),
     *                          @SWG\Property( property="parent_id", type="string", example="1713", description="父级id, 0为顶级"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="店铺ID"),
     *                          @SWG\Property( property="path", type="string", example="1712,1713,1718", description="路径"),
     *                          @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                          @SWG\Property( property="is_main_category", type="string", example="true", description="是否为商品主类目"),
     *                          @SWG\Property( property="goods_params", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="goods_spec", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="category_level", type="string", example="3", description="分类等级"),
     *                          @SWG\Property( property="image_url", type="string", example="", description="图片"),
     *                          @SWG\Property( property="crossborder_tax_rate", type="string", example="null", description="跨境税率，百分比，小数点2位"),
     *                          @SWG\Property( property="created", type="string", example="1607417719", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1609001916", description="修改时间"),
     *                          @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *                          @SWG\Property( property="buy_num", type="string", example="1", description=""),
     */
}
