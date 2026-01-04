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

namespace EspierBundle\Commands;

use Illuminate\Console\Command;

class MakeAddressCommand extends Command
{
    /**
    * 命令行执行命令
    * @var string
    */
    protected $signature = 'make:address';


    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '生成地区数据(微信开发者工具中的region文件)';

    // 每个地名行政代码的key
    protected $idKey = 'value';
    // 每个地名的key
    protected $nameKey = 'label';
    // 每个地名的父类行政代码key
    protected $parentIdKey = 'parent_id';
    // 子节点的key
    protected $childrenKey = 'children';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * 微信小程序国家行政区域划分数据
     * 各个平台（高德地图、百度地图、腾讯地图、微信小程序）的地区划分都有所差异，对比后感觉微信小程序的数据最好用，比如东莞、海南的一些地区都做了划分；
     * 微信官方没有开放的数据，甚至腾讯地图的数据都是不一致的，所以整理了个脚本处理下数据。
     * region文件为小程序开发者工具提取，文件位置（macOS）
     * /Applications/wechatwebdevtools.app/Contents/Resources/package.nw/js/libs/region
     *
     * @return mixed
     */
    public function handle()
    {
        $raw = file_get_contents(storage_path('static/region')); // 微信开发者工具中的地区文件
        $raw = $this->convert_eol(trim($raw), "\n");
        $rows = explode("\n", $raw);

        // $list = [];

        $rootList = []; // 树状结构根结点列表
        $lastProvinceIndex = null;
        foreach ($rows as $row) {
            $id = $this->preg_search('/[0-9]{6,9}/', $row);
            $name = $this->preg_search('/[\x{4e00}-\x{9fa5}]+/u', $row);
            $item = [
                $this->idKey => $id,
                $this->nameKey => $name,
            ];
            // $list[] = $item;
            $lastNode = &$item;
            if ($this->is_province($id)) {
                // $item[$this->parentIdKey] = 0;
                $rootList[] = $item;
                continue;
            }
            $lastProvinceIndex = count($rootList) - 1;
            if ($this->is_city($id)) {
                // print_r($rootList[$lastProvinceIndex][$this->idKey]);exit;
                if (!isset($rootList[$lastProvinceIndex][$this->childrenKey])) {
                    $rootList[$lastProvinceIndex][$this->childrenKey] = [];
                }
                // $item[$this->parentIdKey] = $rootList[$lastProvinceIndex][$this->idKey];
                $rootList[$lastProvinceIndex][$this->childrenKey][] = $item;
                continue;
            }
            $lastCityIndex = count($rootList[$lastProvinceIndex][$this->childrenKey]) - 1;
            if ($this->is_county($id)) {
                if (!isset($rootList[$lastProvinceIndex][$this->childrenKey][$lastCityIndex][$this->childrenKey])) {
                    $rootList[$lastProvinceIndex][$this->childrenKey][$lastCityIndex][$this->childrenKey] = [];
                }
                // print_r($rootList[$lastProvinceIndex][$this->childrenKey][$lastCityIndex][$this->idKey]);exit;
                // $item[$this->parentIdKey] = $rootList[$lastProvinceIndex][$this->childrenKey][$lastCityIndex][$this->idKey];
                $rootList[$lastProvinceIndex][$this->childrenKey][$lastCityIndex][$this->childrenKey][] = $item;
            }
        }

        // $listJson = json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        // file_put_contents(storage_path('static/newregion.json'), $listJson);

        $treeListJson = json_encode($rootList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents(storage_path('static/district.json'), $treeListJson);

        echo "\n处理完成，输出文件在storage/static目录下\n";
    }

    private function convert_eol($string, $to = "\n")
    {
        return preg_replace("/\r\n|\r|\n/", $to, $string);
    }

    private function preg_search($pattern, $string)
    {
        preg_match($pattern, $string, $matches);
        if ($matches && count($matches)) {
            return $matches[0];
        }
        return false;
    }

    private function is_province($id)
    {
        return $this->preg_search('/^\d{2}0{4}$/', "$id") !== false;
    }

    private function is_city($id)
    {
        if ($this->is_province($id)) {
            return false;
        }
        return $this->preg_search('/^\d{4}0{2}$/', "$id") !== false;
    }

    private function is_county($id)
    {
        if ($this->is_province($id) || $this->is_city($id)) {
            return false;
        }
        return $this->preg_search('/^\d{6,9}$/', "$id") !== false;
    }
}
