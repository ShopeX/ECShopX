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

use OrdersBundle\Entities\ShippingTemplates;
use KaquanBundle\Services\VipGradeService;
use KaquanBundle\Services\MemberCardService;
use PromotionsBundle\Services\MemberPriceService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GuzzleHttp\Client as Client;

class NormalGoodsUploadService
{
    public const MEMBER_PRICE_KEY = '会员价';//忽略的字段，不导入

    public $memberPriceHeaderReady = false;//会员价表头已经加载

    public $itemName = null;

    public $defaultItemId = null;

    public $header = [
        '管理分类' => 'item_main_category',
        '商品名称' => 'item_name',
        'SPU编码' => 'goods_bn',
        'SKU编码' => 'item_bn',
        '简介' => 'brief',
        '销售价' => 'price',
        '市场价' => 'market_price',
        '成本价' => 'cost_price',
        '起订量' => 'start_num',
        '会员价' => 'member_price',//会被替换
        // '审核状态' => 'audit_status',
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
        '发货时间' => 'delivery_time',
        '是否支持分润' => 'is_profit',
        '分润类型' => 'profit_type',
        '拉新分润' => 'profit',
        '推广分润' => 'popularize_profit',
        '商品状态' => 'approve_status',
    ];

    public $headerInfo = [
        '管理分类' => ['size' => 255, 'remarks' => '类目名称，一级类目->二级类目->三级类目', 'is_need' => true],
        '商品名称' => ['size' => 255, 'remarks' => '', 'is_need' => true],
        'SPU编码' => ['size' => 32, 'remarks' => '平台唯一自动生成', 'is_need' => false],
        'SKU编码' => ['size' => 32, 'remarks' => '平台唯一自动生成	', 'is_need' => false],
        '简介' => ['size' => 20, 'remarks' => '', 'is_need' => false],
        '销售价' => ['size' => 255, 'remarks' => '单位为(元)，最多两位小数', 'is_need' => true],
        '市场价' => ['size' => 255, 'remarks' => '单位为(元)，最多两位小数', 'is_need' => false],
        '成本价' => ['size' => 255, 'remarks' => '单位为(元)，最多两位小数', 'is_need' => false],
        '会员价' => ['size' => 255, 'remarks' => '单位为(元)，最多两位小数', 'is_need' => false],//会被替换
        '起订量' => ['size' => 255, 'remarks' => '起订量', 'is_need' => false],
        // '审核状态' => ['size' => 20, 'remarks' => '待提交、待审核、已通过、已拒绝', 'is_need' => false],
        '库存' => ['size' => 255, 'remarks' => '库存为0-999999999的整数', 'is_need' => true],
        '头图' => ['size' => 255, 'remarks' => '多个图片使用英文逗号隔开，最多上传9个', 'is_need' => false],
        '详情' => ['size' => 255,  'remarks' => '多个图片使用英文逗号隔开', 'is_need' => false],
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
        '发货时间' => ['size' => 255, 'remarks' => '发货时间按天计算', 'is_need' => true],
        '是否支持分润' => ['size' => 255, 'remarks' => '是否支持: 0不支持分润 1支持分润', 'is_need' => false],
        '分润类型' => ['size' => 255, 'remarks' => '分润类型:0,1或2, 0默认分润 1固定比例分润 2固定金额分润', 'is_need' => true],
        '拉新分润' => ['size' => 255, 'remarks' => '1:按照比例分润 1-100, 2:按照固定金额分润(元)，最多两位小数', 'is_need' => false],
        '推广分润' => ['size' => 255, 'remarks' => '1:按照比例分润 1-100, 2:按照固定金额分润(元)，最多两位小数', 'is_need' => false],
        '商品状态' => ['size' => 30, 'remarks' => '前台可销售，前端不展示，不可销售, 前台仅展示', 'is_need' => false],
    ];

    public $allApproveStatus = [
        '前台可销售' => 'onsale',
        '前端不展示' => 'offline_sale',
        '不可销售' => 'instock',
        '前台仅展示' => 'only_show',
    ];

