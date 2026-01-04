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

namespace PointsmallBundle\Http\FrontApi\V1\Swagger\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Cur"))
 */
class Cur
{
    // IDX: 2367340174
    /**
     * @SWG\Property(format="int64", example="1")
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(description="币种", example="CNY")
     * @var string
     */
    public $currency;

    /**
     * @SWG\Property(description="币种名称", example="中国人民币"),
     * @var string
     */
    public $title;

    /**
     * @SWG\Property(description="币种符号", example="￥")
     * @var int
     */
    public $symbol;

    /**
     * @SWG\Property(description="币种税率", example="1")
     * @var string
     */
    public $rate;

    /**
     * @SWG\Property(description="是否默认", example=true)
     * @var boolean
     */
    public $is_default;

    /**
     * @SWG\Property(description="适用端。可选值为 service,normal", example="normal")
     * @var string
     */
    public $use_platform;
}
