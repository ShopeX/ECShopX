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

namespace PromotionsBundle\Services;

use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorService;
use EspierBundle\Services\Export\Template\TemplateExport;
use GoodsBundle\Services\ItemsService;
use EspierBundle\Services\File\AbstractTemplate;
use PromotionsBundle\Entities\LimitItemPromotions;
use PromotionsBundle\Entities\LimitPromotions;

/**
 * 更新店铺商品的模板
 * Class LimitSaleItemUploadService
 * @package PromotionsBundle\Services
 */
class LimitSaleItemUploadService extends AbstractTemplate
{
    protected $header = [
        "店铺号" => "shop_code",
        "商品货号" => "item_bn",
        "限购数量" => "limit_num",
    ];

    protected $headerInfo = [
        "店铺号" => ["size" => 32, "remarks" => "店铺号需填写", "is_need" => true],
        "商品货号" => ["size" => 32, "remarks" => "", "is_need" => true],
        "限购数量" => ["size" => 32, "remarks" => "0-1000之间的数字", "is_need" => true],
    ];

    protected $isNeedCols = [
        "店铺号" => "shop_code",
        "商品货号" => "item_bn",
        "限购数量" => "limit_num",
    ];

    public $limitRepository;
    public $limitItemRepository;

    public function handleRow(int $companyId, array $row): void
    {
    }

    //处理限购商品文件
    public function handleFile($fileObject)
    {
        // CONST: 1E236443
        $file = $fileObject->getRealPath();
        $filePath = $file . '.xlsx';
        rename($file, $filePath);

        try {
            $results = app('excel')->toArray(new \stdClass(), $filePath);
            $results = $results[0]; // 读取第1张sheet
            /*
            app('excel')->load($filePath, function ($reader) use (&$results)
            {
                $reader = $reader->getSheet(0);//excel第一张sheet
                $results = $reader->toArray();
            }, null, true);
            */
            if (count($results) < 2) {
                throw new ResourceException(trans('PromotionsBundle.import_file_unrecognizable'));
            }
            if (count($results) > 20001) {
                throw new ResourceException(trans('PromotionsBundle.import_max_20000_rows'));
            }
            unset($results[0]);
        } catch (\Exception $e) {
            throw new ResourceException($e->getMessage());
        }

        return ['total_count' => count($results), 'list' => array_values($results)];
    }

