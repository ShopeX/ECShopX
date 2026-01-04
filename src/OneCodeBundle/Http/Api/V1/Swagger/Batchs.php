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

namespace OneCodeBundle\Http\Api\V1\Swagger;

/**
 * @SWG\Definition(type="object")
 */
class Batchs
{
    // ShopEx framework
    /**
     * @SWG\Property( property="batch_id", type="string", example="2", description="批次ID"),
     * @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     * @SWG\Property( property="thing_id", type="string", example="1", description="物品ID"),
     * @SWG\Property( property="batch_number", type="string", example="213333333121211", description="批次编号"),
     * @SWG\Property( property="batch_name", type="string", example="cefe", description="批次名称"),
     * @SWG\Property( property="batch_quantity", type="string", example="12", description="批次件数"),
     * @SWG\Property( property="show_trace", type="string", example="true", description="前台是否可以查看流通信息"),
     * @SWG\Property( property="trace_info", type="string", example="5445", description="流通信息(DC2Type:json_array)"),
     * @SWG\Property( property="created", type="string", example="1611820745", description=""),
     * @SWG\Property( property="updated", type="string", example="1611820745", description="修改时间"),
     */
}
