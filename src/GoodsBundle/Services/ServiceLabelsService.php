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

namespace GoodsBundle\Services;

use GoodsBundle\Entities\ServiceLabels;
use Dingo\Api\Exception\ResourceException;

class ServiceLabelsService
{
    /** @var serviceLabelsRepository */
    private $serviceLabelsRepository;

    /**
     * ServiceLabelsService 构造函数.
     */
    public function __construct()
    {
        $this->serviceLabelsRepository = app('registry')->getManager('default')->getRepository(ServiceLabels::class);
    }

    /**
     * 添加会员数值属性
     *
     * @param array params 会员数值属性数据
     * @return array
     */
    public function createServiceLabels(array $params)
    {
        $data = [
            'label_name' => $params['label_name'],
            'label_price' => $params['label_price'],
            'label_desc' => $params['label_desc'],
            'service_type' => $params['service_type'],
            'company_id' => $params['company_id'],
        ];
        $rs = $this->serviceLabelsRepository->create($data);

        return $rs;
    }

    /**
     * 删除会员数值属性
     *
     * @param array filter
     * @return bool
     */
    public function deleteServiceLabels($filter)
    {
        $serviceLabelsInfo = $this->serviceLabelsRepository->get($filter['label_id']);

        if ($filter['company_id'] != $serviceLabelsInfo['company_id']) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.delete_member_value_attr_info_error'));
        }
        if (!$filter['label_id']) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.member_value_attr_id_cannot_be_empty'));
        }

        return $this->serviceLabelsRepository->delete($filter['label_id']);
    }

    /**
     * 获取会员数值属性详情
     *
     * @param inteter label_id 会员数值属性id
     * @return array
     */
    public function getServiceLabelsDetail($label_id)
    {
        $serviceLabelsInfo = $this->serviceLabelsRepository->get($label_id);

        return $serviceLabelsInfo;
    }

    /**
     * 获取会员数值属性列表
     *
     * @param array filter
     * @return array
     */
    public function getServiceLabelsList($filter, $page, $pageSize, $orderBy = ['label_id' => 'DESC'])
    {
        $page = ($page < 1) ? 1 : $page;
        $pageSize = ($pageSize > 100) ? 100 : $pageSize;
        $pageSize = ($pageSize <= 0) ? 10 : $pageSize;
        $serviceLabelsList = $this->serviceLabelsRepository->list($filter, $orderBy, $pageSize, $page);

        return $serviceLabelsList;
    }

    /**
     * 修改会员数值属性
     *
     * @param array params 提交的门店数据
     * @return array
     */
    public function updateServiceLabels($params)
    {
        $serviceLabelsInfo = $this->serviceLabelsRepository->get($params['label_id']);

        if ($params['company_id'] != $serviceLabelsInfo['company_id']) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.please_confirm_member_value_attr'));
        }
        $data = [
            'label_name' => $params['label_name'],
            'label_price' => $params['label_price'],
            'label_desc' => $params['label_desc'],
            'service_type' => $params['service_type'],
            'company_id' => $params['company_id'],
        ];

        $rs = $this->serviceLabelsRepository->update($params['label_id'], $data);

        return $rs;
    }
}
