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

namespace AliyunsmsBundle\Services;
use AliyunsmsBundle\Entities\Scene;
use AliyunsmsBundle\Entities\SceneItem;
use Dingo\Api\Exception\ResourceException;

class SceneService
{
    public $sceneRepository;
    public function __construct()
    {
        $this->sceneRepository = app('registry')->getManager('default')->getRepository(Scene::class);
    }

    public function getList($filter, $cols = [], $page = 1, $pageSize = 10)
    {
        // Ref: 1996368445
        $list = $this->sceneRepository->lists($filter, $cols, $page, $pageSize);
        $scene_id = array_column($list['list'], 'id');
        $itemFilter = [
            'scene_id' => $scene_id,
            'company_id' => $filter['company_id']
        ];
        $itemList = (new SceneItemService())->getItemListByScene($itemFilter);
        $template_type = ["验证码","短信通知","推广短信"];
        foreach ($list['list'] as &$v) {
            $v['template_type'] = $template_type[$v['template_type']];
            if($itemList[$v['id']] ?? 0) {
                $v['itemList'] = $itemList[$v['id']];
            } else {
                $v['itemList']  = [];
            }
        }
        return $list;
    }
    public function getSimpleList($filter, $cols = [], $page = 1, $pageSize = 10)
    {
        return $this->sceneRepository->lists($filter, $cols, $page, $pageSize);
    }

    public function getDetail($id)
    {
        $data = $this->sceneRepository->getInfoById($id);
        if(!$data) {
            $data = [
                "default_template" => null,
                "variables" => null
            ];
        }
        if($data['variables'] ?? 0) {
            $data['variables'] = json_decode($data['variables'], true);
        }
        return $data;
    }


    /**
     * Dynamically call the CommentService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->sceneRepository->$method(...$parameters);
    }
}
