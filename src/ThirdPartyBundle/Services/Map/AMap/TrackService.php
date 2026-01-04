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
