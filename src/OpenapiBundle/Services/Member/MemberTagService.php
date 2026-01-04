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

namespace OpenapiBundle\Services\Member;

use MembersBundle\Entities\MemberTags;
use MembersBundle\Services\MemberTagsService;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Services\BaseService;

class MemberTagService extends BaseService
{
    public function getEntityClass(): string
    {
        return MemberTags::class;
    }

    /**
     * 检查标签是否存在
     * @param int $companyId 企业id
     * @param array $tagNames 标签名
     * @param array $tagIds 标签id
     * @return $this
     */
    protected function checkTags(int $companyId, array $tagNames, array &$tagIds): self
    {
        // 过滤空值
        $tagNames = array_filter($tagNames);
        $tagIds = array_filter($tagIds);
        if (empty($tagNames) && empty($tagIds)) {
            return $this;
        }
        // 将标签名和标签id的key-value做转换
        $transformTagsName = [];
        $transformTagsId = [];
        foreach ($tagNames as $tagName) {
            $transformTagsName[$tagName] = true;
        }
        foreach ($tagIds as $tagsId) {
            $transformTagsId[$tagsId] = true;
        }
        // 根据标签的名字或者标签id去查询对应的数据
        $data = $this->getRepository()->getListsByTagNamesOrTagIds($companyId, $tagNames, $tagIds, "tag_id,company_id,tag_name");
        // 将已存在的标签就移除操作
        foreach ($data as $datum) {
            $tagId = $datum["tag_id"];
            $tagName = $datum["tag_name"];
            unset($transformTagsName[$tagId]);
            unset($transformTagsId[$tagName]);
        }
        // 对剩下没有找到的标签做报错处理
        foreach ($transformTagsName as $tagName => $value) {
            throw new ErrorException(ErrorCode::MEMBER_TAG_NOT_FOUND, sprintf("标签名为:%s, 标签不存在！", $tagName));
        }
        foreach ($transformTagsId as $tagId => $value) {
            throw new ErrorException(ErrorCode::MEMBER_TAG_NOT_FOUND, sprintf("标签id为:%s, 标签不存在！", $tagId));
        }
        $tagIds = (array)array_column($data, "tag_id");
        return $this;
    }

    public function create(array $createData): array
    {
        $tagNames = (array)($createData["tag_name"] ?? []);
        $tagsIds = (array)($createData["tag_id"] ?? []);
        $userId = (int)($createData["user_id"]);
        $companyId = (int)($createData["company_id"]);
        $this->checkTags($companyId, $tagNames, $tagsIds);
        (new MemberTagsService())->createRelTags([$userId], $tagsIds, $companyId, true);
        return [];
    }
}
