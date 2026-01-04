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

use WechatBundle\Entities\Weapp;
use WechatBundle\Services\OpenPlatform;
use YoushuBundle\Entities\YoushuSetting;
use YoushuBundle\Services\src\Kernel\Config;
use YoushuBundle\Services\src\Kernel\Factory;

/**
 * Class SrDataService
 * @package YoushuBundle\Services
 *
 * 腾讯有数数据传输，微信数据上报接口
 */
class TaskService
{
    public $youshuSettingRepository;
    public $weappRepository;
    public function __construct()
    {
        $this->youshuSettingRepository = app('registry')->getManager('default')->getRepository(YoushuSetting::class);
        $this->weappRepository = app('registry')->getManager('default')->getRepository(Weapp::class);
    }


    /**
     *  上报页面访问
     */
    public function addWxappVisitPage()
    {
        $youshu_setting = $this->youshuSettingRepository->getAll();
        if (empty($youshu_setting['list'])) {
            return true;
        }

        foreach ($youshu_setting['list'] as $k => $v) {
            try {
                $company_id = $v['company_id'];
                $template_name = 'yykweishop';
                $wxa_app_id = $this->weappRepository->getWxappidByTemplateName($company_id, $template_name);
                if (empty($wxa_app_id)) {
                    continue;
                }

                $begindate = date("Ymd", strtotime("-1 day"));
                $enddate = date("Ymd", strtotime("-1 day"));

                $openPlatform = new OpenPlatform();
                $app = $openPlatform->getAuthorizerApplication($wxa_app_id);
                $visit_data = $app->data_cube->visitPage($begindate, $enddate);

                $config = new Config();
                $config->base_uri = $v['api_url'] ? $v['api_url'] : $v['sandbox_api_url'];
                $config->merchant_id = $v['merchant_id'];
                $config->app_id = $v['app_id'] ? $v['app_id'] : $v['sandbox_app_id'];
                $config->app_secret = $v['app_secret'] ? $v['app_secret'] : $v['sandbox_app_secret'];
                Factory::setOptions($config, $company_id);

                $data_source_type = 0;
                $data_source_id = $this->getDataSourcesId($v['merchant_id'], $data_source_type);
                $reslut = Factory::app()->analysis()->addWxappVisitPage($data_source_id, $visit_data);
                // var_dump($reslut);
            } catch (\Exception $e) {
                app('log')->debug('addWxappVisitPage_error:'.$e->getMessage());
            }
        }
    }

    /**
     * 上报访问分布
     */
    public function addWxappVisitDistribution()
    {
        $youshu_setting = $this->youshuSettingRepository->getAll();
        if (empty($youshu_setting['list'])) {
            return true;
        }

        foreach ($youshu_setting['list'] as $k => $v) {
            try {
                $company_id = $v['company_id'];
                $template_name = 'yykweishop';
                $wxa_app_id = $this->weappRepository->getWxappidByTemplateName($company_id, $template_name);
                if (empty($wxa_app_id)) {
                    continue;
                }

                $begindate = date("Ymd", strtotime("-1 day"));
                $enddate = date("Ymd", strtotime("-1 day"));

                $openPlatform = new OpenPlatform();
                $app = $openPlatform->getAuthorizerApplication($wxa_app_id);
                $visit_data = $app->data_cube->visitDistribution($begindate, $enddate);

                $config = new Config();
                $config->base_uri = $v['api_url'] ? $v['api_url'] : $v['sandbox_api_url'];
                $config->merchant_id = $v['merchant_id'];
                $config->app_id = $v['app_id'] ? $v['app_id'] : $v['sandbox_app_id'];
                $config->app_secret = $v['app_secret'] ? $v['app_secret'] : $v['sandbox_app_secret'];
                Factory::setOptions($config, $company_id);

                $data_source_type = 8;
                $data_source_id = $this->getDataSourcesId($v['merchant_id'], $data_source_type);
                $reslut = Factory::app()->analysis()->addWxappVisitDistribution($data_source_id, $visit_data);
                // var_dump($reslut);
            } catch (\Exception $e) {
                app('log')->debug('addWxappVisitDistribution_error:'.$e->getMessage());
            }
        }
    }

    /**
     * 上报订单汇总
     */
    public function addOrderSum()
    {
        $youshu_setting = $this->youshuSettingRepository->getAll();
        if (empty($youshu_setting['list'])) {
            return true;
        }

        foreach ($youshu_setting['list'] as $k => $v) {
            try {
                $company_id = $v['company_id'];
                $start_time = strtotime("yesterday");
                $end_time = strtotime("today");

                // 查询订单汇总
                $orderService = new OrderService();
                $order_sum_data = [
                    "ref_date" => $start_time.'000',
                    "give_order_amount_sum" => $orderService->countOrderAmount($company_id, $start_time, $end_time),
                    "give_order_num_sum" => $orderService->countOrderNum($company_id, $start_time, $end_time),
                    "payment_amount_sum" => $orderService->countPaymentAmount($company_id, $start_time, $end_time),
                    "payed_num_sum" => $orderService->countPaymentNum($company_id, $start_time, $end_time),
                ];

                $config = new Config();
                $config->base_uri = $v['api_url'] ? $v['api_url'] : $v['sandbox_api_url'];
                $config->merchant_id = $v['merchant_id'];
                $config->app_id = $v['app_id'] ? $v['app_id'] : $v['sandbox_app_id'];
                $config->app_secret = $v['app_secret'] ? $v['app_secret'] : $v['sandbox_app_secret'];
                Factory::setOptions($config, $company_id);

                $data_source_type = 0;
                $data_source_id = $this->getDataSourcesId($v['merchant_id'], $data_source_type);
                $reslut = Factory::app()->analysis()->addOrderSum($data_source_id, $order_sum_data);
                // var_dump($reslut);
            } catch (\Exception $e) {
                app('log')->debug('addOrderSum_error:'.$e->getMessage());
            }
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
