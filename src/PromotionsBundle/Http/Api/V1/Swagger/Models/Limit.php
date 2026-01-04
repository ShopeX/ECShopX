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

namespace PromotionsBundle\Http\Api\V1\Swagger\Models;

/**
 * @SWG\Definition(
 *     definition="LimitBase",
 *     type="object",
 *     @SWG\Property( property="limit_id", type="string", example="82", description="限购活动id | 限购活动规则id"),
 *     @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
 *     @SWG\Property( property="limit_name", type="string", example="000", description="限购活动名称"),
 *     @SWG\Property( property="rule", type="string", example="", description="限购规则"),
 *     @SWG\Property( property="start_time", type="string", example="1611504000", description="开始时间"),
 *     @SWG\Property( property="end_time", type="string", example="1611676799", description="结束时间"),
 *     @SWG\Property( property="created", type="string", example="1611539524", description=""),
 *     @SWG\Property( property="updated", type="string", example="1611539524", description=""),
 *     @SWG\Property( property="valid_grade", type="string", example="4", description="会员级别集合数组"),
 *     @SWG\Property( property="use_bound", type="string", example="1", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用 | 适用范围: 1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
 *     @SWG\Property( property="tag_ids", type="string", example="", description="标签id集合数组"),
 *     @SWG\Property( property="brand_ids", type="string", example="", description="品牌id集合数组"),
 *     @SWG\Property( property="status", type="string", example="end", description="活动状态 waiting:未开始 ongoing:进行中 end:已结束"),
 * )
 */


/**
 * @SWG\Definition(
 *     definition="LimitDetail",
 *     type="object",
 *     @SWG\Property( property="limit_id", type="string", example="82", description="限购活动id | 限购活动规则id"),
 *     @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
 *     @SWG\Property( property="limit_name", type="string", example="000", description="限购活动名称"),
 *     @SWG\Property( property="rule", type="string", example="", description="限购规则"),
 *     @SWG\Property( property="start_time", type="string", example="1611504000", description="开始时间"),
 *     @SWG\Property( property="end_time", type="string", example="1611676799", description="结束时间"),
 *     @SWG\Property( property="created", type="string", example="1611539524", description=""),
 *     @SWG\Property( property="updated", type="string", example="1611539524", description=""),
 *     @SWG\Property( property="valid_grade", type="string", example="4", description="会员级别集合数组"),
 *     @SWG\Property( property="use_bound", type="string", example="1", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用 | 适用范围: 1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
 *     @SWG\Property( property="tag_ids", type="string", example="", description="标签id集合数组"),
 *     @SWG\Property( property="brand_ids", type="string", example="", description="品牌id集合数组"),
 *     @SWG\Property( property="status", type="string", example="end", description="活动状态 waiting:未开始 ongoing:进行中 end:已结束"),
 *     @SWG\Property(
 *         property="itemTreeLists",
 *         type="array",
 *         description="商品数据",
 *         @SWG\Items( type="object",
 *             ref="#definitions/ItemList",
 *         ),
 *     ),
 *     @SWG\Property(
 *         property="items",
 *         type="array",
 *         description="商品数据",
 *         @SWG\Items( type="object",
 *             ref="#definitions/ItemList",
 *         ),
 *     ),
 * )
 */

/**
 * @SWG\Definition(
 *     definition="LimitResult",
 *     type="object",
 *     @SWG\Property( property="limit_id", type="string", example="82", description="限购活动id"),
 *     @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
 *     @SWG\Property( property="limit_name", type="string", example="000", description="限购活动名称"),
 *     @SWG\Property( property="rule", type="string", example="", description="限购规则 包含字段:day,limit x天限购x件"),
 *     @SWG\Property( property="start_time", type="string", example="1611504000", description="开始时间"),
 *     @SWG\Property( property="end_time", type="string", example="1611676799", description="结束时间"),
 *     @SWG\Property( property="created", type="string", example="1611539524", description=""),
 *     @SWG\Property( property="updated", type="string", example="1611539524", description=""),
 *     @SWG\Property( property="valid_grade", type="string", example="4", description="会员级别集合数组"),
 *     @SWG\Property( property="use_bound", type="string", example="1", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用 | 适用范围: 1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
 *     @SWG\Property( property="tag_ids", type="string", example="", description="标签id集合数组"),
 *     @SWG\Property( property="brand_ids", type="string", example="", description="品牌id集合数组"),
 *     @SWG\Property( property="status", type="string", example="end", description="活动状态 waiting:未开始 ongoing:进行中 end:已结束"),
 *     @SWG\Property(
 *         property="items",
 *         type="array",
 *         description="关联商品数据",
 *         @SWG\Items( type="object",
 *             @SWG\Property( property="limit_id", type="string", example="1", description="限购活动id"),
 *             @SWG\Property( property="item_id", type="string", example="82", description="商品id"),
 *             @SWG\Property( property="item_name", type="string", example="商品名称", description="商品名称"),
 *             @SWG\Property( property="item_spec_desc", type="string", example="", description="商品规格描述"),
 *             @SWG\Property( property="pics", type="string", example="", description="商品图片"),
 *             @SWG\Property( property="price", type="string", example="100", description="活动价,单位为‘分’"),
 *             @SWG\Property( property="start_time", type="string", example="1612195200", description="活动开始时间"),
 *             @SWG\Property( property="end_time", type="string", example="1612195200", description="活动结束时间"),
 *
 *         ),
 *     ),
 * )
 */
