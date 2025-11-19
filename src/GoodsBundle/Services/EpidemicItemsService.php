<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Services;

use EspierBundle\Services\File\AbstractTemplate;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class EpidemicItemsService extends AbstractTemplate
{
    protected $extensionArray = ["xlsx"];

    public $header = [
        '商品条形码' => 'barcode',
        '商品编号' => 'item_bn',
        '是否设为疫情商品' => 'is_epidemic',
    ];

    public $headerInfo = [
        '商品条形码' => ['size' => 255, 'remarks' => '', 'is_need' => false],
        '商品编号' => ['size' => 255, 'remarks' => '', 'is_need' => false],
        '是否设为疫情商品' => ['size' => 255, 'remarks' => '1:设为疫情商品  0:设为普通商品', 'is_need' => true],
    ];

    public $isNeedCols = [
        '商品条形码' => 'barcode',
        '商品编号' => 'item_bn',
        '是否设为疫情商品' => 'is_epidemic',
    ];

    public $tmpTarget = null;

    /**
     * 获取头部标题
     */
    public function getHeaderTitle($companyId = 0)
    {
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }


    public function handleRow(int $companyId, array $row): void
    {
        if (!($row['barcode'] ?? []) && !($row['item_bn'] ?? [])) {
            throw new BadRequestHttpException(trans('GoodsBundle/Controllers/Items.item_code_or_barcode_required'));
        }

        if (!isset($row['is_epidemic'])) {
            throw new BadRequestHttpException(trans('GoodsBundle/Controllers/Items.is_epidemic_item_required'));
        }

        $itemsService = new ItemsService();
        if ($row['item_bn'] ?? []) {
            $filter['item_bn'] = $row['item_bn'];
        }

        if ($row['barcode'] ?? []) {
            $filter['barcode'] = $row['barcode'];
        }

        $itemInfo = $itemsService->getItem($filter);
        if (!$itemInfo) {
            $msg = '编码为:' . $row['item_bn'] . ' ,条码为:' . $row['barcode'] . ' 的商品不存在';
            app('log')->debug("\n".$msg);
            throw new BadRequestHttpException($msg);
        }

        $itemsService->simpleUpdateBy($filter, ['is_epidemic' => $row['is_epidemic']]);
    }
}
