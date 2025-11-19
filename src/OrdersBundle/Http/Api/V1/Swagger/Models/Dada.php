<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Http\Api\V1\Swagger\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Dada"))
 */
class Dada
{
    /**
     * @SWG\Property(format="int64", example="1", description="ID")
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(format="int64", example="3321656000130032", description="订单号")
     * @var int
     */
    public $order_id;

    /**
     * @var integer
     * @SWG\Property(example="1", description="公司id")
     */
    public $company_id;

    /**
     * @var integer
     * @SWG\Property(example="1", description="达达状态 0:待处理,1:待接单,2:待取货,3:配送中,4:已完成,5:已取消,9:妥投异常之物品返回中,10:妥投异常之物品返回完成,100: 骑士到店,1000:创建达达运单失败")
     */
    public $dada_status;

    /**
     * @var string
     * @SWG\Property(example="1", description="达达平台订单号")
     */
    public $dada_delivery_no;

    /**
     * @var integer
     * @SWG\Property(example="1", description="订单取消原因来源 1:达达回调配送员取消；2:达达回调商家主动取消；3:达达回调系统或客服取消；11:商城系统取消；12:商城商家主动取消；13:商城消费者主动取消；")
     */
    public $dada_cancel_from;

    /**
     * @var integer
     * @SWG\Property(example="1", description="达达配送员id")
     */
    public $dm_id;

    /**
     * @var string
     * @SWG\Property(example="测试", description="配送员姓名")
     */
    public $dm_name;

    /**
     * @var string
     * @SWG\Property(example="11111", description="配送员手机号")
     */
    public $dm_mobile;

    /**
     * @var integer
     * @SWG\Property(example="111", description="取货时间")
     */
    public $pickup_time;

    /**
     * @var integer
     * @SWG\Property(example="11111", description="送达时间")
     */
    public $delivered_time;

    /**
     * @var integer
     * @SWG\Property(example="11111", description="创建时间")
     */
    public $create_time;

    /**
     * @var integer
     * @SWG\Property(example="11111", description="更新时间")
     */
    public $update_time;
}
