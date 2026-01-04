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

namespace DistributionBundle\Http\Api\V1\Action;

use DistributionBundle\Services\DistributorWhiteListService;
use EspierBundle\Jobs\ExportFileJob;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use DistributionBundle\Services\DistributeLogsService;
use DistributionBundle\Services\DistributeCountService;

use Dingo\Api\Exception\ResourceException;

class DistributorWhiteList extends Controller
{
    // todo 下单判断下白名单


   // /distributor/whitelist/add
    public function addWhiteList(Request $request)
    {
        // NOTE: important business logic
        $params = $request->all();
        $service = new DistributorWhiteListService();
        $params['company_id'] = app('auth')->user()->get('company_id');
        $data = $service->addWhiteList($params);
        return $this->response->array(['status'=>true]);
    }

    // /distributor/whitelist/delete
    public function deleteWhiteList(Request $request)
    {
        $params = $request->all();
        $type = $params['type'];
        $id = $params['id'];
        $service = new DistributorWhiteListService();
        if($type === 'id'){
            $distributorId = 0;
            $operatorType = app('auth')->user()->get('operator_type');
            if ($operatorType == 'distributor') {
                $distributorId  = app('auth')->user()->get('distributor_id');
            }
            $service->deleteOneWhiteList($id,$distributorId);
        }else{
            $service->deleteByDistributorId($id);
        }
        return $this->response->array(['status'=>true]);
    }


    // /distributor/whitelist/get
    public function getWhiteList(Request $request)
    {
        $params = $request->all();
        $service = new DistributorWhiteListService();
        $filter = [];
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') {
            $params['distributor_id']  = app('auth')->user()->get('distributor_id');
        }
        if(isset($params['mobile'])){
            $filter['mobile'] = $params['mobile'];
        }
        if(isset($params['distributor_id'])){
            $filter['distributor_id'] = $params['distributor_id'];
        }
        if(isset($params['shop_code'])){
            $filter['shop_code'] = $params['shop_code'];
        }
        if(isset($params['username'])){
            $filter['username'] = $params['username'];
        }
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 20;
        $data = $service->getWhiteList($filter,$page,$pageSize);
        return $this->response->array($data);
    }


    public function exportWhiteList(Request $request)
    {
        // NOTE: important business logic
        $authdata = app('auth')->user()->get();
        $inputData = $request->input();

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');
        $params['company_id'] = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') {
            $params['distributor_id']  = app('auth')->user()->get('distributor_id');
        }
//        $data =
//        if ($inputData['mobile'] ?? '') {
//            $params['mobile'] = $inputData['mobile'] ?? '';
//        }
        $data = $request->all();
        if(isset($data['search_mobile'])){
            $params['mobile'] = $data['search_mobile'];
        }
        if ($inputData['username'] ?? '') {
            $params['username'] = $inputData['username'] ?? '';
        }
        if ($inputData['distributor_id'] ?? '') {
            $params['distributor_id'] = $inputData['distributor_id'] ?? '';
        }
        if ($inputData['shop_code'] ?? '') {
            $params['shop_code'] = $inputData['shop_code'] ?? '';
        }
        // 是否有权限查看加密数据
        $params['datapass_block'] = $request->get('x-datapass-block');
        $gotoJob = (new ExportFileJob('distributor_white_list', $authdata['company_id'], $params, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }
}
