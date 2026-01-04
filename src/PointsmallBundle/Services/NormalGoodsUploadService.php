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

namespace PointsmallBundle\Services;

use OrdersBundle\Services\ShippingTemplatesService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;
use GoodsBundle\Services\ItemsAttributesService;
use GoodsBundle\Services\ItemsCategoryService;

class NormalGoodsUploadService
{
    public $itemName = null;

    public $defaultItemId = null;

    public $header = [
        '管理分类' => 'item_main_category',
        '商品名称' => 'item_name',
        '商品编码' => 'item_bn',
        '简介' => 'brief',
        // '商品价格' => 'price',
        '市场价' => 'market_price',
        '成本价' => 'cost_price',
        '积分价格' => 'point',
        '库存' => 'store',
        '图片' => 'pics',
        '视频' => 'videos',
        '品牌' => 'goods_brand',
        '运费模板' => 'templates_id',
        '分类' => 'item_category',
        '重量' => 'weight',
        '条形码' => 'barcode',
        '单位' => 'item_unit',
        '规格值' => 'item_spec',
        '参数值' => 'item_params',
    ];

    public $headerInfo = [
        '管理分类' => ['size' => 255, 'remarks' => '类目名称，一级类目->二级类目->三级类目', 'is_need' => true],
        '商品名称' => ['size' => 255, 'remarks' => '', 'is_need' => true],
        '商品编码' => ['size' => 32, 'remarks' => '', 'is_need' => false],
        '简介' => ['size' => 20, 'remarks' => '', 'is_need' => false],
        // '商品价格' => ['size' => 255, 'remarks' => '单位为(元)，最多两位小数', 'is_need' => true],
        '市场价' => ['size' => 255, 'remarks' => '单位为(元)，最多两位小数', 'is_need' => false],
        '成本价' => ['size' => 255, 'remarks' => '单位为(元)，最多两位小数', 'is_need' => false],
        '积分价格' => ['size' => 255, 'remarks' => '', 'is_need' => true],
        '图片' => ['size' => 255, 'remarks' => '多个图片使用英文逗号隔开，最多上传9个', 'is_need' => false],
        '视频' => ['size' => 255, 'remarks' => '在视频素材复制对应的ID', 'is_need' => false],
        '库存' => ['size' => 255, 'remarks' => '库存为0-999999999的整数', 'is_need' => true],
        '品牌' => ['size' => 255, 'remarks' => '已有的品牌名称', 'is_need' => false],
        '运费模板' => ['size' => 255, 'remarks' => '运费模板名称', 'is_need' => true],
        '分类' => ['size' => 255, 'remarks' => '分类名称，一级分类->二级分类|一级分类->二级分类->三级分类 多个二级三级分类使用|隔开', 'is_need' => true],
        '重量' => ['size' => 255, 'remarks' => '商品重量，单位KG', 'is_need' => false],
        '条形码' => ['size' => 255, 'remarks' => '条形码', 'is_need' => false],
        '单位' => ['size' => 255, 'remarks' => '单位', 'is_need' => false],
        '规格值' => ['size' => 255, 'remarks' => '例如：颜色:红色|尺码:20cm', 'is_need' => false],
        '参数值' => ['size' => 255, 'remarks' => '例如：系列:生机展颜|功效:美白提亮', 'is_need' => false],
    ];

    public $isNeedCols = [
        '商品名称' => 'item_name',
        // '商品价格' => 'price',
        '积分价格' => 'point',
        '库存' => 'store',
        '运费模板' => 'templates_id',
        '分类' => 'item_category',
        '管理分类' => 'item_main_category',
        '图片' => 'pics',
    ];
    public $tmpTarget = null;

