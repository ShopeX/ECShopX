<?php

// 开启分析
# xhprof_enable(XHPROF_FLAGS_NO_BUILTINS | XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| First we need to get an application instance. This creates an instance
| of the application / container and bootstraps the application so it
| is ready to receive HTTP / Console requests from the environment.
|
*/

$app = require __DIR__.'/../bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
*/

$app->run();

// 结束分析
# $xhprof_data = xhprof_disable();
# $XHPROF_ROOT = realpath(dirname(__FILE__) .'/..');
# include_once $XHPROF_ROOT . "/xhprof/xhprof_lib/utils/xhprof_lib.php";
# include_once $XHPROF_ROOT . "/xhprof/xhprof_lib/utils/xhprof_runs.php";
# $xhprof_runs = new XHProfRuns_Default();
# $run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_test"); # 保存分析结果，默认存储在 `sys_get_temp_dir()` 目录中
