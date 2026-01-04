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

namespace SupplierBundle\Services;

use GoodsBundle\Services\ItemsAttributesService;
use GoodsBundle\Services\ItemsCategoryService;
use OrdersBundle\Entities\ShippingTemplates;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;

class SupplierGoodsUploadService
{
    public $memberPriceHeaderReady = false;//会员价表头已经加载

    public $itemName = null;

    public $defaultItemId = null;

    public $header = [
        '管理分类' => 'item_main_category',
        '商品名称' => 'item_name',
        'SPU编码' => 'goods_bn',
        'SKU编码' => 'item_bn',
        // '供应商货号' => 'supplier_goods_bn',
        '简介' => 'brief',
        '销售价' => 'price',
        '市场价' => 'market_price',
        '成本价' => 'cost_price',
        '起订量' => 'start_num',
        '库存' => 'store',
        '头图' => 'pics',
        '详情图' => 'intro',
        '规格图' => 'item_spec_pics',
        '视频' => 'videos',
        '品牌' => 'goods_brand',
        '运费模板' => 'templates_id',
        '销售分类' => 'item_category',
        '重量' => 'weight',
        '条形码' => 'barcode',
        '单位' => 'item_unit',
        '规格值' => 'item_spec',
        '参数值' => 'item_params',
        '供应状态' => 'is_market'
    ];

    public $headerInfo = [
        '管理分类' => ['size' => 255, 'remarks' => '类目名称，一级类目->二级类目->三级类目', 'is_need' => true],
        '商品名称' => ['size' => 255, 'remarks' => '', 'is_need' => true],
        'SPU编码' => ['size' => 32, 'remarks' => '平台唯一自动生成', 'is_need' => false],
        'SKU编码' => ['size' => 32, 'remarks' => '平台唯一自动生成	', 'is_need' => false],
        // '供应商货号' => ['size' => 32, 'remarks' => '', 'is_need' => false],
        '简介' => ['size' => 20, 'remarks' => '', 'is_need' => false],
        '销售价' => ['size' => 255, 'remarks' => '单位为(元)，最多两位小数', 'is_need' => true],
        '市场价' => ['size' => 255, 'remarks' => '单位为(元)，最多两位小数', 'is_need' => false],
        '成本价' => ['size' => 255, 'remarks' => '单位为(元)，最多两位小数', 'is_need' => false],
        '起订量' => ['size' => 255, 'remarks' => '起订量', 'is_need' => false],
        '库存' => ['size' => 255, 'remarks' => '库存为0-999999999的整数', 'is_need' => true],
        '头图' => ['size' => 255, 'remarks' => '多个图片使用英文逗号隔开，最多上传9个', 'is_need' => false],
        '详情' => ['size' => 255, 'remarks' => '多个图片使用英文逗号隔开', 'is_need' => false],
        '规格图' => ['size' => 255, 'remarks' => '多个图片使用英文逗号隔开，最多上传5个', 'is_need' => false],
        '视频' => ['size' => 255, 'remarks' => '在视频素材复制对应的ID', 'is_need' => false],
        '品牌' => ['size' => 255, 'remarks' => '已有的品牌名称', 'is_need' => false],
        '运费模板' => ['size' => 255, 'remarks' => '运费模板名称', 'is_need' => true],
        '销售分类' => ['size' => 255, 'remarks' => '分类名称，一级分类->二级分类|一级分类->二级分类>三级分类 多个二级三级分类使用|隔开', 'is_need' => true],
        '重量' => ['size' => 255, 'remarks' => '商品重量，单位KG', 'is_need' => false],
        '条形码' => ['size' => 255, 'remarks' => '条形码', 'is_need' => false],
        '单位' => ['size' => 255, 'remarks' => '单位', 'is_need' => false],
        '规格值' => ['size' => 255, 'remarks' => '例如：颜色:红色|尺码:20cm，必须和管理分类一起导入', 'is_need' => false],
        '参数值' => ['size' => 255, 'remarks' => '例如：系列:生机展颜|功效:美白提亮', 'is_need' => false],
        '供应状态' => ['size' => 30, 'remarks' => '可售，不可售', 'is_need' => false],
    ];

    public $allIsMarket = [
        '可售' => 1,
        '不可售' => 0,
    ];

