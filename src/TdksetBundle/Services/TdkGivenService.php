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

namespace TdksetBundle\Services;

use GoodsBundle\Entities\ItemsCategory;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\CommonLangModService;
use CompanysBundle\Services\Shops\WxShopsService;
use Dingo\Api\Exception\DeleteResourceFailedException;

class TdkGivenService
{
    public $key = 'TdkGiven_';

    public function __construct()
    {
    }

    /**
     * 获取信息
     */
    public function getInfo($key2, $companyId)
    {
        $redis = app('redis')->connection('default');
        $result = $redis->get($this->key . $key2 . '_' . $companyId);

        if (!empty($result) and $result != 'null') {
            $result = json_decode($result, true);
            $ns = new CommonLangModService();
            $result = $ns->getLangDataIndexLang($result);
            if(empty($result)){
                $data['title'] = '';
                $data['mate_description'] = '';
                $data['mate_keywords'] = '';
                return  $data;
            }
            return $result;
        } else {
            $data['title'] = '';
            $data['mate_description'] = '';
            $data['mate_keywords'] = '';
            return $data;
        }
    }

    /**
     * 保存
     */
    public function saveSet($key2, $companyId, $data)
    {
        $redis = app('redis')->connection('default');
        // 多对语言保存
        $ns = new CommonLangModService();
        $data = $ns->setLangDataIndexLang($data, ['title', 'mate_description', 'mate_keywords']);

        $info = $redis->set($this->key . $key2 . '_' . $companyId, json_encode($data));
        if (!empty($info)) {
            return [];
        } else {
            throw new DeleteResourceFailedException("保存失败");
        }
    }

    /**
     * 获取处理后的数据
     */
    public function getData($tdk, $data = null)
    {
        $tdkData['title'] = str_replace(',', '_', $this->handleGetData($tdk['title'], $data));
        $tdkData['mate_description'] = str_replace(',', '_', $this->handleGetData($tdk['mate_description'], $data));
        $tdkData['mate_keywords'] = str_replace(',', '_', $this->handleGetData($tdk['mate_keywords'], $data));
        return $tdkData;
    }

    /**
     * 处理数据
     */
    private function handleGetData($tdk_k, $data)
    {
        $tdk_k = explode(',', $tdk_k);
        foreach ($tdk_k as $k => $v) {
            $tdk_kk = substr(substr($v, 0, strlen($v) - 1), 1);

            if ($tdk_kk == 'goods_brand') {
                $tdk_data[] = $data['goods_brand'];
            } elseif ($tdk_kk == 'goods_price') {
                $tdk_data[] = '￥' . $data['price'] / 100;
            } elseif ($tdk_kk == 'goods_name') {
                $tdk_data[] = $data['item_name'];
            } elseif ($tdk_kk == 'goods_category') {
                $category_data = $this->getcategory($data['item_category'][0]);
                $tdk_data[] = $category_data['category_name'];
            } elseif ($tdk_kk == 'goods_brief') {
                if (!empty($data['brief'])) {
                    $tdk_data[] = $data['brief'];
                }
            } elseif ($tdk_kk == 'search_keywords') {  // 列表中的
                // 列表搜索关键字
                if (!empty($data['keywords'])) {
                    $tdk_data[] = $data['keywords'];
                }
            } elseif ($tdk_kk == 'category') {
                // 列表分类名称
                $category_data = $this->getcategory($data['category_id']);
                $tdk_data[] = $category_data['category_name'];
            } elseif ($tdk_kk == 'category_path') {
                // 列表分类路径
                $category_data = $this->getcategory($data['category_id']);
                $tdk_data[] = $category_data['category_path'];
            } elseif ($tdk_kk == 'shop_name') {
                // 商城名
                $shopsService = new ShopsService(new WxShopsService());
                $shopsSetInfo = $shopsService->getWxShopsSetting($data['company_id']);
                $tdk_data[] = $shopsSetInfo['brand_name'];
            } else {
                $tdk_data[] = $tdk_kk . '-无';
            }
        }
        $tdk_data = implode(',', $tdk_data ?? []);
        return $tdk_data;
    }

    /**
     * 获取分类信息
     */
    private function getcategory($category_id = null)
    {
        if (empty($category_id)) {
            $categoryinfo['category_name'] = '';
            $categoryinfo['category_path'] = '';
            return $categoryinfo;
        }
        $where['category_id'] = $category_id;
        $ItemsCategory = app('registry')->getManager('default')->getRepository(ItemsCategory::class);
        $categorydata = $ItemsCategory->getInfo($where);                                        // 分类信息
        $categoryinfo['category_name'] = $categorydata['category_name'];                        // 分类名称

        // 分类路径信息
        $path = explode(',', $categorydata['path']);
        $categorypathdata = $ItemsCategory->lists(['category_id' => $path], null, -1);
        foreach ($categorypathdata['list'] as $k => $v) {
            $categoryinfo['category_path'][] = $v['category_name'];
        }

        $categoryinfo['category_path'] = implode('/', $categoryinfo['category_path']);      // 分类路径名称
        return $categoryinfo;
    }
}
