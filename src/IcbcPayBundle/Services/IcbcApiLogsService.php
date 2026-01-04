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

namespace IcbcPayBundle\Services;



use IcbcPayBundle\Entities\IcbcPayLog;


class IcbcApiLogsService
{


    /**
     * @var \IcbcPayBundle\Repositories\IcbcPayLogRepository
     */
    private $icbcPayLogRepository;
    /**
     * IcbcPayLogRepository
     */
    public function __construct()
    {
        $this->icbcPayLogRepository = app('registry')->getManager('default')->getRepository(IcbcPayLog::class);
    }
    /**
     * 获取单条数据
     */
    public function getOne($filter=[]) {
        return $this->icbcPayLogRepository->getInfo($filter);
    }
    /**
     * 记录接口日志
     */
    public function apiStart($company_id=0, $api_name='', $api_url='', $params=[], $order_id=0)
    {
//                app('log')->debug(__NAMESPACE__.'oms-order-create start data_id:'.$data_id.'-company_id:'.$company_id.'-api_name:'.$api_name.'-api_url:'.$api_url);
        //'id', 'company_id',
        //        'order_id', 'unique_key', 'log_type', 'log_data', 'api_res',
        //        'add_time', 'modify_time'
        if (empty($company_id) || empty($api_name) || empty($api_url)) {
            return 0;
        }
        $param_json = [
            'api_url'   =>  $api_url
        ];
        if ($params ?? '') {
            $param_json['params'] = $params;
        }
        $insertData = [];
        $insertData['company_id'] = $company_id;
        $insertData['log_type'] = $api_name;
        $insertData['unique_key'] = $params['msg_id'] ?? '';
        $insertData['log_data'] = json_encode($param_json);
        $insertData['add_time'] = time();
        $insertData['order_id'] = $order_id ;
//        app('log')->debug(__NAMESPACE__.'wxshopapi-order-create start data_id:'.$data_id.'-ApiLogsData:'.json_encode($insertData));


        $rest = $this->icbcPayLogRepository->create($insertData);
        if ($rest['id'] ?? '') {
            return $rest['id'];
        }
        return 0;
    }
    /**
     * 接口日志执行结束
     */
    public function apiEnd($log_id=0, $return='') {
        if (empty($log_id)) {
            return false;
        }
        $updateData = [];

        $updateData['modify_time'] = time();// 精确到秒即可
        if (!empty($return)) {
            $updateData['api_res'] = $return;
        }
        $filter = ['id' => $log_id];
//        app('log')->debug(__NAMESPACE__.'wxshopapi-order-create start data_id:'.'-ApiLogsData:'.json_encode($updateData));

        $rest = $this->icbcPayLogRepository->updateOneBy($filter, $updateData);
        if ($rest['id'] ?? '') {
            return $rest['id'];
        }

//        $cdjob = (new JdApiLogJob($rest))->onQueue('slow')->delay(Carbon::now()->addSecond(5));
//        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($cdjob);

        return 0;
    }

    public function __call($method, $parameters)
    {
        // EcShopX core
        return $this->icbcPayLogRepository->$method(...$parameters);
    }
}
