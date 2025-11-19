<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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