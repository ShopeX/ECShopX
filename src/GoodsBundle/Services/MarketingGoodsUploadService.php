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

namespace GoodsBundle\Services;

use CompanysBundle\Services\SettingService;
use OrdersBundle\Services\ShippingTemplatesService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;

class MarketingGoodsUploadService
{
    public $itemName = null;

    public $defaultItemId = null;

    public $header = [
        '商品编码' => 'item_bn',
        '商品名称' => 'item_name',
        '活动价' => 'activity_price',
        '库存(非秒杀活动可为0)' => 'activity_store',
        '每人限购' => 'limit_num',
        '排序' => 'sort',
    ];

    public $headerInfo = [
        '商品编码' => [
            'size' => 32,
            'remarks' => '商品编码',
            'is_need' => true,
        ],
        '商品名称' => [
            'size' => 255,
            'remarks' => '商品名称',
            'is_need' => true,
        ],
        '活动价' => [
            'size' => 255,
            'remarks' => '单位为(元)，最多两位小数',
            'is_need' => true,
        ],
        '库存' => [
            'size' => 255,
            'remarks' => '库存为大于0的整数，请注意是整数，小数或浮点数将向上取整',
            'is_need' => false,
        ],
        '每人限购' => [
            'size' => 255,
            'remarks' => '大于等于0的整数',
            'is_need' => true,
        ],
        '排序' => [
            'size' => 255,
            'remarks' => '大于等于0的整数，从大到小排序',
            'is_need' => false,
        ],
    ];

    public $isNeedCols = [
        '商品编码' => 'item_bn',
        '商品名称' => 'item_name',
        '活动价' => 'activity_price',
        '每人限购' => 'limit_num',
    ];
    public $tmpTarget = null;

    /**
     * 验证上传的实体商品信息
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('活动商品信息上传只支持Excel文件格式(xlsx)');
        }
    }

    /**
     * 返回上传的活动商品列表
     *
     * @param $fileUrl
     *
     * @return array
     */
    public function syncProcess($fileUrl)
    {
        ini_set('memory_limit', '256M');
        $items = [];
        $fail_items = [];//数据库里不存在的商品货号
        $invalid = []; //已参加其他活动的商品
        $maxItemNums = 500;//每次最多上传500
        //设置头部
        $results = app('excel')->toArray(new \stdClass(), $fileUrl, null, 'Xlsx');
        $results = $results[0];

        $headerData = array_filter($results[0]);
        $column = $this->headerHandle($headerData);
        $headerSuccess = true;
        unset($results[0]);

        if (count($results) > $maxItemNums) {
            throw new BadRequestHttpException("每次最多上传{$maxItemNums}个商品...请减少后再提交");
        }

        // 如果头部是正确的，才会处理到下一步
        if ($headerSuccess) {
            foreach ($results as $row) {
                if (!array_filter($row)) {
                    continue;
                }
                $item = $this->preRowHandle($column, $row);
                $items[$item['item_bn']] = $item;
            }
            //批量查询商品信息, ID 和 商品图片
            if ($items) {
                $itemsService = new ItemsService();
                $params = [
                    'item_bn'        => array_keys($items),
                    'distributor_id' => app('auth')->user()->get('distributor_id')
                ];
                $list = $itemsService->getItemsList($params, 1, $maxItemNums);
                $datalist = array_column($list['list'], null, 'item_id');
                if ($datalist) {
                    foreach ($datalist as $v) {
                        $v['item_bn'] = trim($v['item_bn']);
                        $items[$v['item_bn']]['item_id'] = $v['item_id'];
                        $items[$v['item_bn']]['itemId'] = $v['item_id'];
                        $items[$v['item_bn']]['default_item_id'] = $v['default_item_id'];
                        $items[$v['item_bn']]['pics'] = $v['pics'];
                        $items[$v['item_bn']]['market_price'] = $v['market_price'];
                        $items[$v['item_bn']]['item_name'] = $v['item_name'];
                        $items[$v['item_bn']]['itemName'] = $v['item_name'];
                        $items[$v['item_bn']]['item_type'] = $v['item_type'];
                        $items[$v['item_bn']]['nospec'] = true;
                        $items[$v['item_bn']]['price'] = ($items[$v['item_bn']]['activity_price'] ?? 0) * 100;
                        $items[$v['item_bn']]['sort'] = $items[$v['item_bn']]['sort'] ?? 0;
                        $items[$v['item_bn']]['store'] = $items[$v['item_bn']]['activity_store'] ?? $v['store'];
                    }
                }
            }
            //将错误和正确的商品编码分开返回
            foreach ($items as $k => $v) {
                if ($v['item_bn'] == null) {
                    throw new BadRequestHttpException('货号不能为空...请检查数据');
                }
                if (!isset($v['item_id']) && $v['item_bn']) {
                    $fail_items[] = [
                        'item_bn' => $v['item_bn'],
                        'item_name' => $v['item_name'],
                    ];
                    unset($items[$k]);
                }
            }
        }

        return [
            'succ' => array_values($items),
            'invalid' => $invalid,
            'fail' => $fail_items,
        ];
    }

