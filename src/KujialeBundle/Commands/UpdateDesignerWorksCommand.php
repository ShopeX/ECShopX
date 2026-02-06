<?php
namespace KujialeBundle\Commands;

use Illuminate\Console\Command;
use KujialeBundle\Services\DesignerWorksScriptService;

class UpdateDesignerWorksCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'kujiale:update';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '更新酷家乐设计师作品方案';

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
       $scriptService = new DesignerWorksScriptService();
       $scriptService->scheduleUpdateDesigner();
    }
}
