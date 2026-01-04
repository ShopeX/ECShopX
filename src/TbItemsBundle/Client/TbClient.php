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

namespace TbItemsBundle\Client;

use TbItemsBundle\Client\ClientBase;


class TbClient extends ClientBase
{
    public $uri;
    public $config;
    public function __construct()
    {
        $this->config = config('tbitems');
        $this->uri = $this->config['tb_url'];
    }

    public function request(array $params = []):  ? string
    {
        $this->setOptions();
        $params += $this->config + ['timestamp' => time(), 'shop_bn' => $this->config['tb_shop_bn']];
        unset($params['token']);
        $params['sign'] = $this->genSign($params);

        return $this->call($params);
    }

   
    public function genSign(array $params = []) :  ? string
    {
        if (!$this->config['tb_token']) {
            return false;
        }

        return strtoupper(md5(strtoupper(md5($this->assemble($params))) . $this->config['tb_token']));
    }

   
    public static function assemble(array $params = []) :  ? string
    {
        if (!is_array($params)) {
            return null;
        }

        ksort($params, SORT_STRING);
        $sign = '';
        foreach ($params as $key => $value) {
            if (is_null($value)) {
                continue;
            }
            if (is_bool($value)) {
                $value = $value ? 1 : 0;
            }
            $sign .= $key . (is_array($value) ? self::assemble($value) : $value);
            // app('log')->info('sign====' . $sign . PHP_EOL);
        }

        return $sign;
    }

    /**
     * 获取淘宝分类 - ESB接口
     * taobao.itemcats.authorize.get
     * @param array $filter
     * @param int $page
     * @param int $pageSize
     * taobao返回的格式
     * {"itemcats_authorize_get_response":{"seller_authorize":{"item_cats":{"item_cat":[{"cid":50011999,"parent_cid":0,"name":"单方精油","status":"normal","sort_order":1,"is_parent":true}]},"xinpin_item_cats":{"item_cat":[{"cid":50011999,"parent_cid":0,"name":"单方精油","status":"normal","sort_order":1,"is_parent":true}]},"brands":{"brand":[{"vid":3709439,"name":"测试<<品牌","pid":20000,"prop_name":"品牌"}]}}}}

     * @return string|null
     */
    public function getTbCategoryListing(array $filter = [], int $page = 1, int $pageSize = 10) :  ? string
    {
        // ModuleID: 76fe2a3d
        $params = [
            // 'method' => 'store.itemcats.authorize.get',
            'method' => 'service.itemcats.get',
            'format' => 'json',
            'v' => '1.0',
            // 'from_node_id' => 0,
            'page_no' => $page,
            'page_size' => $pageSize,
            'parent_cid' => 0,
            // 'fields' => 'cid,parent_cid,name,is_parent',
        ];
        //处理返回的格式 rsp = succ
        // $response = $this->request($params);
        // app('log')->info('getTbCategoryListing response====' . $response . PHP_EOL);
        // $response = json_decode($response, true);

        //测试
        // $response = '{"itemcats_authorize_get_response":{"seller_authorize":{"item_cats":{"item_cat":[{"cid":50011999,"parent_cid":0,"name":"单方精油","status":"normal","sort_order":1,"is_parent":true},{"cid":500119991,"parent_cid":50011999,"name":"单方精油1","status":"normal","sort_order":2,"is_parent":false},{"cid":5001199911,"parent_cid":500119991,"name":"单方精油11","status":"normal","sort_order":3,"is_parent":false}]},"xinpin_item_cats":{"item_cat":[{"cid":50011999,"parent_cid":0,"name":"单方精油","status":"normal","sort_order":1,"is_parent":true}]},"brands":{"brand":[{"vid":3709439,"name":"测试<<品牌","pid":20000,"prop_name":"品牌"}]}}}}';
        $response = '{"data":{"seller_authorize":{"item_cats":[{"cid":50011999,"parent_cid":0,"name":"默认一级分类","status":"normal","sort_order":1,"is_parent":true},{"cid":500119991,"parent_cid":50011999,"name":"默认二级分类","status":"normal","sort_order":2,"is_parent":false},{"cid":5001199911,"parent_cid":500119991,"name":"默认三级分类","status":"normal","sort_order":3,"is_parent":false}]}},"rsp":"succ"}';
        $response = json_decode($response, true);

        //{"rsp":"succ","msg":null,"code":"","data":"{\"seller_authorize\": {\"xinpin_item_cats\": {}}, \"request_id\": \"15r2pasbyv6y6\"}","msg_id":"685A5C7FC0A8028646463EA7C6CE2C28"}
        $result = [];
        if (isset($response['rsp']) && $response['rsp'] == 'succ') {
            $result['rsp'] = 'succ';
            $result['data'] = $response['data']['seller_authorize']['item_cats'] ?? [];
        }
        if (!isset($response['rsp']) || $response['rsp'] != 'succ') {
            $result['rsp'] = 'fail';
            $result['msg'] = '淘宝端无分类数据，或接口异常，请联系管理员！';
            $result['data'] = [];
        }
        return json_encode($result);
    }

