<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\Map\AMap;

/**
 * 高德地图的猎鹰轨迹
 * 参考文档：https://lbs.amap.com/api/track/lieying-rumen
 */
class TrackService
{
    /**
     * Key   ->   商家
     * Service  ->  服务类型（1个service最多有10W个Terminal）
     * Terminal ->  店铺（1个Terminal最多有50W条Trace）
     * Trace    ->  这个店铺下的轨迹
     */

    /**
     * 个人开发者   ->   5000次/日   30次/秒（QPS）
     * 企业开发者   ->   30000次/日  50次/秒（QPS）
     */
}
