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

namespace SystemLinkBundle\Jobs;

use EspierBundle\Jobs\Job;
use SystemLinkBundle\Services\ShopexErp\OpenApi\Request;
use GoodsBundle\Services\ItemsCategoryService;
use SystemLinkBundle\Services\ThirdSettingService;

class GetItemsCategoryFromOme extends Job
{
    public $companyId = '';
    public $distributorId = 0;
    /**
     * 拉取oms商品规格
     *
     * @return void
     */
    public function __construct($companyId)
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        $this->companyId = $companyId;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        // 判断是否开启OME
        $service = new ThirdSettingService();
        $data = $service->getShopexErpSetting($this->companyId);
        if (!isset($data) || ($data['is_openapi_open'] ?? false) == false) {
            app('log')->debug('companyId:'.$this->companyId.",msg:未开启OME开放数据接口");
            return true;
        }

        $result = [];
        try {
            $data = [
                // 'page_no' => 1,
            ];
            $omeRequest = new Request($this->companyId);
            $method = 'category.getList';
            $result = $omeRequest->call($method, $data);
            if (!isset($result['rsp']) || $result['rsp'] != 'succ') {
                app('log')->debug('companyId:'.$this->companyId.",msg:OME批量获取商品分类信息请求失败");
                return true;
            }
            if ($result['data']['count'] > 0) {
                $list = $result['data']['lists'];
                $this->saveCategories($list);
            }
            app('log')->debug($method.'=>requestData:'. json_encode($data)."==>result:\r\n".var_export($result, 1));
        } catch (\Exception $e) {
            app('log')->debug('OME请求失败:'. $e->getMessage().'=>method:'.$method.'=>requestData:'.json_encode($data)."=>result:". json_encode($result));
        }
        return true;
    }
    public function saveCategories($data)
    {
        $itemsCategoryService = new ItemsCategoryService();
        $lists = $this->getTree($data, '', 0);
        return $itemsCategoryService->saveItemsCategory($lists, $this->companyId, $this->distributorId);
    }
    //todo 这个递归还可以改进效率
    /**
     * 递归实现无限极分类
     * @param $array 分类数据
     * @param $pid 父ID
     * @param $level 分类级别
     * @return $list 分好类的数组 直接遍历即可 $level可以用来遍历缩进
     */

    public function getTree($array, $pid = '', $level = 0)
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        $list = [];
        foreach ($array as $k => $v) {
            if ($v['parent_code'] == $pid) {
                $v['children'] = [];

                $tmp = [
                    'category_name' => $v['cat_name'],
                    'category_code' => $v['cat_code'],
                    'is_main_category' => true,
                    'category_level' => $level,
                    'sort' => 0,
                    'goods_params' => [],
                    'goods_spec' => [],
                    'image_url' => ''
                ];
                //云店只接收到三级类目
                if (count(explode(',', $v['cat_code_path'])) < 3) {
                    $tmp['children'] = $this->getTree($array, $v['cat_code'], $level + 1);
                }
                $list[] = $tmp;
            }
        }
        return $list;
    }
}