    protected function preRowHandle($column, $row)
    {
        $data = [];
        foreach ($column as $key => $col) {
            if (isset($row[$key])) {
                $data[$col] = trim($row[$key]);
            } else {
                $data[$col] = null;
            }
        }
        return $data;
    }

    /**
     * 处理导入头部信息
     */
    protected function headerHandle($headerData)
    {
        // Ref: 1996368445
        $title = $this->getHeaderTitle();
        if ($title) {
            foreach (array_keys($title['is_need']) as $col) {
                if (!in_array($col, $headerData)) {
                    throw new BadRequestHttpException($col . '必须导入');
                }
            }

            foreach ($headerData as $key => $columnName) {
                if (isset($title['all'][$columnName])) {
                    $column[$key] = $title['all'][$columnName];
                }
            }
        }
        return $column;
    }

    /**
     * getFilePath function
     *
     * @return void
     */
    public function getFilePath($filePath, $fileExt = '')
    {
        $url = $this->getFileSystem()->privateDownloadUrl($filePath);

        $client = new Client();
        $content = $client->get($url)->getBody()->getContents();

        $this->tmpTarget = tempnam('/tmp', 'import-file') . $fileExt;
        file_put_contents($this->tmpTarget, $content);

        return $this->tmpTarget;
    }

    public function getFileSystem()
    {
        //$filesystem = app('filesystem')->disk('qiniu');
        //$filesystem->getAdapter()->withBucket('import-file');
        //return $filesystem;
        return app('filesystem')->disk('import-file');
    }

    public function finishHandle()
    {
        unlink($this->tmpTarget);
        return true;
    }

    /**
     * 获取头部标题
     */
    public function getHeaderTitle()
    {
        return [
            'all' => $this->header,
            'is_need' => $this->isNeedCols,
            'headerInfo' => $this->getHeaderInfo(),
        ];
    }

    private function validatorData($row)
    {
        $arr = [
            'item_name',
            'price',
            'store',
            'templates_id',
        ];
        $data = [];
        foreach ($arr as $column) {
            if ($row[$column]) {
                $data[$column] = $row[$column];
            }
        }

        return $data;
    }

    /**
     * 通过运费模版名称，获取运费模版ID
     */
    private function getTemplatesId($companyId, $row)
    {
        if (!$row['templates_id']) {
            throw new BadRequestHttpException('请填写商品运费模版');
        }

        $shippingTemplatesService = new ShippingTemplatesService();
        $data = $shippingTemplatesService->getInfoByName($row['templates_id'], $companyId);
        if (!$data) {
            throw new BadRequestHttpException('填写的运费模版不存在');
        }

        return $data['template_id'];
    }

