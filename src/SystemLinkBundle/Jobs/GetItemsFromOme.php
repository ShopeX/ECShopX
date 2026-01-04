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
use Dingo\Api\Exception\ResourceException;
use SystemLinkBundle\Services\ShopexErp\OpenApi\Request;
use SystemLinkBundle\Services\ThirdSettingService;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsAttributesService;
use SystemLinkBundle\Services\OmsQueueLogService;

class GetItemsFromOme extends Job
{
    private $companyId;
    private $page;
    private $pageSize = 10;
    private $endLastmodify;

    private $goodsBn = null;
    private $preGoodsbn = null;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($companyId, $page, $endLastmodify, $goodsBn = '')
    {
        $this->companyId = $companyId;
        $this->page = $page;
        $this->endLastmodify = $endLastmodify;
        $this->goodsBn = $goodsBn;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $companyId = $this->companyId;
        $endLastmodify = $this->endLastmodify;

        // 判断是否开启OME
        $service = new ThirdSettingService();
        $data = $service->getShopexErpSetting($companyId);
        if (!isset($data) || ($data['is_openapi_open'] ?? false) == false) {
            app('log')->debug('companyId:'.$companyId.",msg:未开启OME开放数据接口");
            return true;
        }

        $startLastmodify = app('redis')->hget($this->_key(), 'items');

        $params = [
            'page_no' => $this->page,
            'page_size' => $this->pageSize,
            'start_lastmodify' => date('Y-m-d H:i:s', $startLastmodify ?: 0),
            'end_lastmodify' => date('Y-m-d H:i:s', $endLastmodify),
        ];

        if ($this->goodsBn) {
            $params = [
                'goods_bn' => $this->goodsBn,
            ];
        }

        try {
            $omeRequest = new Request($companyId);
            $method = 'goods.getList';
            $result = $omeRequest->call($method, $params);
            app('log')->debug($method."=>". var_export($result, 1));

            $data = $result['data'] ?? [];
            if (!isset($data['rsp']) || $data['rsp'] != 'succ') {
                app('log')->debug('companyId:'.$companyId.",msg:OME批量获取商品信息请求失败");
                return true;
            }

            if ($data['count'] > 0) {
                $conn = app('registry')->getConnection('default');
                $itemsService = new ItemsService();
                $conn->beginTransaction();
                try {
                    foreach ($data['list'] as $goods) {
                        $t1 = microtime(true);
                        $logParams['goods_bn'] = $goods['goods_bn'];

                        $products = $goods['products'];
                        unset($goods['products']);
                        foreach ($products as $row) {
                            $row = array_merge($goods, $row);
                            $item = $itemsService->getInfo([
                                'item_bn' => $row['product_bn'],
                                'company_id' => $companyId,
                            ]);
                            $this->handleRow($companyId, $row, $itemInfo, $item);
                        }
                        $itemInfo['spec_items'] = json_encode($itemInfo['spec_items']);
                        $itemsService->addItems($itemInfo);
                    }

                    $conn->commit();
                } catch (\Exception $e) {
                    $conn->rollback();
                    $this->saveErrLog($method, $t1, $goods, $e->getMessage());
                    throw new ResourceException($e->getMessage());
                } catch (\Throwable $e) {
                    $conn->rollback();
                    $this->saveErrLog($method, $t1, $goods, $e->getMessage());
                    throw new \Exception($e->getMessage());
                }

                if ($params['page_no'] * $params['page_size'] >= $data['count']) {
                    app('redis')->hset($this->_key(), 'items', $endLastmodify);
                } else {
                    $gotoJob = (new GetItemsFromOme($companyId, $params['page_no'] + 1, $endLastmodify))->onQueue('slow');
                    app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
                }
            }
        } catch (\Exception $e) {
            app('log')->debug('OME批量获取商品信息请求失败:'. $e->getMessage());
        }
    }

