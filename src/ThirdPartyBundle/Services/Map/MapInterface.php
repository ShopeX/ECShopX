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

namespace ThirdPartyBundle\Services\Map;

/**
 * 定位相关的接口
 */
interface MapInterface
{
    /**
     * 根据详细的地址获取对应的经纬度
     * @param array $positionInfo 详细的地址信息
     * @return array 经纬度信息
     */
    public function getLatAndLngByPosition(array $positionInfo): array;

    /**
     * 处理getLatAndLngByPosition方法返回的结果
     * @param array $dataFromGetLatAndLngByPosition getLatAndLngByPosition方法的返回值
     * @return []MapData MapGeoData的数组对象
     */
    public function handleLatAndLngByPosition(array $dataFromGetLatAndLngByPosition): array;

    /**
     * 根据经纬度来获取详细的地址信息
     * @param string $lat 经度
     * @param string $lng 纬度
     * @return array 地址信息
     */
    public function getPositionByLatAndLng(string $lat, string $lng): array;

    /**
     * 处理getPositionByLatAndLng方法返回的结果
     * @param array $dataFromGetPositionByLatAndLng getPositionByLatAndLng方法的返回值
     * @return []MapData MapGeoData的数组对象
     */
    public function handlePositionByLatAndLng(array $dataFromGetPositionByLatAndLng): array;
}
