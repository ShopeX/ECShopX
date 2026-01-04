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

namespace AdaPayBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use OrdersBundle\Services\TradeService;

class AdapayTradeDataUploadService
{
    public $header = [
        '订单号' => 'order_id',
        '交易单号' => 'trade_id',
        '是否分账' => 'adapay_div_status',
    ];

    public $headerInfo = [
        '订单号' => ['size' => 32, 'remarks' => '不得重复，订单号如果大于11位时，请关闭excel单元格的科学记数法，常用禁用方法：“单元格格式”-“自定义”-“类型”改为“0”', 'is_need' => true],
        '交易单号' => ['size' => 32, 'remarks' => '不得重复，交易单号如果大于11位时，请关闭excel单元格的科学记数法，常用禁用方法：“单元格格式”-“自定义”-“类型”改为“0”', 'is_need' => true],
        '是否分账' => ['size' => 30, 'remarks' => '已分账/未分账', 'is_need' => true],
    ];

    public $isNeedCols = [
        '订单号' => 'order_id',
        '交易单号' => 'trade_id',
        '是否分账' => 'adapay_div_status',
    ];

    public $tmpTarget;

    /**
    * 获取头部标题
    */
    public function getHeaderTitle()
    {
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }


    /**
     * 验证上传的会员信息
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('文件上传只支持xlsx文件格式');
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
        $content = file_get_contents($url);

        $this->tmpTarget = tempnam('/tmp', 'import-file') . $fileExt;
        file_put_contents($this->tmpTarget, $content);

        return $this->tmpTarget;
    }

    public function finishHandle()
    {
        unlink($this->tmpTarget);
        return true;
    }

    public function getFileSystem()
    {
        return app('filesystem')->disk('import-file');
    }

    public function handleRow($companyId, $row)
    {
        $row['trade_id'] = trim(trim($row['trade_id']), "'");
        $row['order_id'] = trim(trim($row['order_id']), "'");

        $tradeList = $this->getTradeList(['trade_id' => $row['trade_id'], 'order_id' => $row['order_id']]);
        if (!$tradeList) {
            throw new BadRequestHttpException('分账订单不存在');
        }
        $trade = $tradeList[0];
        if ($trade['payType'] == 'adapay') {
            throw new BadRequestHttpException('线上分账订单不可手动分账');
        }
        if (!$trade['canDiv']) {
            throw new BadRequestHttpException('订单当前不可分账');
        }

        if ($row['adapay_div_status'] == '已分账') {
            $status = 'DIVED';
        } elseif ($row['adapay_div_status'] == '已分账') {
            $status = 'NOTDIV';
        } else {
            throw new BadRequestHttpException('是否分账填写错误');
        }

        $tradeService = new TradeService();
        $tradeService->updateOneBy(['trade_id' => $row['trade_id'], 'order_id' => $row['order_id']], ['adapay_div_status' => $status]);
    }

    public function getTradeList($filter, $pageSize = -1, $page = 1)
    {
        // $filter['pay_type'] = 'adapay'; //待分账的支付单的支付方式都是adapay
        $filter['trade_state'] = ['SUCCESS'];
        $filter['trade_source_type|neq'] = 'membercard';
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $cols = 'a.trade_id as tradeId, a.pay_type as payType, a.order_id as orderId,a.total_fee as totalFee,a.pay_fee as payFee,coalesce(sum(b.refunded_fee),0) as refundedFee,a.adapay_div_status as adapayDivStatus,a.adapay_fee_mode as adapayFeeMode,a.adapay_fee as adapayFee,a.time_start as timeStart,a.distributor_id as distributorId,a.pay_channel as payChannel, c.order_auto_close_aftersales_time as closeAftersalesTime, c.order_id as normalOrderId';
        $qb->from('trade', 'a')
            ->leftJoin('a', 'aftersales_refund', 'b', 'a.order_id = b.order_id and b.refund_status = '.$qb->expr()->literal("SUCCESS"))
            ->leftJoin('a', 'orders_normal_orders', 'c', 'a.order_id = c.order_id');
        $this->getFilter($filter, $qb);
        $qb->groupBy('a.trade_id');
        $qb->orderBy('time_start', 'DESC');
        $list = $qb->select($cols)->execute()->fetchAll();

        array_walk($list, function (&$row) {
            if ($row['refundedFee'] < $row['payFee'] && (!isset($row['normalOrderId']) || ($row['closeAftersalesTime'] > 0 and $row['closeAftersalesTime'] < time()))) {
                $row['canDiv'] = true;
            } else {
                $row['canDiv'] = false;
            }
        });
        return $list;
    }

    /**
     * 筛选条件格式化
     *
     * @param $filter
     * @param $qb
     */
    private function getFilter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                }
                if ($k == 'like') {
                    $value = '%'.$value.'%';
                }
                if (is_array($value)) {
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->$k($field, $value));
                } else {
                    $qb = $qb->andWhere($qb->expr()->$k("a.".$v, $qb->expr()->literal($value)));
                }
                continue;
            } elseif (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in("a.".$field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq("a.".$field, $qb->expr()->literal($value)));
            }
        }
        return $qb;
    }
}