    public $isNeedCols = [
        '管理分类' => 'item_main_category',
        '商品名称' => 'item_name',
        '销售价' => 'price',
        '库存' => 'store',
        '运费模板' => 'templates_id',
        '销售分类' => 'item_category',
        '分润类型' => 'profit_type',
        '发货时间' => 'delivery_time',
    ];
    public $tmpTarget = null;

    /**
     * 验证上传的实体商品信息
     */
    public function check($fileObject)
    {
        // This module is part of ShopEx EcShopX system
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
        $url = $this->getFileSystem()->privateDownloadUrl($filePath);

        //兼容本地文件存储
        if (strtolower(substr($url, 0, 4)) != 'http') {
            $url = storage_path('uploads') . '/' . $filePath;
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
    public function getHeaderTitle($companyId = 0)
    {
        $this->addMemberPriceHeader($companyId);
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }

    /**
     * 增加支持会员价字段导入
     */
    public function addMemberPriceHeader($companyId = 0)
    {
        if ($this->memberPriceHeaderReady) {
            return true;
        }

        if (!$companyId) {
            return false;
        }

        //获取VIP会员等级
        $vipGradeService = new VipGradeService();
        $vipGrade = $vipGradeService->lists(['company_id' => $companyId, 'is_disabled' => false]);
        if ($vipGrade) {
            $vipGrade = array_column($vipGrade, null, 'vip_grade_id');
        }

        //获取普通会员等级
        $kaquanService = new MemberCardService();
        $userGrade = $kaquanService->getGradeListByCompanyId($companyId, false);
        if ($userGrade) {
            $userGrade = array_column($userGrade, null, 'grade_id');
        }

        $this->_setHeader($userGrade, $vipGrade);
        $this->_setHeaderInfo($userGrade, $vipGrade);

        $this->memberPriceHeaderReady = true;

        return true;
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

    /**
     * 设置会员价导入头的字段说明
     *
     * @param string $memberPriceKey
     * @param array $userGrade
     * @param array $vipGrade
     */
    private function _setHeaderInfo($userGrade = [], $vipGrade = [])
    {
        //$dataFormat = ['size' => 255, 'remarks' => '单位为(元)，最多两位小数', 'is_need' => false];
        $dataFormat = $this->headerInfo[self::MEMBER_PRICE_KEY];
        $newHeaderInfo = [];
        foreach ($this->headerInfo as $k => $v) {
            if ($k != self::MEMBER_PRICE_KEY) {
                $newHeaderInfo[$k] = $v;
                continue;
            }

            foreach ($userGrade as $grade) {
                $newHeaderInfo[$grade['grade_name']] = $dataFormat;
            }

            foreach ($vipGrade as $grade) {
                $newHeaderInfo[$grade['grade_name']] = $dataFormat;
            }
        }

        $this->headerInfo = $newHeaderInfo;
    }

    public function handleRow($companyId, $row)
    {
        //app('log')->debug("\n _uploadItems handleRow =>:".json_encode($row, 256));

        //支持导入更新商品数据
        $row['goods_id'] = false;
        $row['item_id'] = false;
        if ($row['item_bn']) {
            $filter = ['item_bn' => $row['item_bn'], 'company_id' => $companyId];
            $itemsService = new ItemsService();
            $oldItemInfo = $itemsService->getItem($filter);
            if ($oldItemInfo) {
                $row['default_item_id'] = $oldItemInfo['default_item_id'];
                $row['goods_id'] = $oldItemInfo['goods_id'];
                $row['item_id'] = $oldItemInfo['item_id'];//如果存在，更新商品数据
                if ($row['distributor_id'] != $oldItemInfo['distributor_id']) {
                    throw new BadRequestHttpException('商品编码已存在其他店铺中，不能更新');
                }
                $this->updateGoods($companyId, $row, $oldItemInfo);
                return;
            }
        }

        $this->createGoods($companyId, $row);
    }

    private function createGoods($companyId, $row)
    {
        $itemsService = new ItemsService();

        $validatorData = $this->validatorData($row);

        $rules = [
            'item_name' => ['required', '请填写商品名称'],
            'price' => ['required', '请填写价格'],
            // 'market_price' => ['required', '请填写市场价'],
            // 'cost_price' => ['required', '请填写结算价'],
//            'store' => ['required|integer|min:0|max:999999999', '库存为0-999999999的整数'],
            'templates_id' => ['required', '请填写运费模板'],
        ];
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
        if (($nospec === false || $nospec === 'false') && $this->itemName && trim($row['item_name']) == $this->itemName) {
            $isCreateRelData = false;
            $defaultItemId = $this->defaultItemId;
        } else {
            $isCreateRelData = true;
            $defaultItemId = null;
        }
        // search items by goods_bn
        $filter = [
            'goods_bn' => $row['goods_bn'],
            'company_id' => $companyId,
            'is_default' => 1,
        ];
        app('log')->debug('debug:'.__FUNCTION__.':'.__LINE__.':'.json_encode($filter));
        $items = $itemsService->getInfo($filter);
        app('log')->debug('debug:'.__FUNCTION__.':'.__LINE__.':'.json_encode($items));
        if ($items) {
            $defaultItemId = $items['default_item_id'];
            $row['goods_id'] = $items['goods_id'];
            $isCreateRelData = false;
            app('log')->debug('debug:'.__FUNCTION__.':'.__LINE__.':'.json_encode($defaultItemId));
            app('log')->debug('debug:'.__FUNCTION__.':'.__LINE__.':'.json_encode($row['goods_id']));
            app('log')->debug('debug:'.__FUNCTION__.':'.__LINE__.':'.json_encode($isCreateRelData));
        }
        $row['default_item_id'] = $defaultItemId;
        $itemsProfitService = new ItemsProfitService();

        $profitType = 0;
        $profitFee = 0;
        if (!in_array(intval($row['is_profit']), [0, 1])) {
            throw new BadRequestHttpException('是否支持分润参数错误');
        }
        $row['profit_type'] = intval($row['profit_type']);
        if ($row['profit_type'] >= 0) {
            if (!in_array($row['profit_type'], [$itemsProfitService::STATUS_PROFIT_DEFAULT, $itemsProfitService::STATUS_PROFIT_SCALE, $itemsProfitService::STATUS_PROFIT_FEE])) {
                throw new BadRequestHttpException('分润类型错误');
            }
            if (0 != $row['profit_type']) {
                if (!($row['profit'] ?? 0)) {
                    throw new BadRequestHttpException('拉新分润金额不能为空');
                }
                if (!($row['popularize_profit'] ?? 0)) {
                    throw new BadRequestHttpException('推广分润金额不能为空');
                }
                $profitType = $itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type'] ? $itemsProfitService::PROFIT_ITEM_PROFIT_SCALE : $itemsProfitService::PROFIT_ITEM_PROFIT_FEE;
                $profitFee = $itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type'] ? bcmul(bcdiv($row['popularize_profit'], 100, 4), $row['price'], 2) : $row['popularize_profit'];
            }
        }

        $mainCategory = $this->getItemMainCategoryId($companyId, $row);//获取主类目信息

        $isProfit = intval($row['is_profit']);

        $itemInfo = [
            'company_id' => $companyId,
            'item_main_cat_id' => $mainCategory['category_id'],
            'item_name' => trim($row['item_name']),
            'item_bn' => trim($row['item_bn']),
            'goods_bn' => trim($row['goods_bn']),
            'brief' => trim($row['brief']),
            'price' => floatval($row['price']),
            'cost_price' => floatval($row['cost_price']),
            'market_price' => floatval($row['market_price']),
            'store' => $row['store'],
            'pics' => isset($row['pics']) ? explode(',', $row['pics']) : [],
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
            'is_profit' => ($isProfit == 1) ? 'true' : 'false',
            'profit_type' => $profitType,
            'profit_fee' => $profitFee,
            'item_type' => 'normal',
            'sort' => 1,
            'approve_status' => 'onsale',
            'distributor_id' => $row['distributor_id'],
            'start_num' => isset($row['start_num']) && $row['start_num'] > 0 ? $row['start_num'] : 0, // 起订量
            'delivery_time' => isset($row['delivery_time']) ? intval($row['delivery_time']) : 0,
        ];

        // 商品上下架状态，默认为 onsale
        if (isset($row['approve_status']) && isset($this->allApproveStatus[$row['approve_status']])) {
            $itemInfo['approve_status'] = $this->allApproveStatus[$row['approve_status']];
        }

        if ($nospec === false || $nospec === 'false') {
            $specItem = [
                'item_bn' => trim($row['item_bn']),
                'weight' => $row['weight'],
                'barcode' => trim($row['barcode']),
                'price' => $row['price'],
                'cost_price' => $row['cost_price'],
                'market_price' => $row['market_price'],
                'item_unit' => $row['item_unit'],
                'store' => $row['store'],
                'is_default' => $isCreateRelData,
                'default_item_id' => $defaultItemId,
                'item_spec' => $this->getItemSpec($companyId, $row, $mainCategory),
                'approve_status' => $itemInfo['approve_status'],
                'spec_pics' => $row['item_spec_pics'] ?? [],
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
        if ($profitType) {
            if ($itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type']) {
                //按比例
                $profitConfData = [
                    //'profit' => bcmul(bcdiv($row['profit'], 100, 4), bcmul($row['price'], 100)),
                    'profit' => $row['profit'],
                    //'popularize_profit' => bcmul(bcdiv($row['popularize_profit'], 100, 4), bcmul($row['price'], 100)),
                    'popularize_profit' => $row['popularize_profit'],
                ];
            } else {
                //按金额
                $profitConfData = [
                    'profit' => bcmul($row['profit'], 100),
                    'popularize_profit' => bcmul($row['popularize_profit'], 100),
                ];
            }
            $itemProfitInfo = [
                'company_id' => $companyId,
                'profit_type' => $row['profit_type'],
                'profit_conf' => $profitConfData,
            ];
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $itemsService->addItems($itemInfo, $isCreateRelData);
            $itemId = $result['item_id'] ?? 0;
            if ($itemProfitInfo && $itemId) {
                $itemProfitInfo['item_id'] = $itemId;
                $itemsProfitService->deleteBy(['company_id' => $companyId, 'item_id' => $itemId]);
                $itemsProfitService->create($itemProfitInfo);
            }
            if ($isCreateRelData) {
                $this->defaultItemId = $result['item_id'];
                $this->itemName = trim($row['item_name']);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            app('log')->debug("_uploadItems_error =>:" . $e->getFile() . ', '. $e->getLine() . ', '. $e->getMessage());
            throw new BadRequestHttpException($e->getMessage());
        } catch (\Throwable $e) {
            $conn->rollback();
            app('log')->debug("_uploadItems_error =>:" . $e->getFile() . ', '. $e->getLine() . ', '. $e->getMessage());
            throw new \Exception($e->getMessage());
        }

        //保存商品的会员价，注意，这里面有事务，不能和上面的事务叠加
        $this->_saveMemberPrice($row, $result['item_id'], $companyId);
    }

    private function updateGoods($companyId, $row, $oldItemInfo)
    {
        $itemsService = new ItemsService();
        $itemsProfitService = new ItemsProfitService();

        $itemId = $row['item_id'];
        $itemInfo = [
            'item_id' => $itemId,
            'goods_id' => $row['goods_id'],
            'company_id' => $companyId,
            'default_item_id' => $row['default_item_id'],
        ];
        $profitType = 0;
        $profitFee = 0;

        // 商品价格，用来计算分润
        $itemPrice = $oldItemInfo['price'];
        if (isset($row['price']) && $row['price']) {
            $itemPrice = bcmul($row['price'], 100);
        }

        //是否支持分润参数
        if (!empty($row['is_profit'])) {
            if (!in_array($row['is_profit'], ['0', '1'])) {
                throw new BadRequestHttpException('是否支持分润参数错误');
            }
            $itemInfo['is_profit'] = ($row['is_profit'] == '1') ? 'true' : 'false';

            if (!empty($row['profit_type'])) {
                $row['profit_type'] = intval($row['profit_type']);
                if (!in_array($row['profit_type'], [$itemsProfitService::STATUS_PROFIT_DEFAULT, $itemsProfitService::STATUS_PROFIT_SCALE, $itemsProfitService::STATUS_PROFIT_FEE])) {
                    throw new BadRequestHttpException('分润类型错误');
                }
                if (0 != $row['profit_type']) {
                    if (!($row['profit'] ?? 0)) {
                        throw new BadRequestHttpException('拉新分润金额不能为空');
                    }
                    if (!($row['popularize_profit'] ?? 0)) {
                        throw new BadRequestHttpException('推广分润金额不能为空');
                    }
                    $profitType = $itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type'] ? $itemsProfitService::PROFIT_ITEM_PROFIT_SCALE : $itemsProfitService::PROFIT_ITEM_PROFIT_FEE;
                    $profitFee = $itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type'] ? bcmul(bcdiv($row['popularize_profit'], 100, 4), $itemPrice, 2) : bcmul($row['popularize_profit'], 100);
                }
                $itemInfo['profit_type'] = $profitType;
                $itemInfo['profit_fee'] = $profitFee;
            }
        }

        //获取主类目信息
        $mainCategory = [];
        if ($row['item_main_category']) {
            $mainCategory = $this->getItemMainCategoryId($companyId, $row);
            $itemInfo['item_main_cat_id'] = $mainCategory['category_id'];
        }

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
                    $itemInfo['pics'] = isset($row['pics']) ? explode(',', $row['pics']) : [];
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

                case 'approve_status':
                    if (empty($v)) {
                        break;
                    }
                    if (!isset($this->allApproveStatus[$v])) {
                        throw new BadRequestHttpException('商品状态错误');
                    }
                    $itemInfo['approve_status'] = $this->allApproveStatus[$v];
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
                case 'start_num':
                    $itemInfo['start_num'] = isset($row['start_num']) && $row['start_num'] > 0 ? $row['start_num'] : 0; // 起订量
                    break;
                case 'delivery_time':
                    $itemInfo['delivery_time'] = isset($v) ? intval($v) : 0;
                    app('log')->debug('debug:'.__FUNCTION__.':'.__LINE__.':'.json_encode($itemInfo['delivery_time']));
                    break;
                case 'start_num':
                    $itemInfo['start_num'] = isset($row['start_num']) && $row['start_num'] > 0 ? $row['start_num'] : 0; // 起订量
                    app('log')->debug('debug:'.__FUNCTION__.':'.__LINE__.':'.json_encode($itemInfo['start_num']));
                    break;
                default:
                    if (empty($v)) {
                        break;
                    }
                    $itemInfo[$k] = trim($v);
            }
        }

        //app('log')->debug('_uploadItems itemInfo =>: '.json_encode($itemInfo, 256));

        $itemProfitInfo = [];
        if ($profitType) {
            if ($itemsProfitService::STATUS_PROFIT_SCALE == $row['profit_type']) {
                //按比例
                $profitConfData = [
                    //'profit' => bcmul(bcdiv($row['profit'], 100, 4), $itemPrice, 2),
                    'profit' => $row['profit'],
                    //'popularize_profit' => bcmul(bcdiv($row['popularize_profit'], 100, 4), $itemPrice, 2),
                    'popularize_profit' => $row['popularize_profit'],
                ];
            } else {
                //按金额
                $profitConfData = [
                    'profit' => bcmul($row['profit'], 100),
                    'popularize_profit' => bcmul($row['popularize_profit'], 100),
                ];
            }
            $itemProfitInfo = [
                'company_id' => $companyId,
                'profit_type' => $row['profit_type'],
                'profit_conf' => $profitConfData,
            ];
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->_saveMemberPrice($row, $itemId, $companyId);

            $audit_status = $itemsService->getDistributorItemAuditStatus($companyId, $row['distributor_id']);
            if ($audit_status) {
                $itemInfo['audit_status'] = $audit_status;
            }
            $result = $itemsService->updateUploadItems($itemInfo);
            if ($itemProfitInfo) {
                $itemProfitInfo['item_id'] = $itemId;
                $itemsProfitService->deleteBy(['item_id' => $itemId, 'company_id' => $companyId]);
                $itemsProfitService->create($itemProfitInfo);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            app('log')->debug("\n _updateItems_error =>:" . $e->getFile() . $e->getLine() . $e->getMessage());
            throw new BadRequestHttpException($e->getMessage());
        } catch (\Throwable $e) {
            $conn->rollback();
            app('log')->debug("\n _updateItems_error =>:" . $e->getFile() . $e->getLine() . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 保存商品的会员价
     *
     * @param array $row
     * @param int $itemId
     * @param int $companyId
     * @return bool|void
     */
    private function _saveMemberPrice($row = [], $itemId = 0, $companyId = 0)
    {
        //mprice: {"5427":{"grade":{"4":"1","8":"2","26":"3","27":""},"vipGrade":{"1":"4","2":""}}}
        //"vipGrade_price1":60,"vipGrade_price2":50
        $memberPrice = [];
        $priceLabel = ['grade', 'vipGrade'];
        $priceValid = false;//是否存在有效的会员价格
        foreach ($row as $k => $v) {
            foreach ($priceLabel as $label) {
                if (!isset($memberPrice[$itemId][$label])) {
                    $memberPrice[$itemId][$label] = [];//初始化结构，防止报错
                }
                if (strstr($k, $label . '_price')) {
                    $gradeId = str_replace($label . '_price', '', $k);
                    $v = floatval($v);
                    if (!$v) {
                        $v = '';//不合法的价格都设置成空
                    } else {
                        $priceValid = true;
                    }
                    $memberPrice[$itemId][$label][$gradeId] = $v;
                }
            }
        }

        //会员价必须一起更新，如果没有填写任何会员价，不做更新
        if ($priceValid === false) {
            return false;
        }

        try {
            $priceParams = [
                'item_id' => $itemId,
                'company_id' => $companyId,
                'mprice' => json_encode($memberPrice, 256),
            ];
            //app('log')->debug("\n _saveMemberPrice priceParams =>:".json_encode($priceParams, 256));
            $memberPriceService = new MemberPriceService();
            $memberPriceService->saveMemberPrice($priceParams);
        } catch (\Exception $e) {
            app('log')->debug("\n _saveMemberPrice error =>:" . $e->getMessage());
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    private function validatorData($row)
    {
        $arr = ['item_name','store', 'price','cost_price','market_price', 'templates_id'];
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
        if(empty($introPics)){
            return $intro;
        }
        foreach ($introPics as $value) {
            if ($value) {
                $intro .= "<img src=\"$value\" style=\"display: block;\">";
            }
        }
        return  $intro;
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
        $data = $shippingTemplatesRepository->getInfo(['name' => $row['templates_id'], 'company_id' => $companyId, 'distributor_id' => $row['distributor_id']]);
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
        // $lists = $itemsCategoryService->lists(['company_id' => $companyId, 'category_name' => $catNamesArr, 'is_main_category' => 1]);
        $lists = $itemsCategoryService->listsByCategoryName(['company_id' => $companyId, 'category_name' => $catNamesArr, 'is_main_category' => 1]);
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
        // $lists = $itemsCategoryService->lists(['company_id' => $companyId, 'category_name' => $catNamesArr, 'is_main_category' => $isMain]);
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
            throw new BadRequestHttpException('请上传商品分类');
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
        // $lists = $itemsCategoryService->lists($filter);
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

            // $attrList = $itemsAttributesService->lists(['company_id' => $companyId, 'attribute_name' => $attributeNames, 'attribute_type' => 'item_params']);
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
                'attribute_id' => $goodsSpecIds, 'attribute_type' => 'item_spec'
            ];
            // $attrList = $itemsAttributesService->lists($filter, 1, 100, ['is_image' => 'DESC', 'attribute_id' => 'ASC']);
            $attrList = $itemsAttributesService->listsByAttributeName($filter, 1, 100, ['is_image' => 'DESC', 'attribute_id' => 'ASC']);
            if ($attrList['total_count'] == count($attributeNames)) {
                $attributeids = array_column($attrList['list'], 'attribute_id');
            } else {
                throw new BadRequestHttpException('商品规格[' . implode(',', $attributeNames) . ']存在无效值');
            }

            $attrValuesList = $itemsAttributesService->getAttrValuesListBy(['company_id' => $companyId, 'attribute_value' => $attributeValues, 'attribute_id' => $attributeids]);
            app('log')->debug(__FUNCTION__.':'.__LINE__.':attrValuesList:'.json_encode($attrValuesList['total_count']));
            app('log')->debug(__FUNCTION__.':'.__LINE__.':attributeValues:'.json_encode($attributeValues));
            app('log')->debug(__FUNCTION__.':'.__LINE__.':attributeids:'.json_encode($attributeids));
            if ($attrValuesList['total_count'] == count($attributeValues)) {
                foreach ($attrValuesList['list'] as $row) {
                    $data[$row['attribute_id']] = [
                        'spec_id' => $row['attribute_id'],
                        'spec_value_id' => $row['attribute_value_id']
                    ];
                }
            } else {
                app('log')->debug(__FUNCTION__.':'.__LINE__.':无效:attrValuesList:'.json_encode($attributeValues));
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

            if (isset($item['default_item_id']) && $item['default_item_id']) {
                $conn = app('registry')->getConnection('default');
                $qb = $conn->createQueryBuilder();
                $exist = $qb->select('count(a.item_id)')
                    ->from('items_rel_attributes', 'a')
                    ->leftJoin('a', 'items', 'i', 'a.item_id = i.item_id and a.attribute_type = '.$qb->expr()->literal('item_spec'))
                    ->andWhere($qb->expr()->eq('i.default_item_id', $item['default_item_id']))
                    ->andWhere($qb->expr()->neq('i.item_bn', $qb->expr()->literal(trim($item['item_bn']))))
                    ->andWhere($qb->expr()->in('a.attribute_value_id', array_column($specInfo, 'spec_value_id')))
                    ->groupBy('a.item_id')
                    ->having('count(*) = '.count($specInfo))
                    ->execute()->fetchColumn();
                if ($exist) {
                    throw new BadRequestHttpException('相同规格值的商品已存在');
                }
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
                'item_main_category' => 'HP->上衣->HP',
                'item_name' => '上衣外套',
                'goods_bn' => 'SPU61401E3179BB6',
                'item_bn' => 'SKU61401E3179BB6',
                'brief' => '1',
                'price' => '0.01',
                'market_price' => '0',
                'cost_price' => '0',
                'member_price' => '',
                // 'audit_status' => '待提交',
                'store' => '99',
                'pics' => '',
                'videos' => '',
                'intro' => '这是详情内容',
                'goods_brand' => '云店',
                'item_category' => '食品副食->咸味食品->屏幕故障',
                'templates_id' => '全国包邮',
                'weight' => '0',
                'barcode' => '',
                'item_unit' => '',
                'item_spec' => '',
                'item_params' => '',
                'is_profit' => '1',
                'profit_type' => '0',
                'profit' => '',
                'popularize_profit' => '',
                'approve_status' => '不可销售'
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