    /**
     * 获取淘宝商品
     * store.items.all.get
     * @param array $filter
     * @param int $page
     * @param int $pageSize
     * @return string|null
     */
    public function getTbItemsListing(array $filter = [], int $page = 1, int $pageSize = 10) :  ? string
    {
        $params = [
            'method' => 'store.items.all.get',
            'approve_status' => 'onsale',
            'format' => 'json',
            'v' => '1.0',   
            // 'fields' => ' approve_status,num_iid,title,nick,type,cid,pic_url,num,props,valid_thru,list_time,price,has_discount,has_invoice,has_warranty,has_showcase,modified,delist_time,postage_id,seller_cids,outer_id,sold_quantity',
            'page_no' => $page,
            'page_size' => $pageSize,
        ];
        
        //start_modified\end_modified
        if (isset($filter['start_modified']) && isset($filter['end_modified'])) {
            $params['start_modified'] = $filter['start_modified'];
            $params['end_modified'] = $filter['end_modified'];
        }
        //处理返回的格式 rsp = succ
        $response = $this->request($params);
        // app('log')->info('getTbItemsListing response====' . $response . PHP_EOL);
        $response = json_decode($response, true);

        // 处理嵌套的 JSON 数据
        if (isset($response['data']) && is_string($response['data'])) {
            $parsedData = json_decode($response['data'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $response['data'] = $parsedData;
            } else {
                $response['data'] = [];
            }
        }

        //测试
        // $response = '{"items_onsale_get_response":{"total_results":150,"items":{"item":[{"approve_status":"onsale","iid":"13232","num_iid":1489161932,"title":"Google test item","nick":"tbtest561","type":"fixed","cid":132443,"seller_cids":"2234445,3344466,446434","pic_url":"http:\/\/img03.taobao.net\/bao\/uploaded\/i3\/T1HXdXXgPSt0JxZ2.8_070458.jpg","num":8888,"props":"135255:344454","valid_thru":7,"list_time":"2009-10-22 14:22:06","price":"5.00","has_discount":true,"has_invoice":true,"has_warranty":true,"has_showcase":true,"modified":"2000-01-01 00:00:00","delist_time":"2000-01-01 00:00:00","postage_id":32,"outer_id":"34143554352","is_ex":true,"is_virtual":true,"is_taobao":true,"sold_quantity":8888,"is_cspu":true,"first_starts_time":"2000-01-01 00:00:00"}]}}}';
        // $response = json_decode($response, true);   

        $result = [];
        if (isset($response['rsp']) && $response['rsp'] == 'succ') {
            $result['rsp'] = 'succ';
            app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品数据msgid' . $response['msg_id']);
            $result['data'] = $response['data']['items']['item'] ?? [];
        }
        if (!isset($response['rsp']) || $response['rsp'] != 'succ') {
            $result['rsp'] = 'fail';
            $result['msg'] = '淘宝端无商品数据，或接口异常，请联系管理员！';
            $result['data'] = [];
        }
        return json_encode($result);
    }

    /**
     * 获取淘宝商品属性
     * taobao.items.attributes.get
     * @param array $filter
     * @param int $page
     * @param int $pageSize
     * @return string|null
     */
    public function getTbItemsAttributes(array $filter = [], int $page = 1, int $pageSize = 10) :  ? string
    {
        $params = [
            'method' => 'store.item.specs.get',
            'format' => 'json',
            'v' => '1.0',
            'page_no' => $page,
            'page_size' => $pageSize,
        ];
        //处理返回的格式 rsp = succ
        $response = $this->request($params);
        app('log')->info('getTbItemsAttributes response====' . $response . PHP_EOL);
        $response = json_decode($response, true);
        return json_encode($response);
    }

    function getTbSkusListing(array $filter = [], int $page = 1, int $pageSize = 10) :  ? string
    {
        $params = [
            'method' => 'store.items.list.get',
            'format' => 'json',
            'v' => '1.0',
            'page_no' => $page,
            'page_size' => $pageSize,
            'iids' => $filter['iids'],
        ];
        $response = $this->request($params);
        // app('log')->info('getTbSkusListing response====' . $response . PHP_EOL);
        $response = json_decode($response, true);

        // 处理嵌套的 JSON 数据
        if (isset($response['data']) && is_string($response['data'])) {
            $parsedData = json_decode($response['data'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $response['data'] = $parsedData;
            } else {
                $response['data'] = [];
            }
        }

        $result = [];
        if (isset($response['rsp']) && $response['rsp'] == 'succ') {
            $result['rsp'] = 'succ';
            app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品skus数据msgid' . $response['msg_id']);
            $result['data'] = $response['data']['items']['item'] ?? [];
        }
        if (!isset($response['rsp']) || $response['rsp'] != 'succ') {
            $result['rsp'] = 'fail';
            $result['msg'] = '淘宝端无商品skus数据，或接口异常，请联系管理员！';
            $result['data'] = [];
        }
        return json_encode($result);
    }   

    //从第三级分类开始获取如有父级分类则获取父级分类，递归获取所有分类  
    function getTbItemsCategoryListing(array $filter = [], int $page = 1, int $pageSize = 10) : ? string
    {
        $params = [
            'method' => 'store.sellercats.list.get',
            'format' => 'json',
            'v' => '1.0',
        ];
        
        $response = $this->request($params);
        // app('log')->info('getTbItemsCategoryListing response====' . $response . PHP_EOL);
        $response = json_decode($response, true);
        
        // 处理嵌套的 JSON 数据
        if (isset($response['data']) && is_string($response['data'])) {
            $parsedData = json_decode($response['data'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $response['data'] = $parsedData;
            } else {
                $response['data'] = [];
            }
        }

        $result = [];
        if (isset($response['rsp']) && $response['rsp'] == 'succ') {
            $result['rsp'] = 'succ';
            app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品分类数据msgid' . $response['msg_id']);
            
            $result['data'] = $response['data']['seller_cats']['seller_cat'] ?? [];
            // 获取当前层级的分类
            // $currentCategories = $response['data']['seller_cats']['seller_cat'] ?? [];
            
            // 只在顶层调用递归方法
            // if (empty($filter['is_recursive'])) {
            //     $allCategories = $this->getAllCategoriesRecursively($currentCategories);
            //     $result['data'] = $allCategories;
            // } else {
            //     $result['data'] = $currentCategories;
            // }
        }
        
        if (!isset($response['rsp']) || $response['rsp'] != 'succ') {
            $result['rsp'] = 'fail';
            $result['msg'] = '淘宝端无商品分类数据，或接口异常，请联系管理员！';
            $result['data'] = [];
        }
        
        return json_encode($result);
    }

    /**
     * 递归获取所有分类
     */
    private function getAllCategoriesRecursively(array $categories, int $maxDepth = 3, int $currentDepth = 0) : array
    {
        $allCategories = [];
        
        // 防止无限递归
        if ($currentDepth >= $maxDepth) {
            app('log')->warning('分类递归深度达到最大值: ' . $maxDepth);
            return $allCategories;
        }
        
        foreach ($categories as $category) {
            // 添加当前分类
            $allCategories[] = $category;
            
            // 如果是父分类且有子分类，递归获取子分类
            if (isset($category['is_parent']) && $category['is_parent'] && isset($category['cid'])) {
                try {
                    // 添加延迟避免请求过快
                    usleep(100000); // 100ms
                    
                    // 标记为递归调用，避免重复处理
                    $childResponse = $this->getTbItemsCategoryListing([
                        'cids' => $category['cid'],
                        'is_recursive' => true
                    ], 1, 100);
                    $childData = json_decode($childResponse, true);
                    
                    if (isset($childData['rsp']) && $childData['rsp'] == 'succ' && !empty($childData['data'])) {
                        $childCategories = $this->getAllCategoriesRecursively(
                            $childData['data'], 
                            $maxDepth, 
                            $currentDepth + 1
                        );
                        $allCategories = array_merge($allCategories, $childCategories);
                    }
                    
                } catch (Exception $e) {
                    app('log')->error('获取子分类失败: ' . $e->getMessage(), [
                        'parent_cid' => $category['cid'],
                        'depth' => $currentDepth
                    ]);
                    continue;
                }
            }
        }
        
        return $allCategories;
    }

    function getFillTbItemsInfo(array $filter = [], int $page = 1, int $pageSize = 10) : ? string
    {
        $params = [
            'method' => 'store.item.get',
            'format' => 'json',
            'v' => '1.0',   
            'iid' => $filter['iid'],
        ];
        $response = $this->request($params);
        // app('log')->info('getFillTbItemsInfo response====' . $response . PHP_EOL);
        $response = json_decode($response, true);

        // 处理嵌套的 JSON 数据
        if (isset($response['data']) && is_string($response['data'])) {
            $parsedData = json_decode($response['data'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $response['data'] = $parsedData;
            } else {
                $response['data'] = [];
            }
        }

        $result = [];
        if (isset($response['rsp']) && $response['rsp'] == 'succ') {
            $result['rsp'] = 'succ';
            app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品详情数据msgid' . $response['msg_id']);
            $result['data'] = $response['data']['item'] ?? [];
            // app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . '同步淘宝商品详情数据' . json_encode($response['data']['item']['outer_id']));
        }
        if (!isset($response['rsp']) || $response['rsp'] != 'succ') {
            $result['rsp'] = 'fail';
            $result['msg'] = '淘宝端无商品详情数据，或接口异常，请联系管理员！';
            $result['data'] = [];
        }

        return json_encode($result);
    }

}
