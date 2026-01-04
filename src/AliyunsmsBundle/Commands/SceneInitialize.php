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

namespace AliyunsmsBundle\Commands;

use Illuminate\Console\Command;
use AliyunsmsBundle\Entities\Scene;
use CompanysBundle\Services\CompanysService;

class SceneInitialize extends Command
{
    /**
     * 命令行执行命令
     * php artisan aliyunsms:scene:initialize 1
     * @var string
     */
    protected $signature = 'aliyunsms:scene:initialize {company_id}';


    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '初始化短信场景 参数：companyId';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $companyId = $this->argument('company_id');
        $company = (new CompanysService())->getInfo(['company_id'=> $companyId]);
        if(!$company) {
            echo "companyId 不存在";
            return true;
        }
        try {
            $input = file_get_contents(storage_path('static/sms_scene.json'));
            $input = json_decode($input, true);
        } catch (\Exception $e) {
            echo "读取json文件出错".$e->getMessage();
            return true;
        }
        if (!$input) {
            echo "未读取到模板json文件";
            return true;
        }
        /** @var \AliyunsmsBundle\Repositories\SceneRepository $repository */
        $repository = app('registry')->getManager('default')->getRepository(Scene::class);
        //判断是否执行过
        if($repository->getInfo(['company_id' => $companyId])) {
            // return true;
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        //template_type: 0-验证码; 1-短信通知; 2-推广短信
        try {
            foreach ($input as $item) {
                $filter = [
                    'company_id' => $companyId,
                    'scene_title' => $item['scene_title'],
                    'template_type' => $item['template_type'],
                ];
                if ($repository->getInfo($filter)) {
                    continue;//不重复添加短信模板
                }
                $tmp = [
                    'company_id' => $companyId,
                    'scene_name' => $item['scene_name'],
                    'scene_title' => $item['scene_title'],
                    'template_type' => $item['template_type'],
                    'default_template' => $item['default_template'] ?? null,
                ];
                if($item['variables'] ?? 0) {
                    $tmp['variables'] = json_encode($item['variables']);
                }
                $repository->create($tmp);
                $this->line('add template success:' . $item['scene_title']);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            echo "导入短信场景数据出错：".$e->getMessage();
            return true;
        }
        echo "操作完成".PHP_EOL;
    }

    public function menusLangue()
    {
        $shopMenuRepository = app('registry')->getManager('default')->getRepository(\SuperAdminBundle\Entities\ShopMenu::class);
        $listsData = $shopMenuRepository->lists([],"*",1, 1000,['pid' => 'asc','sort' => 'asc'] );
        $ns = new \CompanysBundle\Services\CommonLangModService();
        $classObjEn = $ns->getLangMapRepository('en-CN');
        $classObjZH = $ns->getLangMapRepository('zh-CN');
        foreach ($listsData['list'] as $item) {
            foreach (['name','alis_name'] as $field) {
                $insertCN = [
                    'company_id' => $item['company_id'],
                    'table_name' => 'shop_menu',
                    'module_name' => 'shop_menu',
                    'lang' => 'zh-CN',
                    'attribute_value' => $item['name'],
                    'field' => $field,
                    'data_id' => $item['shopmenu_id'],
                    'created' => time(),
                    'updated' => time(),
                ];
                $insertEN = [
                    'company_id' => $item['company_id'],
                    'table_name' => 'shop_menu',
                    'module_name' => 'shop_menu',
                    'lang' => 'en-CN',
                    'attribute_value' => $item['alias_name'],
                    'field' => $field,
                    'data_id' => $item['shopmenu_id'],
                    'created' => time(),
                    'updated' => time(),
                ];
                $classObjEn->create($insertEN);
                $classObjZH->create($insertCN);
            }
        }
    }

}
