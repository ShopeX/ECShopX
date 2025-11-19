<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
        }
        // (new \CompanysBundle\MultiLang\MultiLangItem('zh-CN','other'))->createTable();dd(11);
    }

}