    public function handleRow($companyId, $row, &$itemInfo, $item = [])
    {
        $nospec = $row['spec_info'] ? false : true;
        //表示为多规格，并且已经存储了默认商品，所以只需要新增当前商品数据，通用关联数据不需要更新，例如：商品关联的分类，关联的品牌等
        if (($nospec === false || $nospec === 'false') && $this->preGoodsbn && trim($row['goods_bn']) == $this->preGoodsbn) {
            $isDefault = false;
        } else {
            $isDefault = true;
            $this->preGoodsbn = trim($row['goods_bn']);
        }

        if ($isDefault) {
            $itemInfo = [
                'company_id' => $companyId,
                'item_type' => 'normal',
                'item_name' => trim($row['goods_name']),
                'nospec' => $nospec,
                'item_main_cat_id' => $this->getItemCategoryId($companyId, $row),
                'brand_id' => $this->getBrandId($companyId, $row),
                'item_bn' => $row['product_bn'],
                'weight' => $row['weight'],
                'barcode' => $row['barcode'],
                'price' => $row['price'],
                'cost_price' => $row['cost'],
                'market_price' => $row['mktprice'],
                'item_unit' => $row['unit'],
                'approve_status' => $item['approve_status'] ?? 'instock',
                'is_default' => $isDefault,
                'spec_items' => [],
                // 不需要同步的参数
                'item_id' => $item['item_id'] ?? null,
                'store' => $item['store'] ?? 0,
                'consume_type' => $item['consume_type'] ?? null,
                'brief' => $item['brief'] ?? null,
                'sort' => $item['sort'] ?? null,
                'templates_id' => $item['templates_id'] ?? null,
                'is_show_specimg' => $item['is_show_specimg'] ?? null,
                'pics' => $item['pics'] ?? [],
                'video_type' => $item['video_type'] ?? null,
                'videos' => $item['videos'] ?? null,
                'intro' => $item['intro'] ?? null,
                'special_type' => $item['special_type'] ?? null,
                'purchase_agreement' => $item['purchase_agreement'] ?? null,
                'enable_agreement' => $item['enable_agreement'] ?? null,
                'item_address_city' => $item['item_address_city'] ?? null,
                'item_address_province' => $item['item_address_province'] ?? null,
                'date_type' => $item['date_type'] ?? null,
                'begin_date' => $item['begin_date'] ?? null,
                'end_date' => $item['end_date'] ?? null,
                'fixed_term' => $item['fixed_term'] ?? null,
                'tax_rate' => $item['tax_rate'] ?? null,
                'crossborder_tax_rate' => $item['crossborder_tax_rate'] ?? null,
                'origincountry_id' => $item['origincountry_id'] ?? null,
                'type' => $item['type'] ?? null,
                'distributor_id' => $item['distributor_id'] ?? null,
                'item_source' => $item['item_source'] ?? null,
                'is_gift' => $item['is_gift'] ?? null,
                'is_profit' => $item['is_profit'] ?? null,
                'profit_type' => $item['profit_type'] ?? null,
                'profit_fee' => $item['profit_fee'] ?? null,
            ];
        }

        if ($nospec === false || $nospec === 'false') {
            $specItem = [
                'item_bn' => $row['product_bn'],
                'weight' => $row['weight'],
                'barcode' => $row['barcode'],
                'price' => $row['price'],
                'cost_price' => $row['cost'],
                'market_price' => $row['mktprice'],
                'item_unit' => $row['unit'],
                'approve_status' => $item['approve_status'] ?? 'instock',
                'is_default' => $isDefault,
                'item_spec' => $this->getItemSpec($companyId, $row),
                // 不需要同步的参数
                'item_id' => $item['item_id'] ?? null,
                'store' => $item['store'] ?? 0,
            ];

            $itemInfo['spec_items'][] = $specItem;
        }
    }

    /**
     * 获取商品分类
     */
    private function getItemCategoryId($companyId, $row)
    {
        // NOTE: important business logic
        $itemsCategoryService = new ItemsCategoryService();
        // 数据结构买办法判断获取的分类ID是否最子级分类，三级分类改造后在优化
        $category = $itemsCategoryService->getInfo(['company_id' => $companyId, 'category_code' => $row['cat_code'], 'is_main_category' => true, 'category_level' => 3]);
        if (!$category) {
            throw new ResourceException('商品分类['.$row['cat_code'].']不存在');
        }

        return $category['category_id'];
    }

    /**
     * 通过品牌名称获取品牌ID
     */
    private function getBrandId($companyId, $row)
    {
        // NOTE: important business logic
        $brandName = $row['goods_brand'] ?? "";
        $brandId = 0;
        if ($brandName) {
            $itemsAttributesService = new ItemsAttributesService();
            $data = $itemsAttributesService->getInfo(['company_id' => $companyId, 'attribute_name' => $brandName, 'attribute_type' => 'brand']);
            if (!$data) {
                throw new ResourceException('品牌名称['.$brandName.']不存在');
            }
            $brandId = $data['attribute_id'];
        }
        return $brandId;
    }

    private function getItemSpec($companyId, $row)
    {
        $data = [];
        if ($row['spec_info']) {
            $itemsAttributesService = new ItemsAttributesService();
            $itemParams = $row['spec_info'];
            foreach ($itemParams as $key => $value) {
                $attributeCode[] = $key;
                $attributeValues[] = trim($value);
            }

            $attrList = $itemsAttributesService->lists(['company_id' => $companyId, 'attribute_code' => $attributeCode, 'attribute_type' => 'item_spec']);
            if ($attrList['total_count'] == count($attributeCode)) {
                $attributeids = array_column($attrList['list'], 'attribute_id');
            } else {
                throw new ResourceException('存在无效的商品规格['.implode('、', $attributeCode).']');
            }

            $attrValuesList = $itemsAttributesService->getAttrValuesListBy(['company_id' => $companyId, 'attribute_value' => $attributeValues, 'attribute_id' => $attributeids]);
            if ($attrValuesList['total_count'] == count($attributeValues)) {
                foreach ($attrValuesList['list'] as $row) {
                    $data[] = [
                        'spec_id' => $row['attribute_id'],
                        'spec_value_id' => $row['attribute_value_id']
                    ];
                }
            } else {
                throw new ResourceException('存在无效的商品规格值['.implode('、', $attributeValues).']');
            }
        }

        usort($data, function ($a, $b) {
            if ($a['spec_id'] == $b['spec_id']) {
                return 0;
            } else {
                return $a['spec_id'] > $b['spec_id'] ? 1 : -1;
            }
        });

        return $data;
    }

    private function _key()
    {
        return 'LastTimeGetFromOme:'.$this->companyId;
    }

    private function saveErrLog($api, $t1, $goods, $msg)
    {
        $t2 = microtime(true);
        $runtime = round($t2 - $t1, 3);

        $params = [
            'goods_bn' => $goods['goods_bn'],
            'job' => GetItemsFromOme::class,
        ];

        $logParams = [
            'result' => $msg,
            'runtime' => $runtime,
            'company_id' => $this->companyId,
            'api_type' => 'request',
            'worker' => $api,
            'params' => $params,
            'status' => 'fail',
        ];
        $omsQueueLogService = new OmsQueueLogService();
        $logResult = $omsQueueLogService->create($logParams);
        return true;
    }
}
