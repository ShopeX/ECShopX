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

namespace OpenapiBundle\Services;

use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Constants\ErrorCode;

use MembersBundle\Entities\MemberTags;
use MembersBundle\Entities\Members;
use MembersBundle\Services\MemberService;
use MembersBundle\Services\MemberTagsService;
use MembersBundle\Services\TagsCategoryService;

class MemberTagService extends BaseService
{
    public function getEntityClass(): string
    {
        return MemberTags::class;
    }

    /**
     * 格式化会员标签分类列表数据
     * @param  string $dataList 会员标签分类列表数据
     * @param  string $page     当前页数
     * @param  string $pageSize 每页条数
     * @return array           处理后的列表数据
     */
    public function formateTagCategoryList($dataList, int $page, int $pageSize)
    {
        $result = $this->handlerListReturnFormat($dataList, $page, $pageSize);
        if (!$dataList['list']) {
            return $result;
        }

        $result['list'] = [];
        foreach ($dataList['list'] as $list) {
            $result['list'][] = [
                'category_id' => $list['category_id'],
                'category_name' => $list['category_name'],
                'sort' => $list['sort'],
                'created' => date('Y-m-d H:i:s', $list['created']),
                'updated' => date('Y-m-d H:i:s', $list['updated']),
            ];
        }
        return $result;
    }

    /**
     * 创建会员标签
     * @param  array $data 标签数据
     */
    public function createTag($data)
    {
        $memberTagsService = new MemberTagsService();
        // 检查标签名是否存在
        $tagInfo = $memberTagsService->getInfo(['company_id' => $data['company_id'], 'tag_name' => $data['tag_name']]);
        if ($tagInfo) {
            throw new ErrorException(ErrorCode::MEMBER_TAG_EXIST, '该标签名已存在');
        }
        $result = $memberTagsService->create($data);
        return $this->formateMemberTagStruct($result);
    }

    /**
     * 删除会员标签
     * @param  array $filter 条件
     */
    public function deleteTag($filter)
    {
        $memberTagsService = new MemberTagsService();
        // 检查标签是否存在
        $tagInfo = $memberTagsService->getInfo(['company_id' => $filter['company_id'], 'tag_id' => $filter['tag_id']]);
        if (!$tagInfo) {
            throw new ErrorException(ErrorCode::MEMBER_TAG_NOT_FOUND, '该标签不存在');
        }
        return $memberTagsService->deleteBy($filter);
    }

    /**
     * 更新会员标签
     * @param  array $params 会员标签数据
     */
    public function updateTag($params)
    {
        $memberTagsService = new MemberTagsService();

        $filter = [
            'company_id' => $params['company_id'],
            'tag_id' => $params['tag_id'],
        ];
        // 检查标签是否存在
        $tagInfo = $memberTagsService->getInfo($filter);
        if (!$tagInfo) {
            throw new ErrorException(ErrorCode::MEMBER_TAG_NOT_FOUND, '该标签不存在');
        }
        // 检查标签名是否存在
        $tagname_filter = [
            'company_id' => $params['company_id'],
            'tag_id|neq' => $params['tag_id'],
            'tag_name' => $params['tag_name'],
        ];
        $list = $memberTagsService->getLists($tagname_filter);
        if ($list) {
            throw new ErrorException(ErrorCode::MEMBER_TAG_EXIST, '该标签名已存在');
        }
        $data = [
            'tag_name' => $params['tag_name'],
            'description' => $params['description'],
            'tag_color' => $params['tag_color'],
            'font_color' => $params['font_color'],
        ];
        if (isset($params['category_id']) && $params['category_id']) {
            $data['category_id'] = $params['category_id'];
        }
        $result = $memberTagsService->updateOneBy($filter, $data);
        return $this->formateMemberTagStruct($result);
    }

