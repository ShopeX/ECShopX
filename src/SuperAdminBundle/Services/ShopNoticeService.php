<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
