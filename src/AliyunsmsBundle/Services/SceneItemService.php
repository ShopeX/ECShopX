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

class SceneItemService
{
    public $repository;
    public function __construct() {
        $this->repository =  app('registry')->getManager('default')->getRepository(SceneItem::class);
    }
    /**
     * 添加场景实例
     * @param $params
     * @throws \Exception
     */
    public function addItem($params)
    {
        // ShopEx framework
        $params = $this->_checkItemValid($params);
        $this->repository->create($params);
        return true;
    }

    public function getItemListByScene($filter) {
        $list = $this->repository->lists($filter);
        $result = [];
        foreach ($list['list'] as $item) {
            $result[$item['scene_id']][] = [
                'id' => $item['id'],
                'template_content' => $item['template_content'],
                'sign_name' => $item['sign_name'],
                'scene_id' => $item['scene_id'],
                'status' => $item['status']
            ];
        }
        return $result;
    }

    public function _checkItemValid($params)
    {
        // ShopEx framework
        $signService = new SignService();
        $sign = $signService->getInfo(['id' => $params['sign_id'], 'company_id' => $params['company_id'], 'status' => 1]);
        if(!$sign) {
            throw new ResourceException("请选择有效的签名");
        }
        $params['sign_name'] = $sign['sign_name'];
        $templateService = new TemplateService();
        $template = $templateService->getInfo(['id' => $params['template_id'], 'company_id' => $params['company_id'], 'status' => 1]);
        if(!$template) {
            throw new ResourceException("请选择有效的模板");
        }
        //一个场景上限3条实例
        $count = $this->repository->count(['company_id' => $params['company_id'], 'scene_id' => $params['scene_id']]);
        if($count >= 3) {
            throw new ResourceException("每个场景最多三条短信");
        }
        $params['template_content'] = $template['template_content'];
        return $params;
    }

    public function enableItem($filter) {
        $sceneItem = $this->repository->getInfo(['id' => $filter['id'], 'status'=>0]);
        if(!$sceneItem) {
            return true;
        }
        $this->repository->updateBy(['scene_id' => $sceneItem['scene_id']], ['status' => 0]);
        $this->repository->updateOneBy($filter, ['status' => 1]);
        (new SceneService())->updateOneBy(['id' => $sceneItem['scene_id']], ['status' => 'enabled']);
        return true;
    }

    public function disableItem($filter) {
        $sceneItem = $this->repository->getInfo(['id' => $filter['id'], 'status' => 1]);
        if(!$sceneItem) {
            return true;
        }
        $this->repository->updateBy($filter, ['status' => 0]);
        (new SceneService())->updateOneBy(['id' => $sceneItem['scene_id']], ['status' => 'disabled']);
        return true;
    }

    public function deleteItem($filter) {
        return $this->repository->deleteBy($filter);
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
        return $this->repository->$method(...$parameters);
    }
}