    /**
     * 格式化会员标签数据结构
     *
     * @param array $memberTagInfo 会员标签数据
     */
    public function formateMemberTagStruct($memberTagInfo): array
    {
        $result = [
            'tag_id' => $memberTagInfo['tag_id'],
            'tag_name' => $memberTagInfo['tag_name'],
            'category_id' => $memberTagInfo['category_id'],
            'description' => $memberTagInfo['description'],
            'tag_color' => $memberTagInfo['tag_color'],
            'font_color' => $memberTagInfo['font_color'],
            'created' => date('Y-m-d H:i:s', $memberTagInfo['created']),
            'updated' => date('Y-m-d H:i:s', $memberTagInfo['updated']),
        ];
        return $result;
    }


    /**
     * 格式化会员标签列表数据
     * @param  array $dataList 会员标签列表数据
     * @param  int $page          当前页数
     * @param  int $pageSize      每页条数
     * @return array                格式化后的会员标签列表数据
     */
    public function formateMemberTagList($dataList, int $page, int $pageSize)
    {
        $result = $this->handlerListReturnFormat($dataList, $page, $pageSize);
        if (!$dataList['list']) {
            return $result;
        }

        $result['list'] = [];
        foreach ($dataList['list'] as $list) {
            $_list = $this->formateMemberTagStruct($list);
            $_list['self_tag_count'] = $list['self_tag_count'];
            $result['list'][] = $_list;
        }
        return $result;
    }

    /**
     * 批量为会员打标签(覆盖)
     * @param  array $mobiles   会员手机号
     * @param  array $tagIds    标签ID
     * @param  string $companyId 企业ID
     */
    public function batchCoverMembersTags($mobiles, $tagIds, $companyId)
    {
        // 根据mobile，查询user_id
        $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $filter = [
            'company_id' => $companyId,
            'mobile' => $mobiles,
        ];
        $memberList = $membersRepository->lists($filter);
        $_memberList = array_column($memberList['list'], null, 'mobile');
        $user_ids = [];
        foreach ($mobiles as $mobile) {
            if (!isset($_memberList[$mobile])) {
                throw new ErrorException(ErrorCode::MEMBER_NOT_FOUND, $mobile.'会员不存在');
            }
            $user_ids[] = $_memberList[$mobile]['user_id'];
        }
        // 根据tagIds 查询有效的tagIds
        $memberTagsService = new MemberTagsService();
        $filter = [
            'company_id' => $companyId,
            'tag_id' => $tagIds,
        ];
        $tagList = $memberTagsService->getListTags($filter, 1, -1);
        $_tagIds = array_column($tagList['list'], 'tag_id');
        foreach ($tagIds as $tagid) {
            if (!in_array($tagid, $_tagIds)) {
                throw new ErrorException(ErrorCode::MEMBER_TAG_NOT_FOUND, $tagid.'标签不存在');
            }
        }
        foreach ($user_ids as $user_id) {
            $memberTagsService->createRelTagsByUserId($user_id, $_tagIds, $companyId);
        }

        return true;
    }

    /**
     * 批量为会员打标签(不覆盖)
     * @param  array $mobiles   会员手机号
     * @param  array $tagIds    标签ID
     * @param  string $companyId 企业ID
     */
    public function batchUpdateMembersTags($mobiles, $tagIds, $companyId)
    {
        // 根据mobile，查询user_id
        $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $filter = [
            'company_id' => $companyId,
            'mobile' => $mobiles,
        ];
        $memberList = $membersRepository->lists($filter);
        $_memberList = array_column($memberList['list'], null, 'mobile');
        $user_ids = [];
        foreach ($mobiles as $mobile) {
            if (!isset($_memberList[$mobile])) {
                throw new ErrorException(ErrorCode::MEMBER_NOT_FOUND, $mobile.'会员不存在');
            }
            $user_ids[] = $_memberList[$mobile]['user_id'];
        }
        // 根据tagIds 查询有效的tagIds
        $memberTagsService = new MemberTagsService();
        $filter = [
            'company_id' => $companyId,
            'tag_id' => $tagIds,
        ];
        $tagList = $memberTagsService->getListTags($filter, 1, -1);
        $_tagIds = array_column($tagList['list'], 'tag_id');
        foreach ($tagIds as $tagid) {
            if (!in_array($tagid, $_tagIds)) {
                throw new ErrorException(ErrorCode::MEMBER_TAG_NOT_FOUND, $tagid.'标签不存在');
            }
        }
        return $memberTagsService->createRelTags($user_ids, $_tagIds, $companyId);
    }