    /**
     * 获取商品分类
     */
    private function getItemCategory($companyId, $row, $isMain = false)
    {
        if ($isMain) {
            $category = $row['item_main_category'];
        } else {
            $category = $row['item_category'];
        }

        if ($category) {
            $catNames = explode('|', $category);
        } else {
            if ($isMain) {
                throw new BadRequestHttpException('请上传管理分类');
            } else {
                throw new BadRequestHttpException('请上传商品分类');
            }
        }

        $catNamesArr = [];
        foreach ($catNames as $catNameRow) {
            $catNamesArr = array_merge($catNamesArr, explode('->', $catNameRow));
        }

        $itemsCategoryService = new ItemsCategoryService();
        // 数据结构买办法判断获取的分类ID是否最子级分类，三级分类改造后在优化
        $lists = $itemsCategoryService->lists([
            'company_id' => $companyId,
            'category_name' => $catNamesArr,
            'is_main_category' => $isMain,
        ]);
        if ($lists['total_count'] <= 0) {
            if ($isMain) {
                throw new BadRequestHttpException('上传管理分类参数有误');
            } else {
                throw new BadRequestHttpException('上传商品分类参数有误');
            }
        }
        //主类目

        $parentIds = [];
        $pathArr = [];
        foreach ($lists['list'] as $catRow) {
            if ($catRow['category_level'] != '3') {
                $parentIds[] = $catRow['category_id'];
            }
            if ($catRow['category_level'] == '3') {
                $pathArr[] = $catRow['path'];
            }
        }
        if (!$parentIds) {
            if ($isMain) {
                throw new BadRequestHttpException('上传管理分类参数有误');
            } else {
                throw new BadRequestHttpException('上传商品分类参数有误');
            }
        }
        $catId = [];
        foreach ($lists['list'] as $catRow) {
            $parentArr = [];
            if ($catRow['category_level'] == '3') {
                $parentArr = explode(',', $catRow['path']);
                unset($parentArr[2]);
            } else {
                if ($catRow['category_level'] == '2') {
                    $result = false;
                    foreach ($pathArr as $v) {
                        $result = 0 === strpos($v, $catRow['path']) ? true : false;
                        if ($result) {
                            continue;
                        }
                    }
                    if ($result) {
                        continue;
                    }
                    $parentArr = explode(',', $catRow['path']);
                    unset($parentArr[1]);
                }
            }
            if ($parentArr && $parentArr == array_intersect($parentArr, $parentIds)) {
                $catId[] = $catRow['category_id'];
            }
        }
        if (!$catId) {
            if ($isMain) {
                throw new BadRequestHttpException('上传管理分类参数有误');
            } else {
                throw new BadRequestHttpException('上传商品分类参数有误');
            }
        }
        return $catId;
    }

    /**
     * 通过品牌名称获取品牌ID
     */
    private function getBrandId($companyId, $row)
    {
        $brandName = $row['goods_brand'] ?? "";
        $brandId = 0;
        if ($brandName) {
            $itemsAttributesService = new ItemsAttributesService();
            $data = $itemsAttributesService->getInfo([
                'company_id' => $companyId,
                'attribute_name' => $brandName,
                'attribute_type' => 'brand',
            ]);
            if (!$data) {
                throw new BadRequestHttpException($brandName . ' 品牌名称不存在');
            }
            $brandId = $data['attribute_id'];
        }
        return $brandId;
    }

