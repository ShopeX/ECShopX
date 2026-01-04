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

namespace YoushuBundle\Services;

use YoushuBundle\Entities\YoushuSetting;
use YoushuBundle\Services\src\Kernel\Config;
use YoushuBundle\Services\src\Kernel\Factory;

/**
 * Class SrDataService
 * @package YoushuBundle\Services
 *
 * 腾讯有数数据传输
 */
class SrDataService
{
    //腾讯有数merchant id
    private $merchant_id;
    public $youshuSettingRepository;

    public function __construct($company_id)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        $this->youshuSettingRepository = app('registry')->getManager('default')->getRepository(YoushuSetting::class);
        $info = $this->youshuSettingRepository->getInfo(['company_id' => $company_id]);
        if (empty($info)) {
            return true;
        }

        $config = new Config();
        $config->base_uri = $info['api_url'] ? $info['api_url'] : $info['sandbox_api_url'];
        $config->merchant_id = $info['merchant_id'];
        $config->app_id = $info['app_id'] ? $info['app_id'] : $info['sandbox_app_id'];
        $config->app_secret = $info['app_secret'] ? $info['app_secret'] : $info['sandbox_app_secret'];
        Factory::setOptions($config, $company_id);

        $this->merchant_id = $info['merchant_id'];
    }

    /**
     * @param array $params ['object_id' => 业务id, 'company_id' => ]
     * @param string $job_type store:门店数据,items:商品sku,category:商品类目,member:会员,order:订单,coupon:卡券
     *
     * 同步数据
     */
    public function sync($params, $job_type)
    {
        $merchant_id = $this->merchant_id;
        if (empty($merchant_id)) {
            return true;
        }

        //店铺
        if ($job_type == 'store') {
            $service = new StoreService();
            $data_source_type = 4;
            $data_source_id = $this->getDataSourcesId($merchant_id, $data_source_type);
            $data = $service->getData($params);
            Factory::app()->items()->pushStore($data_source_id, $data);
        }

        //商品sku
        if ($job_type == 'items') {
            $service = new ItemsService();
            $data_source_type = 3;
            $data_source_id = $this->getDataSourcesId($merchant_id, $data_source_type);
            $data = $service->getData($params);
            Factory::app()->items()->pushSku($data_source_id, $data);
        }

        //分类
        if ($job_type == 'category') {
            $service = new CategoryService();
            $data_source_type = 6;
            $data_source_id = $this->getDataSourcesId($merchant_id, $data_source_type);

            //前台分类
            $params['category_type'] = 1;
            $data = $service->getData($params);
            if (count($data) > 50) {
                $num = 1; //计数器，腾讯有数一次只能传递50条记录
                $key = 1; //数据下标值
                $data_arr = [];
                foreach ($data as $k => $v) {
                    $data_arr[$key][] = $v;
                    $num++;
                    if ($num == 50) {
                        $num = 1;
                        $key++;
                    }
                }

                foreach ($data_arr as $key => $val) {
                    Factory::app()->items()->pushCategory($data_source_id, $val);
                }
            } else {
                Factory::app()->items()->pushCategory($data_source_id, $data);
            }

            //后台分类
            $params['category_type'] = 2;
            $data = $service->getData($params);
            if (count($data) > 50) {
                $num = 1; //计数器，腾讯有数一次只能传递50条记录
                $key = 1; //数据下标值
                $data_arr = [];
                foreach ($data as $k => $v) {
                    $data_arr[$key][] = $v;
                    $num++;
                    if ($num == 50) {
                        $num = 1;
                        $key++;
                    }
                }

                foreach ($data_arr as $key => $val) {
                    Factory::app()->items()->pushCategory($data_source_id, $val);
                }
            } else {
                Factory::app()->items()->pushCategory($data_source_id, $data);
            }
        }

        //会员
        if ($job_type == 'member') {
            $service = new MembersService();
            $data_source_type = 11;
            $data_source_id = $this->getDataSourcesId($merchant_id, $data_source_type);
            $data = $service->getData($params);
            Factory::app()->member()->pushMember($data_source_id, $data);
        }

        //订单
        if ($job_type == 'order') {
            $service = new OrderService();
            $data_source_type = 0;
            $data_source_id = $this->getDataSourcesId($merchant_id, $data_source_type);
            $data = $service->getData($params);
            Factory::app()->order()->pushOrder($data_source_id, $data);
        }

        //优惠券
        if ($job_type == 'coupon') {
            $service = new CouponService();
            $data_source_type = 13;
            $data_source_id = $this->getDataSourcesId($merchant_id, $data_source_type);
            $data = $service->getData($params);
            Factory::app()->activity()->pushCoupon($data_source_id, $data);
        }
    }

    /**
     *  获取数据仓库id
     */
    private function getDataSourcesId($merchant_id, $data_source_type)
    {
        $result = Factory::app()->dataSource()->get($merchant_id, $data_source_type);
        $json_data = json_decode($result, JSON_UNESCAPED_UNICODE);
        if ($json_data['retcode'] == 0) {
            if (isset($json_data['data']['dataSources'][0]['id']) && !empty($json_data['data']['dataSources'][0]['id'])) {
                return $json_data['data']['dataSources'][0]['id'];
            }

            //创建数据仓库
            $result = Factory::app()->dataSource()->add($merchant_id, $data_source_type);
            $json_data = json_decode($result, JSON_UNESCAPED_UNICODE);
            if ($json_data['retcode'] == 0) {
                if (isset($json_data['data']['dataSource']['id']) && !empty($json_data['data']['dataSource']['id'])) {
                    return $json_data['data']['dataSource']['id'];
                }
            }
        }

        throw new \Exception('未查询到腾讯有数对应数据仓库');
    }
}
