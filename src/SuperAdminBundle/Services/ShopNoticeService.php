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

namespace SuperAdminBundle\Services;

use SuperAdminBundle\Entities\ShopNotice;
use Dingo\Api\Exception\ResourceException;

class ShopNoticeService
{
    public $shopNoticeRepository;

    public $limit = 10; //每页默认条数

    public function __construct()
    {
        $this->shopNoticeRepository = app('registry')->getManager('default')->getRepository(ShopNotice::class);
    }


    public function create($params)
    {
        $data = [
            'title' => $params['title'],
            'type' => isset($params['type']) ? $params['type'] : 'notice',
            'web_link' => $params['web_link'],
            'is_publish' => ($params['is_publish'] == 'true' || $params['is_publish'] == 1) ? 1 : 0
        ];

        return $this->shopNoticeRepository->create($data);
    }

    public function getShopNoticeInfo($notice_id)
    {
        return $this->shopNoticeRepository->getInfoById($notice_id);
    }

    public function updateShopNotice($filter, $params)
    {
        $noticeInfo = $this->shopNoticeRepository->getInfo($filter);

        if (!$noticeInfo) {
            throw new ResourceException('获取公告信息失败');
        }

        return $this->shopNoticeRepository->updateOneBy($filter, $params);
    }

    public function getShopNoticeList($params)
    {
        $page = isset($params['page']) ? trim($params['page']) : '1';

        $pageSize = isset($params['pageSize']) ? trim($params['pageSize']) : $this->limit;

        $orderBy = ['notice_id' => 'DESC'];

        $filter = [];

        return $this->shopNoticeRepository->lists($filter, $page, $pageSize, $orderBy);
    }

    public function deleteShopNotice($filter)
    {
        $noticeInfo = $this->shopNoticeRepository->getInfo($filter);

        if (!$noticeInfo) {
            throw new ResourceException('获取公告信息失败');
        }

        return $this->shopNoticeRepository->deleteBy($filter);
    }


    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->shopNoticeRepository->$method(...$parameters);
    }
}