    /**
     * 验证上传的实体商品信息
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('实体商品信息上传只支持Excel文件格式(xlsx)');
        }
    }

    /**
     * getFilePath function
     *
     * @return void
     */
    public function getFilePath($filePath, $fileExt = '')
    {
        $url = $this->getFileSystem()->privateDownloadUrl($filePath);

        //兼容本地文件存储
        if (strtolower(substr($url, 0, 4)) != 'http') {
            $url = storage_path('uploads').'/'.$filePath;
            $content = file_get_contents($url);
        } else {
            $client = new Client();
            $content = $client->get($url)->getBody()->getContents();
        }

        $this->tmpTarget = tempnam('/tmp', 'import-file') . $fileExt;
        file_put_contents($this->tmpTarget, $content);

        return $this->tmpTarget;
    }

    public function getFileSystem()
    {
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
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }

    public function handleRow($companyId, $row)
    {
        $validatorData = $this->validatorData($row);
        $rules = [
            'item_name' => ['required', '请填写商品名称'],
            // 'price' => ['required', '请填写价格'],
            'point' => ['required', '请填写正确的积分价格'],
            'store' => ['required|integer|min:0|max:999999999', '库存为0-999999999的整数'],
            'templates_id' => ['required', '请填写运费模板'],
        ];
        $errorMessage = validator_params($validatorData, $rules, false);
        if ($errorMessage) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException($msg);
        }
        $nospec = $row['item_spec'] ? false : true;
        //表示为多规格，并且已经存储了默认商品，所以只需要新增当前商品数据，通用关联数据不需要更新，例如：商品关联的分类，关联的品牌等
        if (($nospec === false || $nospec === 'false') && $this->itemName && trim($row['item_name']) == $this->itemName) {
            $isCreateRelData = false;
            $defaultItemId = $this->defaultItemId;
        } else {
            $isCreateRelData = true;
            $defaultItemId = null;
        }

        $itemsService = new ItemsService();

        //2021新增，支持导入更新商品数据
        $row['goods_id'] = false;
        $row['item_id'] = false;
        if ($row['item_bn']) {
            $filter = ['item_bn' => $row['item_bn'], 'company_id' => $companyId];
            $itemInfo = $itemsService->getItem($filter);
            if ($itemInfo) {
                $row['goods_id'] = $itemInfo['goods_id'];
                $row['item_id'] = $itemInfo['item_id'];
                $defaultItemId = $itemInfo['default_item_id'];//更新商品的时候，不改变默认主规格ID
                $isCreateRelData = $itemInfo['is_default'];
            }
        }

        $mainCategory = $this->getItemMainCategoryId($companyId, $row);//获取主类目信息

        $itemInfo = [
            'company_id' => $companyId,
            'item_type' => 'normal',
            'item_name' => trim($row['item_name']),
            'brief' => trim($row['brief']),
            'sort' => 1,
            'templates_id' => $this->getTemplatesId($companyId, $row),
            'pics' => explode(',', $row['pics']),
            'videos' => $row['videos'],
            'nospec' => $nospec,
            'item_category' => $this->getItemCategory($companyId, $row),
            'item_main_cat_id' => $mainCategory['category_id'],
            'brand_id' => $this->getBrandId($companyId, $row),
            'item_params' => $this->getItemParams($companyId, $row),
            'item_bn' => $row['item_bn'],
            'weight' => $row['weight'],
            'barcode' => $row['barcode'],
            // 'price' => $row['price'],
            'price' => 0,
            'cost_price' => $row['cost_price'],
            'market_price' => $row['market_price'],
            'point' => $row['point'],
            'item_unit' => $row['item_unit'],
            'store' => $row['store'],
            'approve_status' => 'onsale',
            'is_default' => $isCreateRelData,
            'intro' => '',
        ];

