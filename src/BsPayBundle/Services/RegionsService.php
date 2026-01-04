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

namespace BsPayBundle\Services;

use Dingo\Api\Exception\ResourceException;

use BsPayBundle\Entities\RegionsThird;

class RegionsService
{
    private $dataSource = 'https://cloudpnrcdn.oss-cn-shanghai.aliyuncs.com/opps/api/prod/download_file/area/%E7%9C%81%E5%B8%82%E5%8C%BA%E7%BC%96%E7%A0%81.xlsx';
    private $localPath = 'bspay/bspay_regions_local.xlsx';
    public $regionsThirdRepository;

    public function __construct()
    {
        $this->regionsThirdRepository = app('registry')->getManager('default')->getRepository(RegionsThird::class);
    }

    //将省市数据读取到本地数据库
    public function genData()
    {
        $dataPath = storage_path($this->localPath);
        if (!is_file($dataPath)) {
            $data = file_get_contents($this->dataSource);
            file_put_contents($dataPath, $data);
        }
        $count = 0;

        $results = app('excel')->toArray(new \stdClass(), $dataPath);
        $results = $results[0];

        if (!$results) {
            return false;
        }

        array_shift($results);//移出表头
        foreach ($results as $v) {
            $filter = [
                'area_name' => $v[0],
                'area_code' => $v[1],
                'pid' => 0,
            ];
            $rs = $this->regionsThirdRepository->getInfo($filter);
            if (!$rs) {
                $rs = $this->regionsThirdRepository->create($filter);
                $count++;
            }
            $pid = $rs['id'];

            // 二级
            $filter = [
                'area_name' => $v[2],
                'area_code' => $v[3],
                'pid' => $pid,
            ];
            $rs = $this->regionsThirdRepository->getInfo($filter);
            if (!$rs) {
                $rs = $this->regionsThirdRepository->create($filter);
                $count++;
            }
            // 处理三级为空的情况
            if (empty($v[4])) {
                continue;
            }
            // 三级
            $pid = $rs['id'];
            $filter = [
                'area_name' => $v[4],
                'area_code' => $v[5],
                'pid' => $pid,
            ];
            $rs = $this->regionsThirdRepository->getInfo($filter);
            if (!$rs) {
                $rs = $this->regionsThirdRepository->create($filter);
                $count++;
            }
        }

        echo("写入 $count 条地区数据(斗拱 二级code)");
    }

    public function getAreaName($areaCode)
    {
        $info = $this->getInfo(['area_code' => $areaCode]);
        return $info['area_name'] ?? '';
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->regionsThirdRepository->$method(...$parameters);
    }
}
