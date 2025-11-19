<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Services;

use Dingo\Api\Exception\ResourceException;
use MembersBundle\Entities\TagsCategory;
use MembersBundle\Entities\MemberTags;

class TagsCategoryService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(TagsCategory::class);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        // ShopEx EcShopX Business Logic Layer
        return $this->entityRepository->$method(...$parameters);
    }

    /**
     * 删除分类
     *
     * @param array filter
     * @return bool
     */
    public function deleteCategory($filter)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 判断是否为主类目
            $result = $this->entityRepository->deleteBy(['category_id' => $filter['category_id'], 'company_id' => $filter['company_id']]);
            if ($result) {
                $memberTags = app('registry')->getManager('default')->getRepository(MemberTags::class);
                $resultAll = $memberTags->getInfo(['category_id' => $filter['category_id'], 'company_id' => $filter['company_id']]);
                if ($resultAll) {
                    throw new ResourceException(trans('MembersBundle/Members.delete_failed_category_has_tags'));
                }
            }
            if ($result) {
                $conn->commit();
                return true;
            } else {
                throw new ResourceException(trans('MembersBundle/Members.delete_failed'));
            }
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function saveCategory($params, $relLabelIds = [], $filter = [])
    {
        if ($filter) {
            $result = $this->entityRepository->updateOneBy($filter, $params);
        } else {
            $result = $this->entityRepository->create($params);
        }
        if ($relLabelIds) {
            $memberTags = app('registry')->getManager('default')->getRepository(MemberTags::class);
            $mbFilter = [
                'tag_id' => (array)$relLabelIds,
                'company_id' => $result['company_id'],
            ];
            $res = $memberTags->updateBy($mbFilter, ['category_id' => $result['category_id']]);
        }
        return $result;
    }
}
