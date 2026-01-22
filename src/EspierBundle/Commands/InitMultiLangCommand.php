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

namespace EspierBundle\Commands;

use Illuminate\Console\Command;

class InitMultiLangCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:init {lang? }';



    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '多语言初始化';



    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $lang = $this->argument('lang');
        if(empty($lang)){
            $lang = 'zh-CN';
        }
        $moduleArr = ['item','other'];
        foreach($moduleArr as $module){
            (new \CompanysBundle\MultiLang\MultiLangItem($lang,$module))->createTable();

            $this->writeLangueConfig($lang);
        }
        // (new \CompanysBundle\MultiLang\MultiLangItem('zh-CN','other'))->createTable();dd(11);
    }

    // 写入config/langue.php
    private function writeLangueConfig($lang){
        $config = config('langue');
        $config[] = $lang;
        file_put_contents(config_path('langue.php'),'<?php return '.var_export($config,true).';');
    }

}