    public $isNeedCols = [
        '管理分类' => 'item_main_category',
        '商品名称' => 'item_name',
        '销售价' => 'price',
        '库存' => 'store',
        '运费模板' => 'templates_id',
        '销售分类' => 'item_category',
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
     * @return string
     */
    public function getFilePath($filePath, $fileExt = '')
    {
        //兼容本地文件存储
        if (env('DISK_DRIVER', 'qiniu') == 'local') {
            $content = $this->getFileSystem()->get($filePath);
        } else {
            $url = $this->getFileSystem()->privateDownloadUrl($filePath);
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

    /**
     * 设置会员价导入头信息
     *
     * @param string $memberPriceKey
     * @param array $userGrade
     * @param array $vipGrade
     */
    private function _setHeader($userGrade = [], $vipGrade = [])
    {
        $newHeader = [];
        foreach ($this->header as $k => $v) {
            if ($k != self::MEMBER_PRICE_KEY) {
                $newHeader[$k] = $v;
                continue;
            }

            foreach ($userGrade as $grade) {
                $newHeader[$grade['grade_name']] = 'grade_price' . $grade['grade_id'];
            }

            foreach ($vipGrade as $grade) {
                $newHeader[$grade['grade_name']] = 'vipGrade_price' . $grade['vip_grade_id'];
            }
        }

        $this->header = $newHeader;
    }

    public function handleRow($companyId, $row)
    {
        //app('log')->debug("\n _uploadItems handleRow =>:".json_encode($row, 256));
        //支持导入更新商品数据
        $row['goods_id'] = false;
        $row['item_id'] = false;
        $row['distributor_id'] = 0;
        $row['supplier_id'] = $row['supplier_id'] ?? 0;//供应商ID
        $row['operator_type'] = $row['supplier_id'] ? 'supplier' : '';//是否供应商的操作
        if ($row['item_bn']) {
            $filter = ['item_bn' => $row['item_bn'], 'company_id' => $companyId];
            $supplierItemsService = new SupplierItemsService();
            $oldItemInfo = $supplierItemsService->repository->getInfo($filter);
            if ($oldItemInfo) {
                $row['default_item_id'] = $oldItemInfo['default_item_id'];
                $row['goods_id'] = $oldItemInfo['goods_id'];
                $row['item_id'] = $oldItemInfo['item_id'];//如果存在，更新商品数据
                if ($row['supplier_id'] != $oldItemInfo['supplier_id']) {
                    throw new BadRequestHttpException('商品编码已存在其他供应商，不能更新');
                }
                $this->updateGoods($companyId, $row, $oldItemInfo);
                return;
            }
        }

        $this->createGoods($companyId, $row);
    }

    private function createGoods($companyId, $row)
    {
        $SupplierItemsService = new SupplierItemsService();
        $validatorData = $this->validatorData($row);

        $rules = [
            'item_name' => ['required', '请填写商品名称'],
            // 'supplier_goods_bn' => ['required', '请填写供应商货号'],
            'price' => ['required', '请填写价格'],
            // 'market_price' => ['required', '请填写市场价'],
            // 'cost_price' => ['required', '请填写结算价'],
            //            'store' => ['required|integer|min:0|max:999999999', '库存为0-999999999的整数'],
            'templates_id' => ['required', '请填写运费模板'],
        ];
        if ($row['supplier_id']) {
            $rules['cost_price'] = ['required', '请填写成本价'];
            $rules['is_market'] = ['required', '请填写供应状态'];
        }
        $errorMessage = validator_params($validatorData, $rules, false);
        if (intval($row['store']) < 0 || intval($row['store']) > 999999999) {
            $errorMessage[] = '库存为0-999999999的整数';
        }
        if ($errorMessage) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException($msg);
        }

        $nospec = $row['item_spec'] ? false : true;
        //表示为多规格，并且已经存储了默认商品，所以只需要新增当前商品数据，通用关联数据不需要更新，例如：商品关联的分类，关联的品牌等
        if (($nospec === false || $nospec === 'false' || $nospec === 0 || $nospec === '0') && $this->itemName && trim($row['item_name']) == $this->itemName) {
            $isCreateRelData = false;
            $defaultItemId = $this->defaultItemId;
        } else {
            $isCreateRelData = true;
            $defaultItemId = null;
        }
        $row['default_item_id'] = $defaultItemId;

        // $profitType = 0;
        // $profitFee = 0;
        // if (!in_array(intval($row['is_profit']), [0, 1])) {
        //     throw new BadRequestHttpException('是否支持分润参数错误');
        // }
        // $row['profit_type'] = intval($row['profit_type']);

        $mainCategory = $this->getItemMainCategoryId($companyId, $row);//获取主类目信息

        // $isProfit = intval($row['is_profit']);

        $itemInfo = [
            'company_id' => $companyId,
            'supplier_id' => $row['supplier_id'],
            'operator_type' => $row['operator_type'],
            'item_main_cat_id' => $mainCategory['category_id'],
            'item_name' => trim($row['item_name']),
            'item_bn' => trim($row['item_bn']),
            'goods_bn' => trim($row['goods_bn']),
            // 'supplier_goods_bn' => trim($row['supplier_goods_bn']),
            'brief' => trim($row['brief']),
            'price' => floatval($row['price']),
            'cost_price' => floatval($row['cost_price']),
            'market_price' => floatval($row['market_price']),
            'store' => $row['store'],
            'pics' => $row['pics'] ? explode(',', $row['pics']) : [],
            'intro' => $this->getIntro($row),
            'videos' => $row['videos'],
            'item_category' => $this->getItemCategoryNew($companyId, $row, false),
            'brand_id' => $this->getBrandId($companyId, $row),
            'templates_id' => $this->getTemplatesId($companyId, $row),
            'weight' => trim($row['weight']),
            'barcode' => trim($row['barcode']),
            'item_unit' => $row['item_unit'],
            'item_params' => $this->getItemParams($companyId, $row),
            'nospec' => $nospec,
            'is_default' => $isCreateRelData,
            //'is_profit' => intval($row['is_profit']) ?? 0,
            // 'is_profit' => ($isProfit == 1) ? 'true' : 'false',
            // 'profit_type' => $profitType,
            // 'profit_fee' => $profitFee,
            'item_type' => 'normal',
            'sort' => 1,
            'approve_status' => 'onsale',
            'distributor_id' => $row['distributor_id'],
            'start_num' => isset($row['start_num']) && $row['start_num'] > 0 ? $row['start_num'] : 0, // 起订量
        ];

        // 商品上下架状态，默认为 onsale
        // if (isset($row['approve_status']) && isset($this->allApproveStatus[$row['approve_status']])) {
        //     $itemInfo['approve_status'] = $this->allApproveStatus[$row['approve_status']];
        // }

        // if (isset($row['audit_status']) && isset($this->allauditStatus[$row['audit_status']])) {
        //     $itemInfo['audit_status'] = $this->allauditStatus[$row['audit_status']];
        // }

        // if (isset($row['delivery_data_type']) && isset($this->allDeliveryDataType[$row['delivery_data_type']])) {
        //     $itemInfo['delivery_data_type'] = $this->allDeliveryDataType[$row['delivery_data_type']];
        // }

        if (isset($row['is_market']) && isset($this->allIsMarket[$row['is_market']])) {
            $itemInfo['is_market'] = $this->allIsMarket[$row['is_market']];
        }

        if ($row['supplier_id']) {
            // $itemInfo['approve_status'] = $itemInfo['is_market'] ? 'onsale' : 'instock';
            $itemInfo['audit_status'] = 'submiting';//供应商导入的商品默认变成待提交审核
        }

        if ($nospec === false || $nospec === 'false' || $nospec === 0 || $nospec === '0') {
            $specItem = [
                'item_bn' => trim($row['item_bn']),
                'weight' => $row['weight'],
                'barcode' => trim($row['barcode']),
                // 'supplier_goods_bn' => trim($row['supplier_goods_bn']),
                'price' => $row['price'],
                'cost_price' => $row['cost_price'],
                'market_price' => $row['market_price'],
                'item_unit' => $row['item_unit'],
                'store' => $row['store'],
                'is_default' => $isCreateRelData,
                'default_item_id' => $defaultItemId,
                'item_spec' => $this->getItemSpec($companyId, $row, $mainCategory),
                'approve_status' => $itemInfo['approve_status'],
            ];
            $specItems[] = $specItem;
            $itemInfo['spec_items'] = json_encode($specItems);
            $specImages = $this->getItemSpecImages($companyId, $row, $mainCategory);
            if ($specImages) {
                $itemInfo['spec_images'] = json_encode($specImages);
            }
        }

        if (isset($row['goods_id']) && $row['goods_id']) {
            $itemInfo['goods_id'] = $row['goods_id'];
        }

        if (isset($row['item_id']) && $row['item_id']) {
            $itemInfo['item_id'] = $row['item_id'];
        }

        if ($defaultItemId) {
            $itemInfo['default_item_id'] = $defaultItemId;
        }

        $itemProfitInfo = [];

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $SupplierItemsService->addItems($itemInfo, $isCreateRelData);
            $itemId = $result['item_id'] ?? 0;
            if ($isCreateRelData) {
                $this->defaultItemId = $result['item_id'];
                $this->itemName = trim($row['item_name']);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            app('log')->debug("create_supplier_goods_error =>:" . $e->getFile() . ', ' . $e->getLine() . ', ' . $e->getMessage());
            throw new BadRequestHttpException($e->getMessage());
        } catch (\Throwable $e) {
            $conn->rollback();
            app('log')->debug("create_supplier_goods_error =>:" . $e->getFile() . ', ' . $e->getLine() . ', ' . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }

    private function updateGoods($companyId, $row, $oldItemInfo)
    {
        $SupplierItemsService = new SupplierItemsService();
        $itemId = $row['item_id'];
        $itemInfo = [
            'item_id' => $itemId,
            'goods_id' => $row['goods_id'],
            'company_id' => $companyId,
            'default_item_id' => $row['default_item_id'],
            'start_num' => isset($row['start_num']) && $row['start_num'] > 0 ? $row['start_num'] : 0, // 起订量
        ];

        // 商品价格，用来计算分润
        $itemPrice = $oldItemInfo['price'];
        if (isset($row['price']) && $row['price']) {
            $itemPrice = bcmul($row['price'], 100);
        }

        //获取主类目信息
        $mainCategory = [];
        if ($row['item_main_category']) {
            $mainCategory = $this->getItemMainCategoryId($companyId, $row);
            $itemInfo['item_main_cat_id'] = $mainCategory['category_id'];
        }

        //支持部分字段导入
        foreach ($row as $k => $v) {
            switch ($k) {
                case 'item_category'://销售分类
                    if (!$v) {
                        break;
                    }
                    $itemInfo['item_category'] = $this->getItemCategoryNew($companyId, $row, false);
                    break;

                case 'templates_id':
                    if (!$v) {
                        break;
                    }
                    $itemInfo['templates_id'] = $this->getTemplatesId($companyId, $row);
                    break;

                case 'pics':
                    if (!$v) {
                        break;
                    }
                    $itemInfo['pics'] = $row['pics'] ? explode(',', $row['pics']) : [];
                    break;

                case 'goods_brand':
                    if (!$v) {
                        break;
                    }
                    $itemInfo['brand_id'] = $this->getBrandId($companyId, $row);
                    break;

                case 'item_spec':
                    //商品规格，必须和主类目一起导入
                    if (empty($v) or !$mainCategory) {
                        break;
                    }
                    $itemInfo['nospec'] = 'false';
                    $itemInfo['item_spec'] = $this->getItemSpec($companyId, $row, $mainCategory);
                    break;

                case 'item_params':
                    if (empty($v)) {
                        break;
                    }
                    $itemInfo['item_params'] = $this->getItemParams($companyId, $row);
                    break;

                case 'is_market':
                    if ($row['is_market'] && isset($this->allIsMarket[$row['is_market']])) {
                        $itemInfo['is_market'] = $this->allIsMarket[$row['is_market']];
                    } else {
                        throw new BadRequestHttpException('供应状态错误');
                    }
                    break;

                case 'price':
                case 'cost_price':
                case 'market_price':
                    if (!$v) {
                        $v = 0;
                        // break;
                    }
                    $itemInfo[$k] = bcmul($v, 100);
                    break;
                case 'intro':
                    if (empty($v)) {
                        break;
                    }
                    $itemInfo['intro'] = $this->getIntro($row);
                    break;
                default:
                    if (empty($v)) {
                        break;
                    }
                    $itemInfo[$k] = trim($v);
            }
        }

        //app('log')->debug('_uploadItems itemInfo =>: '.json_encode($itemInfo, 256));

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $itemInfo['audit_status'] = 'submiting';//供应商导入的商品默认变成待提交审核
            $result = $SupplierItemsService->updateUploadItems($itemInfo);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            app('log')->debug("update_supplier_goods_error =>:" . $e->getFile() . $e->getLine() . $e->getMessage());
            throw new BadRequestHttpException($e->getMessage());
        } catch (\Throwable $e) {
            $conn->rollback();
            app('log')->debug("update_supplier_goods_error =>:" . $e->getFile() . $e->getLine() . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }

    private function validatorData($row)
    {
        $arr = ['item_name', 'store', 'price', 'cost_price', 'market_price', 'templates_id', 'is_market'];
        $data = [];
        foreach ($arr as $column) {
            if ($row[$column]) {
                $data[$column] = $row[$column];
            }
        }

        return $data;
    }

    public function getIntro($row)
    {
        $intro = '';
        // app('log')->debug("\n getIntro intro =>:".$row['intro']);
        $introPics = explode(',', $row['intro']);
        // app('log')->debug("\n getIntro introPics =>:".json_encode($introPics, 256));
        if (empty($introPics)) {
            return $intro;
        }
        foreach ($introPics as $value) {
            if ($value) {
                $intro .= "<img src=\"$value\" style=\"display: block;\">";
            }
        }
        return $intro;
    }

    /**
     * 通过运费模版名称，获取运费模版ID
     */
    private function getTemplatesId($companyId, $row)
    {
        if (!$row['templates_id']) {
            throw new BadRequestHttpException('请填写商品运费模版');
        }

        $shippingTemplatesRepository = app('registry')->getManager('default')->getRepository(ShippingTemplates::class);
        $data = $shippingTemplatesRepository->getInfo(['name' => $row['templates_id'], 'supplier_id' => $row['supplier_id'], 'company_id' => $companyId, 'distributor_id' => $row['distributor_id']]);
        if (!$data) {
            throw new BadRequestHttpException('填写的运费模版不存在');
        }

        return $data['template_id'];
    }

    /**
     * 获取商品主类目
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
            throw new BadRequestHttpException('上传管理分类必须是三层级,' . $mainCategory);
        }

        $itemsCategoryService = new ItemsCategoryService();
        app('log')->debug("getItemMainCategoryId catNamesArr =>:".json_encode($catNamesArr, 256));
        $lists = $itemsCategoryService->listsByCategoryName(['company_id' => $companyId, 'category_name' => $catNamesArr, 'is_main_category' => 1]);
        app('log')->debug("getItemMainCategoryId lists =>:".json_encode($lists, 256));
        if ($lists['total_count'] <= 0) {
            throw new BadRequestHttpException('上传管理分类不存在,' . $mainCategory);
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
            throw new BadRequestHttpException('无法识别的管理分类,' . $mainCategory);
        }

        //array_multisort($lists['list'], SORT_ASC, array_column($lists['list'], 'category_level'));
        //$categoryInfo = end($lists['list']);
        return $categoryInfo;
    }

    /**
     * 获取商品分类，这个函数有bug，用 getItemCategoryNew 替代
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

        $catNamesArr = array();
        foreach ($catNames as $catNameRow) {
            $catNamesArr = array_merge($catNamesArr, explode('->', $catNameRow));
        }

        $itemsCategoryService = new ItemsCategoryService();
        // 数据结构买办法判断获取的分类ID是否最子级分类，三级分类改造后在优化
        $lists = $itemsCategoryService->listsByCategoryName(['company_id' => $companyId, 'category_name' => $catNamesArr, 'is_main_category' => $isMain]);
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
        $path2Arr = [];
        foreach ($lists['list'] as $catRow) {
            if ($catRow['category_level'] != '3') {
                $parentIds[] = $catRow['category_id'];
            }
            if ($catRow['category_level'] == '3') {
                $pathArr[] = $catRow['path'];
            }
            if ($catRow['category_level'] == '2') {
                $path2Arr[] = $catRow['path'];
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
            } elseif ($catRow['category_level'] == '1') {
                $result = false;
                foreach ($pathArr as $v) {
                    $result = 0 === strpos($v, $catRow['path'] . ',') ? true : false;
                    if ($result) {
                        break;
                    }
                }
                if ($result) {
                    continue;
                }
                foreach ($path2Arr as $v) {
                    $result = 0 === strpos($v, $catRow['path'] . ',') ? true : false;
                    if ($result) {
                        break;
                    }
                }
                if ($result) {
                    continue;
                }
                $catId[] = $catRow['category_id'];
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
     * 获取商品分类
     */
    private function getItemCategoryNew($companyId, $row)
    {
        if (!$row['item_category']) {
            //供应商可以不填写销售分类
            if (!$row['supplier_id']) {
                throw new BadRequestHttpException('请上传销售分类');
            } else {
                return [];
            }
        }

        $catId = [];
        $category = $row['item_category'];
        $catNames = explode('|', $category);

        $catNamesArr = array();
        foreach ($catNames as $catNameRow) {
            $catNamesArr = array_merge($catNamesArr, explode('->', $catNameRow));
        }

        $itemsCategoryService = new ItemsCategoryService();
        // 数据结构买办法判断获取的分类ID是否最子级分类，三级分类改造后在优化
        $filter = ['company_id' => $companyId, 'distributor_id' => $row['distributor_id'], 'category_name' => $catNamesArr, 'is_main_category' => 0];
        $lists = $itemsCategoryService->listsByCategoryName($filter);
        if ($lists['total_count'] <= 0) {
            throw new BadRequestHttpException('上传商品分类参数有误');
        }

        // 服装->套装->连衣裙
        $catNamePath = [];
        $categoryName = array_column($lists['list'], 'category_name', 'category_id');
        foreach ($lists['list'] as $catRow) {
            $path = explode(',', $catRow['path']);
            foreach ($path as $categoryId) {
                if (!isset($categoryName[$categoryId])) {
                    continue;
                }
                if (isset($catNamePath[$catRow['category_id']])) {
                    $catNamePath[$catRow['category_id']] .= '->' . $categoryName[$categoryId];
                } else {
                    $catNamePath[$catRow['category_id']] = $categoryName[$categoryId];
                }
            }
        }

        foreach ($catNamePath as $categoryId => $v) {
            if (in_array($v, $catNames)) {
                $catId[] = $categoryId;
            }
        }

        //app('log')->debug('_uploadItems catNamePath =>:'.json_encode($catNamePath, 256));
        //app('log')->debug('_uploadItems catId =>:'.json_encode($catId, 256));

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
            $data = $itemsAttributesService->listsByAttributeName(['company_id' => $companyId, 'distributor_id' => $row['distributor_id'], 'attribute_name' => $brandName, 'attribute_type' => 'brand']);
            if (!$data || $data['total_count'] == 0 || empty($data['list'])) {
                throw new BadRequestHttpException($brandName . ' 品牌名称不存在');
            }
            $brandId = $data['list'][0]['attribute_id'];

            // $data = $itemsAttributesService->getInfo(['company_id' => $companyId, 'distributor_id' => $row['distributor_id'], 'attribute_name' => $brandName, 'attribute_type' => 'brand']);
            // if (!$data) {
            //     throw new BadRequestHttpException($brandName . ' 品牌名称不存在');
            // }
            // $brandId = $data['attribute_id'];
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
                $attributeValues[$itemRow[0]] = $itemRow[1];
            }

            $attrList = $itemsAttributesService->listsByAttributeName(['company_id' => $companyId, 'attribute_name' => $attributeNames, 'attribute_type' => 'item_params']);
            if ($attrList['total_count'] > 0) {
                foreach ($attrList['list'] as $row) {
                    $attrValue = $itemsAttributesService->getAttrValue(['company_id' => $companyId, 'attribute_value' => $attributeValues[$row['attribute_name']], 'attribute_id' => $row['attribute_id']]);
                    if ($attrValue) {
                        $data[] = [
                            'attribute_id' => $attrValue['attribute_id'],
                            'attribute_value_id' => $attrValue['attribute_value_id']
                        ];
                    }
                }
            } else {
                throw new BadRequestHttpException('商品参数不存在');
            }
        }

        return $data;
    }

    private function getItemSpec($companyId, $item, &$mainCategory = [])
    {
        $data = [];
        $specInfo = [];
        if ($item['item_spec']) {
            //根据主类目获取商品规格属性的排序
            $goodsSpecIds = $mainCategory['goods_spec'];
            if (!is_array($goodsSpecIds)) {
                $goodsSpecIds = json_decode($goodsSpecIds, true);
            }

            $itemsAttributesService = new ItemsAttributesService();
            $itemParams = explode('|', $item['item_spec']);
            app('log')->debug("getItemSpec itemParams =>:".json_encode($itemParams, 256));
            foreach ($itemParams as $row) {
                $itemRow = explode(':', $row);
                app('log')->debug("getItemSpec itemRow =>:".json_encode($itemRow, 256));
                if (empty($itemRow[0])) {
                    throw new BadRequestHttpException('商品规格解析错误');
                }
                if (empty($itemRow[1])) {
                    throw new BadRequestHttpException('商品规格值解析错误');
                }
                $attributeNames[] = $itemRow[0];
                $attributeValues[] = $itemRow[1];
            }

            // $goodsSpecIds 只查询当前主类目关联的规格
            $filter = [
                'company_id' => $companyId, 'attribute_name' => $attributeNames,
                'attribute_id' => $goodsSpecIds, 'attribute_type' => 'item_spec'
            ];
            app('log')->debug("getItemSpec filter =>:".json_encode($filter, 256));
            $attrList = $itemsAttributesService->listsByAttributeName($filter, 1, 100, ['is_image' => 'DESC', 'attribute_id' => 'ASC']);
            app('log')->debug("getItemSpec attrList =>:".json_encode($attrList, 256));
            app('log')->debug("getItemSpec attributeNames =>:".json_encode($attributeNames, 256));
            if ($attrList['total_count'] == count($attributeNames)) {
                $attributeids = array_column($attrList['list'], 'attribute_id');
            } else {
                throw new BadRequestHttpException('商品规格[' . implode(',', $attributeNames) . ']存在无效值');
            }
            
            $attrValuesList = $itemsAttributesService->getAttrValuesListBy(['company_id' => $companyId, 'attribute_value' => $attributeValues, 'attribute_id' => $attributeids]);
            app('log')->debug("getItemSpec attrValuesList =>:".json_encode($attrValuesList, 256));
            app('log')->debug("getItemSpec attributeValues =>:".json_encode($attributeValues, 256));
            if ($attrValuesList['total_count'] == count($attributeValues)) {
                foreach ($attrValuesList['list'] as $row) {
                    $data[$row['attribute_id']] = [
                        'spec_id' => $row['attribute_id'],
                        'spec_value_id' => $row['attribute_value_id']
                    ];
                }
            } else {
                throw new BadRequestHttpException('商品规格值[' . implode(',', $attributeValues) . ']无效');
            }

            //排序，按ID升序，按图像规格倒序
            foreach ($attributeids as $specId) {
                if (isset($data[$specId])) {
                    $specInfo[] = $data[$specId];
                }
            }
            /*
            foreach ($goodsSpecIds as $specId) {
                if (isset($data[$specId])) {
                    $specInfo[] = $data[$specId];
                }
            }*/

            //查找当前商品的所有规格里，是否存在规格一样，但是货号不一样的sku
            if (isset($item['default_item_id']) && $item['default_item_id']) {

                $SupplierItemsService = new SupplierItemsService();
                $rs = $SupplierItemsService->repository->getLists(['default_item_id' => $item['default_item_id']], 'item_id, item_bn');

                $SupplierItemsAttrService = new SupplierItemsAttrService();
                $rsAttr = $SupplierItemsAttrService->repository->getLists(['item_id' => array_column($rs, 'item_id'), 'attribute_type' => 'item_spec']);

                if ($rs && $rsAttr) {
                    $goodsAttrs = [];
                    //转换成 $attr['商品id']['颜色'] = ['红色'];
                    foreach ($rsAttr as $v) {
                        $attr_data = json_decode($v['attr_data'], true);
                        $attribute_value_id = $attr_data[$v['attribute_type']]['attribute_value_id'];
                        $goodsAttrs[$v['item_id']][$v['attribute_id']] = $attribute_value_id;
                    }
                    //核对商品属性
                    foreach ($rs as $v) {
                        if (!isset($goodsAttrs[$v['item_id']])) continue;
                        if ($v['item_bn'] == trim($item['item_bn'])) continue;
                        $is_repeat = true;
                        foreach ($goodsAttrs[$v['item_id']] as $attr_id => $attr_val_id) {
                            $temp_v = [
                                'spec_id' => $attr_id,
                                'spec_value_id' => $attr_val_id
                            ];
                            if (!in_array($temp_v, $specInfo, true)) {
                                $is_repeat = false;
                                break;
                            }
                        }
                        if ($is_repeat) {
                            throw new BadRequestHttpException('相同规格值的商品已存在');
                        }
                    }
                }

                // $conn = app('registry')->getConnection('default');
                // $qb = $conn->createQueryBuilder();
                // $exist = $qb->select('count(a.item_id)')
                //     ->from('items_rel_attributes', 'a')
                //     ->leftJoin('a', 'items', 'i', 'a.item_id = i.item_id and a.attribute_type = '.$qb->expr()->literal('item_spec'))
                //     ->andWhere($qb->expr()->eq('i.default_item_id', $item['default_item_id']))
                //     ->andWhere($qb->expr()->neq('i.item_bn', $qb->expr()->literal(trim($item['item_bn']))))
                //     ->andWhere($qb->expr()->in('a.attribute_value_id', array_column($specInfo, 'spec_value_id')))
                //     ->groupBy('a.item_id')
                //     ->having('count(*) = '.count($specInfo))
                //     ->execute()->fetchColumn();
                // if ($exist) {
                //     throw new BadRequestHttpException('相同规格值的商品已存在');
                // }
            }
        }

        /*
        usort($data, function($a, $b) {
            if($a['spec_id'] == $b['spec_id']) return 0;
            else return $a['spec_id'] > $b['spec_id'] ? 1 : -1;
        });
        */

        return $specInfo;
    }

    private function getItemSpecImages($companyId, $item, &$mainCategory = [])
    {
        $data = [];
        $specInfo = [];
        if ($item['item_spec'] && $item['item_spec_pics']) {
            //根据主类目获取商品规格属性的排序
            $goodsSpecIds = $mainCategory['goods_spec'];

            $itemParams = explode('|', $item['item_spec']);
            foreach ($itemParams as $row) {
                $itemRow = explode(':', $row);
                if (empty($itemRow[0])) {
                    throw new BadRequestHttpException('商品规格解析错误');
                }
                if (empty($itemRow[1])) {
                    throw new BadRequestHttpException('商品规格值解析错误');
                }
                $attributeNames[] = $itemRow[0];
                $attributeValues[] = $itemRow[1];
            }

            // $goodsSpecIds 只查询当前主类目关联的规格
            $filter = [
                'company_id' => $companyId, 'attribute_name' => $attributeNames,
                'attribute_id' => $goodsSpecIds, 'attribute_type' => 'item_spec', 'is_image' => 'true'
            ];
            $itemsAttributesService = new ItemsAttributesService();
            $attr = $itemsAttributesService->getInfo($filter);
            if (!$attr) {
                return [];
            }

            $attrValue = $itemsAttributesService->getAttrValue(['company_id' => $companyId, 'attribute_value' => $attributeValues, 'attribute_id' => $attr['attribute_id']]);
            if (!$attrValue) {
                return [];
            }

            return [[
                'spec_value_id' => $attrValue['attribute_value_id'],
                'item_image_url' => explode(',', $item['item_spec_pics']),
            ]];
        }

        return [];
    }

    public function getDemoData()
    {
        $data = [
            [
                'item_main_category' => '衣服->男装->裤子',
                'item_name' => '上衣外套',
                'goods_bn' => 'SPU61401E3179BB6',
                'item_bn' => 'SKU61401E3179BB6',
                'brief' => '这是商品的简介',
                'price' => '0.01',
                'market_price' => '0',
                'cost_price' => '0',
                'store' => '99',
                'pics' => '',
                'intro' => '这是商品详情内容',
                'item_spec_pics' => '',
                'videos' => '',
                'goods_brand' => '云店',
                'templates_id' => '全国包邮',
                'item_category' => '男装->裤子->牛仔裤',
                'weight' => '0.5',
                'barcode' => '',
                'item_unit' => '件',
                'item_spec' => '',
                'item_params' => '',
                'is_market' => '不可售',
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
