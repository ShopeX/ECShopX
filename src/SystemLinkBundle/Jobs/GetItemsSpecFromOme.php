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

namespace SystemLinkBundle\Jobs;

use EspierBundle\Jobs\Job;
use Dingo\Api\Exception\ResourceException;
use SystemLinkBundle\Services\ShopexErp\OpenApi\Request;
use GoodsBundle\Services\ItemsAttributesService;
use SystemLinkBundle\Services\ThirdSettingService;

class GetItemsSpecFromOme extends Job
{
    public $companyId = '';
    public $pageSize = 10;
    public $pageNo = 1;
    private $endLastmodify;
    /**
     * 拉取oms商品规格
     *
     * @return void
     */
    public function __construct($companyId, $pageNo, $endLastmodify)
    {
        $this->companyId = $companyId;
        $this->pageNo = $pageNo;
        $this->endLastmodify = $endLastmodify;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {

        // 判断是否开启OME
        $service = new ThirdSettingService();
        $data = $service->getShopexErpSetting($this->companyId);
        if (!isset($data) || ($data['is_openapi_open'] ?? false) == false) {
            app('log')->debug('companyId:'.$this->companyId.",msg:未开启OME开放数据接口");
            return true;
        }
        $result = [];
        try {
            $startLastmodify = app('redis')->hget($this->_key(), 'spec') ?: 0;
            $params = [
                'start_time' => date('Y-m-d H:i:s', $startLastmodify ?: 0),
                'end_time' => date('Y-m-d H:i:s', $this->endLastmodify),
                'page_no' => $this->pageNo,
                'page_size' => $this->pageSize
            ];
            $omeRequest = new Request(1);
            $method = 'goodsspec.getList';
            $result = $omeRequest->call($method, $params);
            if (!isset($result['rsp']) || $result['rsp'] != 'succ') {
                app('log')->debug('companyId:'.$this->companyId.",msg:OME批量获取商品规格信息请求失败");
                return true;
            }

            $data = $result['data'];
            if ($data['count'] > 0) {
                $list = $data['lists'];
                $this->saveSpecs($list);
            }

            if ($params['page_no'] * $params['page_size'] >= $data['count']) {
                app('redis')->hset($this->_key(), 'spec', $this->endLastmodify);
            } else {
                $gotoJob = (new GetItemsSpecFromOme($this->companyId, $params['page_no'] + 1, $this->endLastmodify))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
            }

            app('log')->debug($method.'=>requestData:'. json_encode($data)."==>result:\r\n".var_export($result, 1));
        } catch (\Exception $e) {
            app('log')->debug('OMS请求失败:'. $e->getMessage().'=>method:'.$method.'=>requestData:'.json_encode($data)."=>result:". json_encode($result));
        }
        return true;
    }
    private function saveSpecs($data)
    {
        // 0x456353686f7058
        $itemsAttributesService = new ItemsAttributesService();
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            foreach ($data as $spec) {
                $info = $itemsAttributesService->getInfo(['attribute_code' => $spec['spec_code'], 'attribute_type' => 'item_spec', 'company_id' => $this->companyId]);
                $saveSpecs = [
                    'company_id' => $this->companyId,
                    'attribute_type' => 'item_spec',
                    'attribute_name' => $spec['spec_name'],
                    'attribute_code' => $spec['spec_code'],
                    'from_oms' => true
                ];
                array_walk($spec['spec_value'], function (&$value) {
                    $tmp = [];
                    $tmp['attribute_value'] = $value['spec_value'];
                    $tmp['image_url'] = $value['spec_image'];
                    $tmp['oms_value_id'] = $value['spec_value_id'];
                    $value = $tmp;
                });
                $saveSpecs['attribute_values'] = $spec['spec_value'];
                if ($info) {
                    $itemsAttributesService->updateAttr(['attribute_id' => $info['attribute_id'], 'company_id' => $this->companyId], $saveSpecs);
                } else {
                    $itemsAttributesService->createAttr($saveSpecs);
                }
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
    }

    private function _key()
    {
        return 'LastTimeGetFromOme:'.$this->companyId;
    }
    // private function
}