    //保存限购商品文件
    public function saveLimitItems($params = [])
    {
        $this->limitItemRepository = app('registry')->getManager('default')->getRepository(LimitItemPromotions::class);
        $this->limitRepository = app('registry')->getManager('default')->getRepository(LimitPromotions::class);

        $errorDesc = [];
        $page = $params['page'];
        $pageSize = $params['page_size'];
        $totalCount = $params['total_count'];

        $filter = [
            'limit_id' => $params['limit_id'],
            'company_id' => $params['company_id'],
        ];
        $limitInfo = $this->limitRepository->getInfo($filter);
        if ($page > 1) {
            if ($limitInfo) {
                $errorDesc = explode(';', $limitInfo['error_desc']);
            }
        }

        //开启数据库事务
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $shopCode = array_unique(array_column($params['item_data'], 0));
            if (count($shopCode) > 300) {
                throw new ResourceException(trans('PromotionsBundle.import_max_300_stores'));
            }

            //获取所有店铺号对应的店铺id
            $distributorIds = [];
            $distributorService = new DistributorService();
            $shopFilter = [
                'shop_code' => $shopCode,
                'company_id' => $params['company_id'],
            ];
            $distributors = $distributorService->getDistributorOriginalList($shopFilter, 1, 300);
            if ($distributors['list']) {
                $distributorIds = array_column($distributors['list'], 'distributor_id', 'shop_code');
            }

            //获取所有商品ID
            $itemIds = [];
            $itemsService = new ItemsService();
            $itemBns = array_unique(array_column($params['item_data'], 1));
            $itemFilter = [
                'item_bn' => $itemBns,
                'company_id' => $params['company_id']
            ];
            $items = $itemsService->getItemIds($itemFilter, 1, -1, '');
            if ($items) {
                $itemIds = array_column($items, 'item_id', 'item_bn');
            }

            foreach ($params['item_data'] as $k => $v) {
                $currLine = $k + ($page - 1) * $pageSize + 1;

                $v[0] = trim($v[0]);
                if (!isset($distributorIds[$v[0]])) {
                    $this->setErrorDesc($errorDesc, "第{$currLine}行错误, 无法识别的店铺码：" . $v[0]);
                    continue;
                }

                $v[1] = trim($v[1]);
                if (!isset($itemIds[$v[1]])) {
                    $this->setErrorDesc($errorDesc, "第{$currLine}行错误, 无法识别的商品货号：" . $v[1]);
                    continue;
                }

                $limitNum = intval($v[2]);
                if ($limitNum <= 0 or $limitNum > 9999) {
                    $this->setErrorDesc($errorDesc, "第{$currLine}行错误, 限购数量必须在1-9999之间，输入的值：" . $v[2]);
                    continue;
                }

                //先统一删除，再添加
                $filter = [
                    'company_id' => $params['company_id'],
                    'distributor_id' => $distributorIds[$v[0]],
                    'item_id' => $itemIds[$v[1]],
                    'limit_id' => $params['limit_id'],
                ];
                $this->limitItemRepository->deleteBy($filter);

                //时间存在交集，相同店铺下，不允许出现相同的商品
                $rsLimitItems = $this->limitItemRepository->count([
                    'company_id' => $params['company_id'],
                    'distributor_id' => $distributorIds[$v[0]],
                    'item_id' => $itemIds[$v[1]],
                    'start_time|lte' => $limitInfo['end_time'],
                    'end_time|gte' => $limitInfo['start_time'],
                ]);
                if ($rsLimitItems > 0) {
                    $this->setErrorDesc($errorDesc, "第{$currLine}行错误, 商品已经存在限购：" . $v[1]);
                    continue;
                }

                $data = [
                    'distributor_id' => $distributorIds[$v[0]],
                    'item_id' => $itemIds[$v[1]],
                    'limit_num' => $limitNum,
                    'limit_id' => $params['limit_id'],
                    'company_id' => $params['company_id'],
                    'item_type' => $params['use_bound'],
                    'item_name' => '',
                    'item_spec_desc' => '',
                    'pics' => '',
                    'price' => 0,
                    'start_time' => $limitInfo['start_time'],
                    'end_time' => $limitInfo['end_time'],
                ];
                $result[] = $this->limitItemRepository->create($data);
            }

            $saveData = [];
            $saveData['error_desc'] = implode(';', $errorDesc);//错误信息汇总
            $saveData['valid_item_num'] = ($page - 1) * $pageSize + count($params['item_data']);
            if ($page == 1) {
                $saveData['total_item_num'] = $totalCount;
            }
            $filter = [
                'limit_id' => $params['limit_id'],
                'company_id' => $params['company_id'],
            ];
            $this->limitRepository->updateOneBy($filter, $saveData);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        //return $errorDesc;
        return ['success'];
    }

    public function setErrorDesc(&$errorDesc = [], $errMsg = '')
    {
        // CONST: 1E236443
        $maxErrorNum = 101;
        if (count($errorDesc) < $maxErrorNum) {
            $errorDesc[] = $errMsg;
        }
    }

    public function exportErrorDesc($limitId, $fileName, $companyId)
    {
        $errorDesc = [];
        $demoDataList = [];
        $filter = [
            'limit_id' => $limitId,
            'company_id' => $companyId,
        ];
        $this->limitRepository = app('registry')->getManager('default')->getRepository(LimitPromotions::class);
        $rs = $this->limitRepository->getInfo($filter);
        if ($rs) {
            $errorDesc = explode(';', $rs['error_desc']);
            foreach ($errorDesc as $k => $v) {
                if ($k >= 100) {
                    $demoDataList[] = [trans('PromotionsBundle.error_rows_too_many_show_100')];
                    break;
                }
                $demoDataList[] = [$v];
            }
        }

        $dataList[] = ['错误描述'];
        $data = [
            [
                'sheetname' => $fileName,
                'list' => array_merge($dataList, $demoDataList),
            ]
        ];
        $templateObj = new TemplateExport($data);
        return app('excel')->raw($templateObj, \Maatwebsite\Excel\Excel::XLSX);

//        $writer = app('excel')->create('template.xlsx', function ($excel) use ($dataList, $fileName, $demoDataList) {
//            $excel->sheet($fileName, function ($sheet) use ($dataList, $demoDataList) {
//                $sheet->setOrientation('landscape');
//                $sheet->rows($dataList);
//                if ($demoDataList) {
//                    $sheet->rows($demoDataList);
//                }
//                //$sheet->getStyle("D10")->getFont()->getColor()->setRGB('FF0000');
//            });
//        });
//
//        return $writer->string('xlsx');
    }
}
