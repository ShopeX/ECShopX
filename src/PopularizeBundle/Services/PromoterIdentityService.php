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

namespace PopularizeBundle\Services;

use Dingo\Api\Exception\ResourceException;
use PopularizeBundle\Entities\Promoter as EntitiesPromoter;
use PopularizeBundle\Entities\PromoterIdentity;

class PromoterIdentityService
{
    public $promoterIdentityRepository;
    public function __construct()
    {
        $this->promoterIdentityRepository = app('registry')->getManager('default')->getRepository(PromoterIdentity::class);
    }

    public function __call($method, $parameters)
    {
        return $this->promoterIdentityRepository->$method(...$parameters);
    }

    /**
     * 保存推广员身份信息
     */
    public function save($data)
    {
        if (isset($data['id']) && $data['id']) {
            unset($data['is_subordinates']);
            $result = $this->updateOneBy(['id' => $data['id']], $data);
        } else {
            $this->__checkParams($data);
            $result = $this->create($data);
        }
        return $result;
    }

    private function __checkParams(&$data)
    {
        $data['is_subordinates'] ?? 0;
        $data['is_default'] = 0;
        if ($data['is_subordinates'] == 0) {
            $info = $this->getInfo(['company_id' => $data['company_id'], 'is_default' => 1, 'is_subordinates' => 0]);
            if (empty($info)) {
                $data['is_default'] = 1;
            }
        }
        return true;
    }

    public function deletePromoterIdentity($companyId, $id)
    {
        // 检查是否有推广员已使用
        $promoterRepository = app('registry')->getManager('default')->getRepository(EntitiesPromoter::class);
        $filter = [
            'company_id' => $companyId,
            'identity_id' => $id,
        ];
        $promoterLists = $promoterRepository->lists($filter, 1, -1);
        if ($promoterLists['total_count'] > 0) {
            throw new ResourceException('不能删除');
        }
        $this->deleteById($id);
    }

    public function defaultPromoterIdentity($companyId, $id)
    {
        $filter = [
            'company_id' => $companyId,
            'id' => $id,
        ];
        $info = $this->getInfo($filter);
        if (empty($info)) {
            throw new ResourceException('未查询到信息');
        }
        if ($info['is_subordinates'] == 1) {
            throw new ResourceException('不能设置默认');
        }
        $this->updateBy(['company_id' => $companyId], ['is_default' => 0]);
        return $this->updateBy($filter, ['is_default' => 1]);
    }
}