        if ($nospec === false || $nospec === 'false') {
            $specItem = [
                'item_bn' => $row['item_bn'],
                'weight' => $row['weight'],
                'barcode' => $row['barcode'],
                'price' => 0,
                'cost_price' => $row['cost_price'],
                'market_price' => $row['market_price'],
                'point' => $row['point'],
                'item_unit' => $row['item_unit'],
                'store' => $row['store'],
                'approve_status' => 'onsale',
                'is_default' => $isCreateRelData,
                'item_spec' => $this->getItemSpec($companyId, $row, $mainCategory),
            ];
            if ($row['item_id']) {
                $specItem['item_id'] = $row['item_id'];
            }
            $specItems[] = $specItem;
            $itemInfo['spec_items'] = json_encode($specItems);
        }

        if ($row['goods_id']) {
            $itemInfo['goods_id'] = $row['goods_id'];
        }

        if ($row['item_id']) {
            $itemInfo['item_id'] = $row['item_id'];
        }

        if ($defaultItemId) {
            $itemInfo['default_item_id'] = $defaultItemId;
        }

        try {
            $result = $itemsService->addItems($itemInfo, $isCreateRelData);
            if ($isCreateRelData) {
                $this->defaultItemId = $result['item_id'];
                $this->itemName = trim($row['item_name']);
            }
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }
    }

    private function validatorData($row)
    {
        $arr = ['item_name', 'point', 'store', 'templates_id'];
        // $arr = ['item_name', 'price', 'point', 'store', 'templates_id'];
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
     * 获取管理分类
     *
     * @param int $companyId
     * @param array $row
     */
    private function getItemMainCategoryId($companyId = 0, &$row = [])
    {
        $categoryInfo = [];
        $splitChar = '->';
        $mainCategory = $row['item_main_category'];
        if (!$mainCategory) {
            throw new BadRequestHttpException('请上传管理分类');
        }
        $catNamesArr = explode($splitChar, $mainCategory);
        if (count($catNamesArr) != 3) {
            throw new BadRequestHttpException('上传管理分类必须是三层级,'.$mainCategory);
        }

        $itemsCategoryService = new ItemsCategoryService();
        $lists = $itemsCategoryService->lists(['company_id' => $companyId, 'category_name' => $catNamesArr, 'is_main_category' => 1]);
        if ($lists['total_count'] <= 0) {
            throw new BadRequestHttpException('上传管理分类不存在,'.$mainCategory);
        }

        $categoryName = array_column($lists['list'], 'category_name', 'category_id');
        foreach ($lists['list'] as $v) {
            if (!$v['path']) {
                continue;
            }
            $paths = explode(',', $v['path']);
            $pathName = [];
            foreach ($paths as $id) {
                if (!isset($categoryName[$id])) {
                    continue;
                }
                $pathName[] = $categoryName[$id];
            }
            //根据路径判断，找到一样的为止
            if (implode($splitChar, $pathName) == $mainCategory) {
                $categoryInfo = $v;
                break;
            }
        }

        if (!$categoryInfo) {
            throw new BadRequestHttpException('无法识别的管理分类,'.$mainCategory);
        }

        return $categoryInfo;
    }

    /**
     * 获取商品分类
     */
    private function getItemCategory($companyId, $row)
    {
        $category = $row['item_category'];

        if ($category) {
            $catNames = explode('|', $category);
        } else {
            throw new BadRequestHttpException('请上传商品分类');
        }

        $catNamesArr = array();
        foreach ($catNames as $catNameRow) {
            $catNamesArr = array_merge($catNamesArr, explode('->', $catNameRow));
        }

        $itemsCategoryService = new ItemsCategoryService();
        // 数据结构买办法判断获取的分类ID是否最子级分类，三级分类改造后在优化
        $lists = $itemsCategoryService->lists(['company_id' => $companyId, 'category_name' => $catNamesArr, 'is_main_category' => 0]);
        if ($lists['total_count'] <= 0) {
            throw new BadRequestHttpException('上传商品分类参数有误');
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
            throw new BadRequestHttpException('上传商品分类参数有误');
        }
        $catId = [];
        foreach ($lists['list'] as $catRow) {
            $parentArr = [];
            if ($catRow['category_level'] == '3') {
                $parentArr = explode(',', $catRow['path']);
                unset($parentArr[2]);
            } elseif ($catRow['category_level'] == '2') {
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
            if ($parentArr && $parentArr == array_intersect($parentArr, $parentIds)) {
                $catId[] = $catRow['category_id'];
            }
        }
        if (!$catId) {
            throw new BadRequestHttpException('上传商品分类参数有误');
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
            $data = $itemsAttributesService->getInfo(['company_id' => $companyId, 'attribute_name' => $brandName, 'attribute_type' => 'brand']);
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

            $attrList = $itemsAttributesService->lists(['company_id' => $companyId, 'attribute_name' => $attributeNames, 'attribute_type' => 'item_params']);
            if ($attrList['total_count'] > 0) {
                $attributeids = array_column($attrList['list'], 'attribute_id');
            } else {
                throw new BadRequestHttpException('商品参数不存在');
            }

            $attrValuesList = $itemsAttributesService->getAttrValuesListBy(['company_id' => $companyId, 'attribute_value' => $attributeValues, 'attribute_id' => $attributeids]);
            if ($attrValuesList['total_count'] > 0) {
                foreach ($attrValuesList['list'] as $row) {
                    $data[] = [
                        'attribute_id' => $row['attribute_id'],
                        'attribute_value_id' => $row['attribute_value_id']
                    ];
                }
            } else {
                throw new BadRequestHttpException('商品参数值不存在');
            }
        }

        return $data;
    }

    private function getItemSpec($companyId, $row, $mainCategory = [])
    {
        $data = [];
        $specInfo = [];
        if ($row['item_spec']) {
            //根据主类目获取商品规格属性的排序
            $goodsSpecIds = $mainCategory['goods_spec'];

            $itemsAttributesService = new ItemsAttributesService();
            $itemParams = explode('|', $row['item_spec']);
            foreach ($itemParams as $row) {
                $itemRow = explode(':', $row);
                if (count($itemRow) != 2) {
                    throw new BadRequestHttpException('商品规格格式错误。例如：颜色:红色|尺码:20cm');
                }
                if (!$itemRow[0]) {
                    throw new BadRequestHttpException('存在无效的商品规格');
                }
                if (!$itemRow[1]) {
                    throw new BadRequestHttpException('存在无效的商品规格值');
                }
                $attributeNames[] = $itemRow[0];
                $attributeValues[] = $itemRow[1];
            }

            $attrList = $itemsAttributesService->lists(['company_id' => $companyId, 'attribute_id' => $goodsSpecIds, 'attribute_name' => $attributeNames, 'attribute_type' => 'item_spec']);
            if ($attrList['total_count'] == count($attributeNames)) {
                $attributeids = array_column($attrList['list'], 'attribute_id');
            } else {
                throw new BadRequestHttpException('存在无效的商品规格');
            }

            $attrValuesList = $itemsAttributesService->getAttrValuesListBy(['company_id' => $companyId, 'attribute_value' => $attributeValues, 'attribute_id' => $attributeids]);
            if ($attrValuesList['total_count'] == count($attributeValues)) {
                foreach ($attrValuesList['list'] as $row) {
                    $data[$row['attribute_id']] = [
                        'spec_id' => $row['attribute_id'],
                        'spec_value_id' => $row['attribute_value_id']
                    ];
                }
            } else {
                throw new BadRequestHttpException('存在无效的商品规格值');
            }

            //排序
            foreach ($goodsSpecIds as $specId) {
                if (isset($data[$specId])) {
                    $specInfo[] = $data[$specId];
                }
            }
        }

        return $data;
    }

    public function getDemoData()
    {
        $data = [
            [
                'item_main_category' => 'HP->上衣->HP',
                'item_name' => '上衣外套',
                'item_bn' => 'S61401E3179BB6',
                'brief' => '1',
                'point' => '0.01',
                'market_price' => '0',
                'cost_price' => '0',
                'member_price' => '',
                'store' => '99',
                'pics' => '',
                'videos' => '',
                'goods_brand' => '云店',
                'templates_id' => '0.01',
                'item_category' => '食品副食->咸味食品->屏幕故障',
                'weight' => '0',
                'barcode' => '',
                'item_unit' => '',
                'item_spec' => '',
                'item_params' => '',
            ],
            [
                'item_main_category' => '新鲜零食->新品６折->休闲食品',
                'item_name' => '云店特产',
                'item_bn' => 'S6139A88AB7181',
                'brief' => '多种糕点 设计师原创插画包装',
                'point' => '309',
                'market_price' => '599',
                'cost_price' => '100',
                'member_price' => '',
                'store' => '1000',
                'pics' => 'https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/e256a607a5914062d406916bc22b1465BRHbeKZOlgTyIDnPmitVXJESiSXLCaDk,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89c74bf83949f6f67e989f7f48aa12baezCydrvcrV2bSoxPK5YWlDLrxVSZQrsh,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89f2b37de9f030ee4a39b57acb68e34bk6Gluazl1KoKiucCoXU2W9HvhAxzaGf9',
                'videos' => '',
                'goods_brand' => '云店',
                'templates_id' => '快递包邮',
                'item_category' => '新品6折->糕点面包|新品6折->休闲食品',
                'weight' => '0',
                'barcode' => '',
                'item_unit' => '',
                'item_spec' => '口味:芒果味',
                'item_params' => '',
            ],
            [
                'item_main_category' => '新鲜零食->新品６折->休闲食品',
                'item_name' => '云店特产',
                'item_bn' => 'S6139A88AAE005',
                'brief' => '多种糕点 设计师原创插画包装',
                'point' => '309',
                'market_price' => '599',
                'cost_price' => '100',
                'member_price' => '',
                'store' => '1000',
                'pics' => 'https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/e256a607a5914062d406916bc22b1465BRHbeKZOlgTyIDnPmitVXJESiSXLCaDk,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89c74bf83949f6f67e989f7f48aa12baezCydrvcrV2bSoxPK5YWlDLrxVSZQrsh,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89f2b37de9f030ee4a39b57acb68e34bk6Gluazl1KoKiucCoXU2W9HvhAxzaGf9',
                'videos' => '',
                'goods_brand' => '云店',
                'templates_id' => '快递包邮',
                'item_category' => '新品6折->糕点面包|新品6折->休闲食品',
                'weight' => '0',
                'barcode' => '',
                'item_unit' => '',
                'item_spec' => '口味:巧克力味',
                'item_params' => '',
            ],
            [
                'item_main_category' => '新鲜零食->新品６折->休闲食品',
                'item_name' => '云店特产',
                'item_bn' => 'S6139A88AA1930',
                'brief' => '多种糕点 设计师原创插画包装',
                'point' => '309',
                'market_price' => '599',
                'cost_price' => '100',
                'member_price' => '',
                'store' => '1000',
                'pics' => 'https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/e256a607a5914062d406916bc22b1465BRHbeKZOlgTyIDnPmitVXJESiSXLCaDk,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89c74bf83949f6f67e989f7f48aa12baezCydrvcrV2bSoxPK5YWlDLrxVSZQrsh,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89f2b37de9f030ee4a39b57acb68e34bk6Gluazl1KoKiucCoXU2W9HvhAxzaGf9',
                'videos' => '',
                'goods_brand' => '云店',
                'templates_id' => '快递包邮',
                'item_category' => '新品6折->糕点面包|新品6折->休闲食品',
                'weight' => '0',
                'barcode' => '',
                'item_unit' => '',
                'item_spec' => '口味:烧烤味',
                'item_params' => '',
            ],
            [
                'item_main_category' => '新鲜零食->新品６折->休闲食品',
                'item_name' => '云店特产',
                'item_bn' => 'S6139A88A987F2',
                'brief' => '多种糕点 设计师原创插画包装',
                'point' => '309',
                'market_price' => '599',
                'cost_price' => '100',
                'member_price' => '',
                'store' => '1000',
                'pics' => 'https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/e256a607a5914062d406916bc22b1465BRHbeKZOlgTyIDnPmitVXJESiSXLCaDk,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89c74bf83949f6f67e989f7f48aa12baezCydrvcrV2bSoxPK5YWlDLrxVSZQrsh,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89f2b37de9f030ee4a39b57acb68e34bk6Gluazl1KoKiucCoXU2W9HvhAxzaGf9',
                'videos' => '',
                'goods_brand' => '云店',
                'templates_id' => '快递包邮',
                'item_category' => '新品6折->糕点面包|新品6折->休闲食品',
                'weight' => '0',
                'barcode' => '',
                'item_unit' => '',
                'item_spec' => '口味:香辣味',
                'item_params' => '',
            ],
            [
                'item_main_category' => '新鲜零食->新品６折->休闲食品',
                'item_name' => '云店特产',
                'item_bn' => 'S6139A88A8C356',
                'brief' => '多种糕点 设计师原创插画包装',
                'point' => '309',
                'market_price' => '599',
                'cost_price' => '100',
                'member_price' => '',
                'store' => '999',
                'pics' => 'https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/e256a607a5914062d406916bc22b1465BRHbeKZOlgTyIDnPmitVXJESiSXLCaDk,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89c74bf83949f6f67e989f7f48aa12baezCydrvcrV2bSoxPK5YWlDLrxVSZQrsh,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89f2b37de9f030ee4a39b57acb68e34bk6Gluazl1KoKiucCoXU2W9HvhAxzaGf9',
                'videos' => '',
                'goods_brand' => '云店',
                'templates_id' => '快递包邮',
                'item_category' => '新品6折->糕点面包|新品6折->休闲食品',
                'weight' => '0',
                'barcode' => '',
                'item_unit' => '',
                'item_spec' => '口味:混合口味',
                'item_params' => '',
            ],
            [
                'item_main_category' => '新鲜零食->新品６折->休闲食品',
                'item_name' => '云店特产',
                'item_bn' => 'S6139A88A7BF0F',
                'brief' => '多种糕点 设计师原创插画包装',
                'point' => '309',
                'market_price' => '599',
                'cost_price' => '100',
                'member_price' => '',
                'store' => '1000',
                'pics' => 'https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/e256a607a5914062d406916bc22b1465BRHbeKZOlgTyIDnPmitVXJESiSXLCaDk,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89c74bf83949f6f67e989f7f48aa12baezCydrvcrV2bSoxPK5YWlDLrxVSZQrsh,https://b-img-cdn.yuanyuanke.cn/image/21/2021/09/09/89f2b37de9f030ee4a39b57acb68e34bk6Gluazl1KoKiucCoXU2W9HvhAxzaGf9',
                'videos' => '',
                'goods_brand' => '云店',
                'templates_id' => '快递包邮',
                'item_category' => '新品6折->糕点面包|新品6折->休闲食品',
                'weight' => '0',
                'barcode' => '',
                'item_unit' => '',
                'item_spec' => '口味:原味',
                'item_params' => '',
            ],
            [],
            [
                'item_main_category' => '',
                'item_name' => '',
                'item_bn' => '',
                'brief' => '2为一条单规格商品，3-8为一条多规格商品  此文件为DOEM模拟数据，使用该模板请删除本句，重新修改数据。'
            ]
        ];
        $header = array_flip($this->header);
        $column = [];
        foreach ($header as $key => $value) {
            $column[$key] = '';
        }
        $result = [];
        foreach ($data as $key1 => $value1) {
            $tmpData = $column;
            foreach ($value1 as $itemKey => $item) {
                $tmpData[$itemKey] = $item;
            }
            $result[] = array_values($tmpData);
        }
        return $result;
    }
}
