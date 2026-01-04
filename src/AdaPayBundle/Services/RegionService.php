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

namespace AdaPayBundle\Services;

use AdaPayBundle\Entities\AdapayRegionsThird;
use Dingo\Api\Exception\ResourceException;

use AdaPayBundle\Entities\AdapayRegions;

class RegionService
{
    private $dataSource = 'https://cdn.cloudpnr.com/adapayresource/documents/Adapay%E7%9C%81%E5%B8%82%E7%BC%96%E7%A0%81%EF%BC%88%E5%9B%9B%E4%BD%8D%EF%BC%89.json';
    private $dataSourceThird = 'https://cdn.cloudpnr.com/adapayresource/documents/Adapay%E7%9C%81%E5%B8%82%E5%8C%BA%E7%BC%96%E7%A0%81%EF%BC%88%E5%85%AD%E4%BD%8D%EF%BC%89.json';
    private $localPathSecond = 'adapay/region_local.json';
    private $localPathThird = 'adapay/region_third_local.json';
    public $adapayRegionsRepository;
    public $adapayRegionsThirdRepository;

    public function __construct()
    {
        $this->adapayRegionsRepository = app('registry')->getManager('default')->getRepository(AdapayRegions::class);
        $this->adapayRegionsThirdRepository = app('registry')->getManager('default')->getRepository(AdapayRegionsThird::class);
    }

    public function getData($isUseLocal = true)
    {
        if ($isUseLocal) {
            $regionData = file_get_contents(storage_path($this->localPathSecond));
        } else {
            $regionData = file_get_contents($this->dataSource);
        }
        $regionData = json_decode($regionData, true);
        $count = 0;

        if (!$regionData) {
            return false;
        }

        foreach ($regionData as $v) {
            $filter = [
                'area_code' => $v['value'],
                'area_name' => $v['title'],
                'pid' => 0,
            ];
            $rs = $this->getInfo($filter);
            if (!$rs) {
                $rs = $this->create($filter);
                $count++;
            }
            $pid = $rs['id'];

            if (!isset($v['cities'])) {
                continue;
            }
            foreach ($v['cities'] as $city) {
                $filter = [
                    'area_code' => $city['value'],
                    'area_name' => $city['title'],
                    'pid' => $pid,
                ];
                if ($this->count($filter) == 0) {
                    $this->create($filter);
                    $count++;
                }
            }
        }

        echo("写入 $count 条地区数据(adapay 二级code)");
    }

    public function getDataThird($isUseLocal = true)
    {
        if ($isUseLocal) {
            $regionData = file_get_contents(storage_path($this->localPathThird));
        } else {
            $regionData = file_get_contents($this->dataSourceThird);
        }
        $count = 0;
        $regionData = preg_replace('/var(.+?)=\s/', '', $regionData);

        $regionData = json_decode(trim($regionData, chr(239).chr(187).chr(191)), true);
        if (!$regionData) {
            return false;
        }

        foreach ($regionData as $key => $value) {
            $data = [
                'area_name' => $key,
                'pid' => 0,
                'area_code' => $value['val']
            ];
            $level1 = $this->adapayRegionsThirdRepository->create($data);
            $count++;
            if ($value['items']) {
                foreach ($value['items'] as $k => $v) {
                    $data = [
                        'area_name' => $k,
                        'pid' => $level1['id'],
                        'area_code' => $v['val']
                    ];
                    $level2 = $this->adapayRegionsThirdRepository->create($data);
                    $count++;
                    if ($v['items']) {
                        foreach ($v['items'] as $thirdKey => $third) {
                            $data = [
                                'area_name' => $thirdKey,
                                'pid' => $level2['id'],
                                'area_code' => $third
                            ];
                            $this->adapayRegionsThirdRepository->create($data);
                            $count++;
                        }
                    }
                }
            }
        }
        echo("写入 $count 条地区数据(adapay 三级code)");
    }

    public function getRegionsThirdListsService($pid)
    {
        return $this->adapayRegionsThirdRepository->getLists(['pid' => $pid]);
    }

    public function getAreaName($area_code)
    {
        $info = $this->getInfo(['area_code' => $area_code]);
        if (!$info) {
            throw new ResourceException("地区编号不存在");
        }
        return $info['area_name'];
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->adapayRegionsRepository->$method(...$parameters);
    }
}
