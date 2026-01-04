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
use AliyunsmsBundle\Entities\Sign;
use AliyunsmsBundle\Entities\Template;
use AliyunsmsBundle\Jobs\AddSmsTemplate;
use AliyunsmsBundle\Jobs\DeleteSmsSign;
use AliyunsmsBundle\Jobs\DeleteSmsTemplate;
use AliyunsmsBundle\Jobs\ModifySmsTemplate;
use AliyunsmsBundle\Jobs\QuerySmsTemplate;
use Dingo\Api\Exception\ResourceException;

class TemplateService
{
    public $templateRepository;
    public function __construct()
    {
        $this->templateRepository = app('registry')->getManager('default')->getRepository(Template::class);
    }
    /**
     * 新增template
     * @param $params
     * @throws \Exception
     */
    public function addTemplate($params)
    {
        $this->_checkValid($params);
        $template_code = (new AddSmsTemplate($params))->handle();
        $params['template_code'] = $template_code;
        $rs = $this->templateRepository->create($params);
        return true;
    }

    /**
     * 修改sign
     * @param $params
     * @throws \Exception
     */
    public function modifyTemplate($params)
    {
        $template_code = $this->_checkValid($params);
        if(!$template_code) {
            throw new ResourceException("当前模板code未同步");
        }
        $filter['company_id'] = $params['company_id'];
        $filter['id'] = $params['id'];
        unset($params['id']);
        $params['status'] = 0;
        $params['id'] = $filter['id'];
        $this->templateRepository->updateOneBy($filter, $params);
        $params['template_code'] = $template_code;
        $queue = (new ModifySmsTemplate($params))->onQueue('sms');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);
        return true;
    }

    /**
     * 删除template
     * @param $id
     * @throws \Exception
     */
    public function deleteTemplate($params)
    {
        $template = $this->templateRepository->getInfo($params);
        if(!$template) {
            return true;
        }
        if($template['status'] == 0) {
            throw new ResourceException("不支持删除正在审核中的模板");
        }
        //判断是否关联短信场景
        $sceneItemService = new SceneItemService();
        $sceneItem = $sceneItemService->getInfo(['company_id' => $params['company_id'],'template_id' => $params['id']]);
        if($sceneItem) {
            throw new ResourceException("不能删除已关联短信场景的模板");
        }
        //判断是否关联执行中的群发任务
        $taskService = new TaskService();
        $task = $taskService->getInfo(['company_id' => $params['company_id'],'template_id' => $params['id'], 'status' => 1]);
        if($task) {
            throw new ResourceException("不能删除关联群发任务的模板");
        }
        $this->templateRepository->deleteBy($params);
        $params['template_code'] = $template['template_code'];
        $queue = (new DeleteSmsTemplate($params))->onQueue('sms');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);
        return true;
    }
    private function _checkValid($params)
    {
        //变量校验
        $scene = (new SceneService())->getDetail($params['scene_id']);
        preg_match_all("/\\$\{(.+?)\}/", $params['template_content'],$result);
        if($scene['template_type'] == 2) {
            if($result[1]) {
                throw new ResourceException('推广类模板不能包含变量');
            }
        } else {
            if($scene['variables']) {
                $variables = array_column($scene['variables'], 'var_title');
                if(count($result[1]) != count(array_unique($result[1]))) {
                    throw new ResourceException("变量不能重复");
                }
                foreach ($result[1] as $var) {
                    if(!in_array($var, $variables)) {
                        throw new ResourceException("\${".$var . "} 无效变量");
                    }
                }
            }
        }
        if($params['id'] ?? 0) {
            $template = $this->templateRepository->getInfo(['id' => $params['id'], 'status' => 2]);
            if(!$template) {
                throw new ResourceException("未通过审核的模板才能修改");
            }
            return $template['template_code'];
        }
    }

    public function getList($filter, $cols = [], $page = 1, $pageSize = 10, $orderBy = ['created' => 'DESC'])
    {
        $data = $this->templateRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        if(!$data['list']) return $data;
        $sceneFilter['id'] = array_column($data['list'],'scene_id');
        $sceneList = (new SceneService())->lists($sceneFilter,['id','scene_name'],0);
        $sceneList = array_column($sceneList['list'], NULL, 'id');
        //获取关联的场景名称
        foreach ($data['list'] as &$v) {
            $v['scene_name'] = $sceneList[$v['scene_id']]['scene_name'] ?? '';
        }
        return $data;
    }

    //查询审核状态
    public function queryAuditStatus()
    {
        //获取审核中的列表, 调阿里云接口查询状态
        $list = $this->getList(['status' => 0, 'template_code|neq' => ''],[],0);
        foreach ($list['list'] as $row) {
            $params = ['template_code' => $row['template_code'], 'company_id' => $row['company_id']];
            $queue = (new QuerySmsTemplate($params))->onQueue('sms');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);
        }
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
        return $this->templateRepository->$method(...$parameters);
    }
}
