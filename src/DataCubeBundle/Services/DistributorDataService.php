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

namespace DataCubeBundle\Services;

use DataCubeBundle\Entities\DistributorData;
use DataCubeBundle\Jobs\DistributorDataJob;
use Dingo\Api\Exception\ResourceException;

class DistributorDataService
{
    /** @var DistributorDataRepository */
    private $DistributorDataRepository;

    /**
     * MonitorsService 构造函数.
     */
    public function __construct()
    {
        $this->DistributorDataRepository = app('registry')->getManager('default')->getRepository(DistributorData::class);
    }

    public function getDistributorDataList($filter, $page, $pageSize, $orderBy = ['count_date' => 'ASC'])
    {
        // 临时演示区域查看统计，2020.10.22
        if (is_array($filter['distributor_id'])) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder();
            $qb->select('count_date,company_id,sum(member_count) as member_count,sum(aftersales_count) as aftersales_count,sum(refunded_count) as refunded_count,sum(amount_payed_count) as amount_payed_count,sum(amount_point_payed_count) as amount_point_payed_count,sum(order_count) as order_count,sum(order_point_count) as order_point_count,sum(order_payed_count) as order_payed_count,sum(order_point_payed_count) as order_point_payed_count,sum(gmv_count) as gmv_count,sum(gmv_point_count) as gmv_point_count')
                ->from('datacube_distributor_data')
                ->where($qb->expr()->eq('company_id', $filter['company_id']))
                ->andWhere($qb->expr()->in('distributor_id', $filter['distributor_id']))
                ->andWhere($qb->expr()->gte('count_date', $qb->expr()->literal($filter['count_date|gte'])))
                ->andWhere($qb->expr()->lte('count_date', $qb->expr()->literal($filter['count_date|lte'])))
                ->groupBy('count_date')
                ->orderBy('count_date', 'ASC');

            $result = $qb->execute()->fetchAll();
            return ['list' => $result];
        }
        if ($filter['distributor_id'] == 'all') {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder();
            $qb->select('count_date,company_id,sum(member_count) as member_count,sum(aftersales_count) as aftersales_count,sum(refunded_count) as refunded_count,sum(amount_payed_count) as amount_payed_count,sum(amount_point_payed_count) as amount_point_payed_count,sum(order_count) as order_count,sum(order_point_count) as order_point_count,sum(order_payed_count) as order_payed_count,sum(order_point_payed_count) as order_point_payed_count,sum(gmv_count) as gmv_count,sum(gmv_point_count) as gmv_point_count')
                ->from('datacube_distributor_data')
                ->where($qb->expr()->eq('company_id', $filter['company_id']))
                ->andWhere($qb->expr()->in('merchant_id', $filter['merchant_id']))
                ->andWhere($qb->expr()->gte('count_date', $qb->expr()->literal($filter['count_date|gte'])))
                ->andWhere($qb->expr()->lte('count_date', $qb->expr()->literal($filter['count_date|lte'])))
                ->groupBy('count_date')
                ->orderBy('count_date', 'ASC');

            $result = $qb->execute()->fetchAll();
            return ['list' => $result];
        }
        $DistributorDataList = $this->DistributorDataRepository->lists($filter, $page, $pageSize, $orderBy);
        return $DistributorDataList;
    }

    /**
     * 初始化任务。
     *
     * @return void
     */
    public function scheduleInitStatistic()
    {
        app('log')->info('执行统计商城门店数据初始化脚本');
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $companys = $criteria->select('company_id')->from('companys')->execute()->fetchAll();
        $count_date = date('Y-m-d', strtotime('-1 day'));
        foreach ($companys as $v) {
            $qb = $conn->createQueryBuilder();
            $qb->select('distributor_id,company_id,merchant_id')
                ->from('distribution_distributor')
                ->where($qb->expr()->eq('company_id', $v['company_id']));

            $distributor = $qb->execute()->fetchAll();
            //增加总店参数
            $store_head = array(
                'distributor_id' => 0,
                'company_id' => $v['company_id'],
                'merchant_id' => 0,
            );
            array_unshift($distributor, $store_head);

            foreach ($distributor as $vv) {
                $conn = app('registry')->getConnection('default');
                $qb = $conn->createQueryBuilder();
                $qb->select('count(*)')
                    ->from('datacube_distributor_data')
                    ->where($qb->expr()->eq('count_date', $qb->expr()->literal($count_date)))
                    ->andWhere($qb->expr()->eq('company_id', $v['company_id']))
                    ->andWhere($qb->expr()->eq('distributor_id', $vv['distributor_id']));
                $fetchcount = $qb->execute()->fetchColumn();
                if (!$fetchcount) {
                    $conn = app('registry')->getConnection('default');
                    $data = ['company_id' => $v['company_id'], 'distributor_id' => $vv['distributor_id'], 'count_date' => $count_date,'merchant_id' => $vv['merchant_id']];
                    $conn->insert('datacube_distributor_data', $data);
                }
                $vv['count_date'] = $count_date;

                $job = (new DistributorDataJob($vv))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
            }
        }
    }

    /**
     * 执行每日统计。
     *
     * @param integer $company_id
     * @param date $date 日期格式为 Y-m-d
     * @return void
     */
    public function runStatistics($company_id, $distributor_id, $date, $merchant_id)
    {
        app('log')->info('统计商城加经销商数据开始,参数{company_id:' . $company_id . 'distributor_id:' . $distributor_id . ',count_date:' . $date . '}');
        if (!$company_id) {
            throw new ResourceException('必须指定company_id才能统计数据');
        }
//        if (!$distributor_id) {
//            throw new ResourceException('必须指定distributor_id才能统计数据');
//        }
        if (!$date || !$this->isDate($date)) {
            throw new ResourceException('必须填写日期，且格式为为"Y-m-d"');
        }
        $start = strtotime($date . ' 00:00:00');
        $end = strtotime($date . ' 23:59:59');
        $member_count = $this->member_count($company_id, $distributor_id, $start, $end); // 新增会员
        $aftersales_count = $this->aftersales_count($company_id, $distributor_id, $start, $end); // 新增售后单
        $refunded_count = $this->refunded_count($company_id, $distributor_id, $start, $end); // 新增退款额
        $amount_payed_count = $this->amount_payed_count($company_id, $distributor_id, $start, $end); // 新增支付额
        $amount_point_payed_count = $this->amount_point_payed_count($company_id, $distributor_id, $start, $end); // 新增支付额(积分)
        $order_count = $this->order_count($company_id, $distributor_id, $start, $end); // 新增订单
        $order_point_count = $this->order_point_count($company_id, $distributor_id, $start, $end); // 新增订单(积分)
        $order_payed_count = $this->order_payed_count($company_id, $distributor_id, $start, $end); // 新增已付款订单
        $order_point_payed_count = $this->order_point_payed_count($company_id, $distributor_id, $start, $end); // 新增已付款订单(积分)
        $gmv_count = $this->gmv_count($company_id, $distributor_id, $start, $end); // 新增gmv
        $gmv_point_count = $this->gmv_point_count($company_id, $distributor_id, $start, $end); // 新增gmv(积分)

        $updateData = [
            'member_count' => $member_count ?? 0,
            'aftersales_count' => $aftersales_count ?? 0,
            'refunded_count' => $refunded_count ?? 0,
            'amount_payed_count' => $amount_payed_count ?? 0,
            'amount_point_payed_count' => $amount_point_payed_count ?? 0,
            'order_count' => $order_count ?? 0,
            'order_point_count' => $order_point_count ?? 0,
            'order_payed_count' => $order_payed_count ?? 0,
            'order_point_payed_count' => $order_point_payed_count ?? 0,
            'gmv_count' => $gmv_count ?? 0,
            'gmv_point_count' => $gmv_point_count ?? 0,
        ];

        $conn = app('registry')->getConnection('default');
        $conn->update('datacube_distributor_data', $updateData, ['count_date' => $date, 'company_id' => $company_id, 'distributor_id' => $distributor_id,'merchant_id' => $merchant_id]);
        app('log')->info('统计商城加经销商数据结束');
    }

    /**
     * 统计新增会员
     *
     * @param integer $company_id 公司id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function member_count($company_id, $distributor_id, $start, $end)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from('members_rel_shop')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('shop_id', $distributor_id))
            ->andWhere($qb->expr()->gte('created', $start))
            ->andWhere($qb->expr()->lte('created', $end));

        if ($distributor_id === 0) {
            $count = 0;
        } else {
            $count = $qb->execute()->fetchColumn();
        }

        return $count;
    }

    /**
     * 统计新增售后单
     *
     * @param integer $company_id 公司id
     * @param integer $distributor_id 经销商id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function aftersales_count($company_id, $distributor_id, $start, $end)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from('aftersales')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('distributor_id', $distributor_id))
            ->andWhere($qb->expr()->gte('create_time', $start))
            ->andWhere($qb->expr()->lte('create_time', $end));
        $count = $qb->execute()->fetchColumn();

        return $count;
    }

    /**
     * 统计新增退款额
     *
     * @param integer $company_id 公司id
     * @param integer $distributor_id 经销商id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function refunded_count($company_id, $distributor_id, $start, $end)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(refunded_fee)')
            ->from('aftersales_refund')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('distributor_id', $distributor_id))
            ->andWhere($qb->expr()->gte('update_time', $start))
            ->andWhere($qb->expr()->lte('update_time', $end))
            ->andWhere($qb->expr()->eq('refund_status', $qb->expr()->literal('SUCCESS')));
        $sum = $qb->execute()->fetchColumn();
        return $sum;
    }

    /**
     * 统计新增支付额
     *
     * @param integer $company_id 公司id
     * @param integer $distributor_id 经销商id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function amount_payed_count($company_id, $distributor_id, $start, $end)
    {
        $trade_state = ['REFUND_PROCESS', 'REFUND_SUCCESS', 'SUCCESS'];
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        array_walk($trade_state, function (&$value) use ($qb) {
            $value = $qb->expr()->literal($value);
        });
        $qb->select('sum(cast(total_fee as SIGNED))')
            ->from('trade')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('distributor_id', $distributor_id))
            ->andWhere($qb->expr()->in('trade_state', $trade_state))
            ->andWhere($qb->expr()->gte('time_expire', $start))
            ->andWhere($qb->expr()->lte('time_expire', $end));
        $sum = $qb->execute()->fetchColumn();

        return $sum;
    }

    /**
     * 统计新增支付额(积分)
     *
     * @param integer $company_id 公司id
     * @param integer $distributor_id 经销商id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function amount_point_payed_count($company_id, $distributor_id, $start, $end)
    {
        $trade_state = ['REFUND_PROCESS', 'REFUND_SUCCESS', 'SUCCESS'];
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        array_walk($trade_state, function (&$value) use ($qb) {
            $value = $qb->expr()->literal($value);
        });
        $qb->select('sum(cast(total_fee as SIGNED))')
            ->from('trade')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('distributor_id', $distributor_id))
            ->andWhere($qb->expr()->in('trade_state', $trade_state))
            ->andWhere($qb->expr()->gte('time_expire', $start))
            ->andWhere($qb->expr()->lte('time_expire', $end));
        $sum = $qb->execute()->fetchColumn();

        return $sum;
    }

    /**
     * 统计新增订单
     *
     * @param integer $company_id 公司id
     * @param integer $distributor_id 经销商id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function order_count($company_id, $distributor_id, $start, $end)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from('orders_normal_orders')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('distributor_id', $distributor_id))
            ->andWhere($qb->expr()->gte('create_time', $start))
            ->andWhere($qb->expr()->lte('create_time', $end));
        $count = $qb->execute()->fetchColumn();

        return $count;
    }

    /**
     * 统计新增订单(积分)
     *
     * @param integer $company_id 公司id
     * @param integer $distributor_id 经销商id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function order_point_count($company_id, $distributor_id, $start, $end)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from('orders_normal_orders')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('distributor_id', $distributor_id))
            ->andWhere($qb->expr()->gte('create_time', $start))
            ->andWhere($qb->expr()->lte('create_time', $end));
        $count = $qb->execute()->fetchColumn();

        return $count;
    }

    /**
     * 统计新增已付款订单
     *
     * @param integer $company_id 公司id
     * @param integer $distributor_id 经销商id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function order_payed_count($company_id, $distributor_id, $start, $end)
    {
        $trade_state = ['REFUND_PROCESS', 'REFUND_SUCCESS', 'SUCCESS'];
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        array_walk($trade_state, function (&$value) use ($qb) {
            $value = $qb->expr()->literal($value);
        });
        $qb->select('count(*)')
            ->from('trade')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('distributor_id', $distributor_id))
            ->andWhere($qb->expr()->in('trade_state', $trade_state))
            ->andWhere($qb->expr()->gte('time_expire', $start))
            ->andWhere($qb->expr()->lte('time_expire', $end));
        $sum = $qb->execute()->fetchColumn();

        return $sum;
    }

    /**
     * 统计新增已付款订单(积分)
     *
     * @param integer $company_id 公司id
     * @param integer $distributor_id 经销商id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function order_point_payed_count($company_id, $distributor_id, $start, $end)
    {
        $trade_state = ['REFUND_PROCESS', 'REFUND_SUCCESS', 'SUCCESS'];
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        array_walk($trade_state, function (&$value) use ($qb) {
            $value = $qb->expr()->literal($value);
        });
        $qb->select('count(*)')
            ->from('trade')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('distributor_id', $distributor_id))
            ->andWhere($qb->expr()->in('trade_state', $trade_state))
            ->andWhere($qb->expr()->gte('time_expire', $start))
            ->andWhere($qb->expr()->lte('time_expire', $end));
        $sum = $qb->execute()->fetchColumn();

        return $sum;
    }

    /**
     * 统计新增gmv
     *
     * @param integer $company_id 公司id
     * @param integer $distributor_id 经销商id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function gmv_count($company_id, $distributor_id, $start, $end)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(cast(total_fee as SIGNED))')
            ->from('orders_normal_orders')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('distributor_id', $distributor_id))
            ->andWhere($qb->expr()->gte('create_time', $start))
            ->andWhere($qb->expr()->lte('create_time', $end));
        $sum = $qb->execute()->fetchColumn();

        return $sum;
    }

    /**
     * 统计新增gmv(积分)
     *
     * @param integer $company_id 公司id
     * @param integer $distributor_id 经销商id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function gmv_point_count($company_id, $distributor_id, $start, $end)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(cast(total_fee as SIGNED))')
            ->from('orders_normal_orders')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('distributor_id', $distributor_id))
            ->andWhere($qb->expr()->gte('create_time', $start))
            ->andWhere($qb->expr()->lte('create_time', $end));
        $sum = $qb->execute()->fetchColumn();

        return $sum;
    }

    // 检查日期格式是否正确
    private function isDate($strDate, $format = 'Y-m-d')
    {
        $arr = explode('-', $strDate);
        return checkdate($arr[1], $arr[2], $arr[0]) ? true : false;
    }
}
