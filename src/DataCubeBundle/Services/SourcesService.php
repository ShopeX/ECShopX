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

namespace DataCubeBundle\Services;

use DataCubeBundle\Entities\Sources;
use Dingo\Api\Exception\ResourceException;
use WechatBundle\Services\OpenPlatform;

class SourcesService
{
    /** @var sourcesRepository */
    private $sourcesRepository;

    /** @var openPlatform */
    private $openPlatform;

    /**
     * SourcesService 构造函数.
     */
    public function __construct()
    {
        $this->openPlatform = new OpenPlatform();
        $this->sourcesRepository = app('registry')->getManager('default')->getRepository(Sources::class);
    }

    /**
     * 添加来源
     *
     * @param array params 来源数据
     * @return array
     */
    public function addSources(array $params)
    {
        // 456353686f7058
        $data = [
            'source_name' => $params['source_name'],
            'company_id' => $params['company_id'],
            'tags_id' => $params['tags_id'] ?? '',
        ];
        $rs = $this->sourcesRepository->create($data);

        return $rs;
    }

    /**
     * 删除来源
     *
     * @param array filter
     * @return bool
     */
    public function deleteSources($filter)
    {
        $sourcesInfo = $this->sourcesRepository->get($filter['source_id']);

        if ($filter['company_id'] != $sourcesInfo['company_id']) {
            throw new ResourceException('删除来源信息有误.');
        }
        if (!$filter['source_id']) {
            throw new ResourceException('来源id不能为空.');
        }

        return $this->sourcesRepository->delete($filter['source_id']);
    }

    /**
     * 获取来源详情
     *
     * @param inteter source_id 来源id
     * @return array
     */
    public function getSourcesDetail($source_id)
    {
        $sourceInfo = $this->sourcesRepository->get($source_id);

        return $sourceInfo;
    }

    /**
     * 获取来源列表
     *
     * @param array filter
     * @return array
     */
    public function getSourcesList($filter, $page, $pageSize, $orderBy = ['source_id' => 'DESC'])
    {
        $page = ($page < 1) ? 1 : $page;
        $pageSize = ($pageSize > 1000) ? 1000 : $pageSize;
        $pageSize = ($pageSize <= 0) ? 10 : $pageSize;
        $sourcesList = $this->sourcesRepository->list($filter, $orderBy, $pageSize, $page);

        return $sourcesList;
    }

    /**
     * 修改来源
     *
     * @param array params 提交的门店数据
     * @return array
     */
    public function updateSources($params)
    {
        $sourcesInfo = $this->sourcesRepository->get($params['source_id']);

        if ($params['company_id'] != $sourcesInfo['company_id']) {
            throw new ResourceException('请确认您的门店信息后再提交.');
        }
        $data = [
            'source_name' => $params['source_name'],
            'company_id' => $params['company_id'],
            'tags_id' => $params['tags_id'] ?? '',
        ];

        $rs = $this->sourcesRepository->update($params['source_id'], $data);

        return $rs;
    }

    public function updateBy($filter, $params)
    {
        return $this->sourcesRepository->updateBy($filter, $params);
    }

    public function patchSaveTags($filter, $params)
    {
        $lists = $this->sourcesRepository->list($filter, [], -1, 1);
        $tags = $params['tags_id'];
        foreach ($lists['list'] as $data) {
            if ($data['tagsId']) {
                $tagsId = json_decode($data['tagsId'], true);
                $params['tags_id'] = array_unique(array_merge($tagsId, $tags));
            } else {
                $params['tags_id'] = $tags;
            }
            $params['tags_id'] = json_encode($params['tags_id']);
            $filter = [
                'source_id' => $data['sourceId'],
                'company_id' => $data['companyId'],
            ];
            $this->sourcesRepository->updateBy($filter, $params);
        }
        return true;
    }
}