    /**
     * 未单个会员批量删除会员已打标签
     * @param  string $mobile   会员手机号
     * @param  string $tagIds     会员标签ID集合
     * @param  string $companyId 企业ID
     */
    public function userRelTagDelete($mobile, $tagIds, $companyId)
    {
        // 根据mobile，查询user_id
        $memberService = new MemberService();
        $user_id = $memberService->getUserIdByMobile($mobile, $companyId);
        if (!$user_id) {
            throw new ErrorException(ErrorCode::MEMBER_NOT_FOUND);
        }

        // 根据tagIds 查询标签是否有效
        $memberTagsService = new MemberTagsService();
        $filter = [
            'company_id' => $companyId,
            'tag_id' => $tagIds,
            'user_id' => $user_id,
        ];
        $tagList = $memberTagsService->getListTags($filter, 1, -1);
        $tag_ids = [];
        if ($tagList['list']) {
            $tag_ids = array_column($tagList['list'], 'tag_id');
        }
        if (!$tag_ids) {
            return true;
        }
        return $memberTagsService->userRelTagDelete($companyId, [$user_id], $tag_ids);
    }

    public function getUserTaggedList($companyId, $mobile)
    {
        // 根据mobile查询user_id
        $memberService = new MemberService();
        $filter = [
            'company_id' => $companyId,
            'mobile' => $mobile,
        ];
        $memberInfo = $memberService->getMemberInfo($filter, false);
        if (!$memberInfo) {
            throw new ErrorException(ErrorCode::MEMBER_NOT_FOUND);
        }

        $memberTagsService = new MemberTagsService();
        $filter = [
            'company_id' => $companyId,
            'user_id' => $memberInfo['user_id'],
        ];
        $taggedList = $memberTagsService->getUserRelTagList($filter);
        $result = [];
        if (!$taggedList) {
            return $result;
        }
        foreach ($taggedList as $list) {
            $result[] = [
                'mobile' => $memberInfo['mobile'],
                'username' => $memberInfo['username'],
                'tag_id' => $list['tag_id'],
                'tag_name' => $list['tag_name'],
                'category_id' => $list['category_id'],
                'description' => $list['description'],
                'tag_color' => $list['tag_color'],// rgba(0, 206, 209, 1)
                'font_color' => $list['font_color'],// rgba(16, 1, 1, 1)
            ];
        }
        return $result;
    }

    /**
     * 根据标签ID，查询标签下关联的会员列表（分页）
     * @param  string $companyId 企业ID
     * @param  int $tagId     标签ID
     * @param  int $page      当前页数
     * @param  [type] $pageSize  每页条数
     * @return array
     */
    public function getTagMembers($companyId, int $tagId, int $page, int $pageSize)
    {
        $filter = [
            'company_id' => $companyId,
            'tag_id' => $tagId,
        ];
        $memberTagService = new MemberTagsService();
        // 检查标签是否存在
        $tagInfo = $memberTagService->getInfo($filter);
        if (!$tagInfo) {
            throw new ErrorException(ErrorCode::MEMBER_TAG_NOT_FOUND, '该标签不存在');
        }
        $memberService = new MemberService();
        $memberList = $memberService->getMemberList($filter, $page, $pageSize);
        $total_count = $memberService->getMemberCount($filter);
        $dataList = [
            'total_count' => $total_count,
            'list' => $memberList,
        ];
        $result = $this->handlerListReturnFormat($dataList, $page, $pageSize);

        if (!$memberList) {
            return $result;
        }
        $result['list'] = [];
        // 根据user_ids查询关联的会员标签
        $userIds = array_column($memberList, 'user_id');
        $tagFilter = [
            'company_id' => $companyId,
            'user_id' => $userIds,
        ];
        $tagList = $memberTagService->getUserRelTagList($tagFilter);
        $newTags = [];
        foreach ($tagList as $tag) {
            $newTags[$tag['user_id']][] = $tag;
        }

        foreach ($memberList as $list) {
            $_tagList = [];
            foreach ($newTags[$list['user_id']] as $tag_list) {
                $_tagList[] = [
                    'tag_id' => $tag_list['tag_id'],
                    'tag_name' => $tag_list['tag_name'],
                ];
            }

            $result['list'][] = [
                'mobile' => $list['mobile'],
                'username' => $list['username'] ?? '',
                'tag_list' => $_tagList,
            ];
        }
        return $result;
    }

