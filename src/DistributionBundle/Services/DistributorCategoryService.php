<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DistributionBundle\Services;

use DistributionBundle\Entities\DistributorCategory;

class DistributorCategoryService
{
    /**
     * @var \DistributionBundle\Repositories\DistributorCategoryRepository
     */
    public $categoryRepository;

    public function __construct()
    {
        $this->categoryRepository = app('registry')->getManager('default')->getRepository(DistributorCategory::class);
    }

    /**
     * 列表
     */
    public function lists(array $filter, int $page = 1, int $pageSize = 20, array $orderBy = ['created' => 'DESC'])
    {
        return $this->categoryRepository->lists($filter, ['*'], $page, $pageSize, $orderBy);
    }

    /**
     * 详情
     */
    public function getInfo(array $filter)
    {
        return $this->categoryRepository->getInfo($filter);
    }

    /**
     * 创建
     */
    public function create(array $data)
    {
        return $this->categoryRepository->create($data);
    }

    /**
     * 更新
     */
    public function updateOneBy(array $filter, array $data)
    {
        return $this->categoryRepository->updateOneBy($filter, $data);
    }

    /**
     * 删除
     */
    public function deleteById(int $categoryId): bool
    {
        return $this->categoryRepository->deleteById($categoryId);
    }
}