    /**
     * 获取商品参数
     *
     * item_params: 功效:美白提亮|性别:男性
     */
    private function getItemParams($companyId, $row)
    {
        $data = [];
        if ($row['item_params']) {
            $itemsAttributesService = new ItemsAttributesService();
            $itemParams = explode('|', $row['item_params']);
            foreach ($itemParams as $row) {
                $itemRow = explode(':', $row);
                $attributeNames[] = $itemRow[0];
                $attributeValues[] = $itemRow[1];
            }

            $attrList = $itemsAttributesService->lists([
                'company_id' => $companyId,
                'attribute_name' => $attributeNames,
                'attribute_type' => 'item_params',
            ]);
            if ($attrList['total_count'] > 0) {
                $attributeids = array_column($attrList['list'], 'attribute_id');
            } else {
                throw new BadRequestHttpException('商品参数不存在');
            }

            $attrValuesList = $itemsAttributesService->getAttrValuesListBy([
                'company_id' => $companyId,
                'attribute_value' => $attributeValues,
                'attribute_id' => $attributeids,
            ]);
            if ($attrValuesList['total_count'] > 0) {
                foreach ($attrValuesList['list'] as $row) {
                    $data[] = [
                        'attribute_id' => $row['attribute_id'],
                        'attribute_value_id' => $row['attribute_value_id'],
                    ];
                }
            } else {
                throw new BadRequestHttpException('商品参数值不存在');
            }
        }

        return $data;
    }

    private function getItemSpec($companyId, $row)
    {
        $data = [];
        if ($row['item_spec']) {
            $itemsAttributesService = new ItemsAttributesService();
            $itemParams = explode('|', $row['item_spec']);
            foreach ($itemParams as $row) {
                $itemRow = explode(':', $row);
                if (!$itemRow[0]) {
                    throw new BadRequestHttpException('存在无效的商品规格');
                }
                if (!$itemRow[1]) {
                    throw new BadRequestHttpException('存在无效的商品规格值');
                }
                $attributeNames[] = $itemRow[0];
                $attributeValues[] = $itemRow[1];
            }

            $attrList = $itemsAttributesService->lists([
                'company_id' => $companyId,
                'attribute_name' => $attributeNames,
                'attribute_type' => 'item_spec',
            ]);
            if ($attrList['total_count'] == count($attributeNames)) {
                $attributeids = array_column($attrList['list'], 'attribute_id');
            } else {
                throw new BadRequestHttpException('存在无效的商品规格');
            }

            $attrValuesList = $itemsAttributesService->getAttrValuesListBy([
                'company_id' => $companyId,
                'attribute_value' => $attributeValues,
                'attribute_id' => $attributeids,
            ]);
            if ($attrValuesList['total_count'] == count($attributeValues)) {
                foreach ($attrValuesList['list'] as $row) {
                    $data[] = [
                        'spec_id' => $row['attribute_id'],
                        'spec_value_id' => $row['attribute_value_id'],
                    ];
                }
            } else {
                throw new BadRequestHttpException('存在无效的商品规格值');
            }
        }

        return $data;
    }

    /**
     * Notes: 动态获取 活动商品分类的 数据，作为填写说明可选项
     * Author:Michael-Ma
     * Date:  2020年03月31日 18:11:10
     *
     * @return array
     */
    private function getHeaderInfo()
    {
        $result = $this->headerInfo;
        $company_id = app('auth')->user()->get('company_id');
        $community_config_redis_key = 'community_config_redis_key:' . $company_id;
        $community_config = app('cache')->remember($community_config_redis_key, 3, function () use ($company_id) {
            return (new SettingService())->getInfo([
                    'company_id' => $company_id,
                ])['community_config'] ?? '';
        });

        $activity_goods_category_label = $community_config['activity_goods_category_label'] ?? [];
        foreach ($activity_goods_category_label as $v) {
            $result += [
                '分类可选项【' . $v['label'] . '】' => [
                    'size' => 5,
                    'remarks' => $v['value'],
                    'is_need' => false,
                ],
            ];
        }

        $activity_hours_config = $community_config['activity_hours']['activity_hours_config'] ?? [];
        foreach ($activity_hours_config as $k => $v) {
            $index = $v['start_time'] . '~' . $v['end_time'];
            $result += [
                $k . '可售时段【' . $index . '】' => [
                    'size' => 2,
                    'remarks' => $v['remarks'],
                    'is_need' => false,
                ],
            ];
        }

        return $result;
    }
}
