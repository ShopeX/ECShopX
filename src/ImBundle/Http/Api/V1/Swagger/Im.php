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