    /**
     * 创建会员标签分类
     * @param  array $data 保存数据
     */
    public function createCategory($data)
    {
        $tagsCategoryService = new TagsCategoryService();
        // 检查分类名称是否存在
        $tagsCategoryInfo = $tagsCategoryService->getInfo(['company_id' => $data['company_id'], 'category_name' => $data['category_name']]);
        if ($tagsCategoryInfo) {
            throw new ErrorException(ErrorCode::MEMBER_TAGCATEGORY_EXIST);
        }
        $result = $tagsCategoryService->saveCategory($data);
        $return = [
            'category_id' => $result['category_id'],
            'category_name' => $result['category_name'],
            'sort' => $result['sort'],
            'created' => date('Y-m-d H:i:s', $result['created']),
            'updated' => date('Y-m-d H:i:s', $result['updated']),
        ];
        return $return;
    }

    /**
     * 修改会员标签分类
     * @param  array $data 保存数据
     */
    public function updateCategory($data)
    {
        $tagsCategoryService = new TagsCategoryService();
        // 检查分类是否存在
        $tagsCategoryInfo = $tagsCategoryService->getInfo(['company_id' => $data['company_id'], 'category_id' => $data['category_id']]);
        if (!$tagsCategoryInfo) {
            throw new ErrorException(ErrorCode::MEMBER_TAGCATEGORY_NOT_FOUND);
        }
        // 检查分类名称是否存在
        $category_filter = [
            'company_id' => $data['company_id'],
            'category_id|neq' => $data['category_id'],
            'category_name' => $data['category_name'],
        ];
        $list = $tagsCategoryService->getLists($category_filter);
        if ($list) {
            throw new ErrorException(ErrorCode::MEMBER_TAGCATEGORY_EXIST);
        }
        $filter = [
            'company_id' => $data['company_id'],
            'category_id' => $data['category_id'],
        ];
        $_data = [
            'category_name' => $data['category_name'],
        ];
        $data['sort'] and $_data['sort'] = intval($data['sort']);
        $result = $tagsCategoryService->saveCategory($_data, null, $filter);
        $return = [
            'category_id' => $result['category_id'],
            'category_name' => $result['category_name'],
            'sort' => $result['sort'],
            'created' => date('Y-m-d H:i:s', $result['created']),
            'updated' => date('Y-m-d H:i:s', $result['updated']),
        ];
        return $return;
    }

    /**
     * 删除会员标签分类
     * @param  array $filter 删除条件
     */
    public function deleteCategory($filter)
    {
        $tagsCategoryService = new TagsCategoryService();
        // 检查分类是否存在
        $tagsCategoryInfo = $tagsCategoryService->getInfo($filter);
        if (!$tagsCategoryInfo) {
            throw new ErrorException(ErrorCode::MEMBER_TAGCATEGORY_NOT_FOUND);
        }
        return $tagsCategoryService->deleteCategory($filter);
    }

    /**
     * 获取会员分类标签列表
     * @param  array $params 查询参数
     * @return [type]         [description]
     */
    public function getCategoryList($params)
    {
        $tagsCategoryService = new TagsCategoryService();
        $filter = ['company_id' => $params['company_id']];
        if ($params['category_name']) {
            $filter['category_name|contains'] = $params['category_name'];
        }
        $result = $tagsCategoryService->lists($filter, 'category_name,category_id,sort,created,updated', $params['page'], $params['page_size']);
        $return = $this->formateTagCategoryList($result, (int)$params['page'], (int)$params['page_size']);
        return $return;
    }
}
