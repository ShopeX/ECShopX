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

/**
 * @SWG\Definition(
 *     definition="ResourceLevel",
 *     type="object",
 *     description="资源位详情",
 *     @SWG\Property( property="resourceLevelId", type="string", example="139", description="资源位自增id"),
 *     @SWG\Property( property="shopId", type="string", example="454", description="门店id"),
 *     @SWG\Property( property="shopName", type="string", example="门店名称", description="门店名称"),
 *     @SWG\Property( property="name", type="string", example="资源位名称", description="资源位名称"),
 *     @SWG\Property( property="description", type="string", example="资源位描述", description="资源位描述"),
 *     @SWG\Property( property="status", type="string", example="active", description="状态 active:有效，invalid: 失效"),
 *     @SWG\Property( property="imageUrl", type="string", example="", description="图片url"),
 *     @SWG\Property( property="quantity", type="string", example="1", description="数量"),
 *     @SWG\Property( property="created", type="string", example="1611041169", description="创建时间"),
 *     @SWG\Property( property="updated", type="string", example="null", description="更新时间"),
 *     @SWG\Property( property="materialIds", type="array",
 *     @SWG\Items(
 *         type="string", example="79", description="商品id"
 *         ),
 *     ),
 * )
 */