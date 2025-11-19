<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

$api->version('v1', function($api) {
    $api->group(['namespace' => 'PopularizeBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/popularize/config', ['name'=>'获取分销配置信息','as' => 'popularize.config.get', 'uses' => 'SettingController@getConfig']);
        $api->post('/popularize/config', ['name'=>'设置分销配置信息','as' => 'popularize.config.set', 'uses' => 'SettingController@setConfig']);
        $api->get('/popularize/promoter/config', ['name'=>'获取推广员等级','as' => 'popularize.promoter.config.get', 'uses' => 'SettingController@getPromoterGradeConfig']);
        $api->post('/popularize/promoter/config', ['name'=>'设置推广员等级','as' => 'popularize.promoter.config.set', 'uses' => 'SettingController@setPromoterGradeConfig']);

        // 推广员身份
        $api->get('/popularize/promoter/identity/list', ['name'=>'获取推广员身份列表',  'middleware' => ['datapass'], 'as' => 'popularize.promoter.identity.list', 'uses' => 'PromoterIdentityController@getPromoteridentityList']);
        $api->get('/popularize/promoter/identity/info', ['name'=>'获取推广员身份详情',  'middleware' => ['datapass'], 'as' => 'popularize.promoter.identity.info', 'uses' => 'PromoterIdentityController@getPromoteridentityInfo']);
        $api->post('/popularize/promoter/identity', ['name'=>'保存推广员身份',  'middleware' => ['datapass'], 'as' => 'popularize.promoter.identity.save', 'uses' => 'PromoterIdentityController@savePromoteridentity']);
        $api->delete('/popularize/promoter/identity', ['name'=>'删除推广员身份',  'middleware' => ['datapass'], 'as' => 'popularize.promoter.identity.delete', 'uses' => 'PromoterIdentityController@deletePromoteridentity']);    
        $api->post('/popularize/promoter/identity/default', ['name'=>'默认推广员身份',  'middleware' => ['datapass'], 'as' => 'popularize.promoter.identity.default', 'uses' => 'PromoterIdentityController@defaultPromoteridentity']);    

        $api->post('/popularize/promoter/add', ['name'=>'指定会员成为顶级推广员','as' => 'popularize.promoter.add', 'uses' => 'PromoterController@addPromoter']);
        $api->get('/popularize/promoter/children', ['name'=>'获取推广员直属下级列表',  'middleware' => ['datapass'], 'as' => 'popularize.promoter.children.list', 'uses' => 'PromoterController@getPromoterchildrenList']);
        $api->get('/popularize/promoter/list', ['name'=>'获取推广员列表', 'middleware' => ['datapass'], 'as' => 'popularize.promoter.list.get', 'uses' => 'PromoterController@getPromoterList']);
        $api->get('/popularize/promoter/export', ['name'=>'导出推广员业绩', 'middleware' => ['datapass'], 'as' => 'popularize.promoter.export', 'uses' => 'PromoterController@exportPromoterList']);
        $api->get('/popularize/promoter/exportPopularizeOrder', ['name'=>'导出推广员业绩', 'middleware' => ['datapass'], 'as' => 'popularize.promoter.exportPopularizeOrder', 'uses' => 'PromoterController@exportPopularizeOrder']);
        $api->get('/popularize/promoter/exportPopularizeStatic', ['name'=>'导出推广员业绩', 'middleware' => ['datapass'], 'as' => 'popularize.promoter.exportPopularizeStatic', 'uses' => 'PromoterController@exportPopularizeStatic']);
        $api->put('/popularize/promoter/grade', ['name'=>'推广员等级调整','as' => 'popularize.promoter.grade.put', 'uses' => 'PromoterController@updatePromoterGrade']);
        $api->put('/popularize/promoter/disabled', ['name'=>'禁用/激活推广员','as' => 'popularize.promoter.disabled', 'uses' => 'PromoterController@updatePromoterDisabled']);
        $api->put('/popularize/promoter/remove', ['name'=>'调整推广员上下级关系','as' => 'popularize.promoter.remove', 'uses' => 'PromoterController@updatePromoterRemove']);
        $api->put('/popularize/promoter/shop', ['name'=>'对推广员的店铺状态进行更新','as' => 'popularize.promoter.shop.update', 'uses' => 'PromoterController@updatePromoterShop']);
        $api->get('/popularize/promoter/firstidentitylist', ['name'=>'获取推广员一级身份列表',  'middleware' => ['datapass'], 'as' => 'popularize.promoter.firstidentitylist', 'uses' => 'PromoterController@getFirstIdentityPromoter']);
        $api->put('/popularize/promoter/member/remove', ['name'=>'调整会员上级关系','as' => 'popularize.promoter.member.remove', 'uses' => 'PromoterController@updateMemberPromoterRemove']);
        

        $api->put('/popularize/cash_withdrawals/{cash_withdrawal_id}', ['name'=>'处理推广员佣金提现申请','as' => 'popularize.cash_withdrawals.process', 'uses' => 'BrokerageController@processCashWithdrawal']);
        $api->get('/popularize/cashWithdrawals', ['name'=>'获取佣金提现列表', 'middleware' => ['datapass'], 'as' => 'popularize.cash_withdrawals.list.get', 'uses' => 'BrokerageController@getCashWithdrawalList']);
        $api->get('/popularize/cashWithdrawal/payinfo/{cash_withdrawal_id}', ['name'=>'获取佣金提现支付信息','as' => 'popularize.cash_withdrawals.pay.list.get', 'uses' => 'BrokerageController@getMerchantTradeList']);

        $api->get('/popularize/brokerage/count', ['name'=>'获取佣金统计','as' => 'popularize.brokerage.count', 'uses' => 'BrokerageController@brokerageCount']);
        $api->get('/popularize/brokerage/logs', ['name'=>'获取佣金记录','as' => 'popularize.brokerage.logs', 'uses' => 'BrokerageController@getBrokerageList']);

        $api->get('/popularize/taskBrokerage/logs', ['name'=>'获取任务佣金记录', 'middleware' => ['datapass'], 'as' => 'popularize.task.brokerage.logs', 'uses' => 'BrokerageController@getTaskBrokerageList']);
        $api->get('/popularize/taskBrokerage/count', ['name'=>'获取任务佣金统计', 'middleware' => ['datapass'], 'as' => 'popularize.task.brokerage.count', 'uses' => 'BrokerageController@getTaskBrokerageCountList']);
        $api->get('/popularize/export/taskBrokerage/count', ['name'=>'获取任务佣金统计', 'middleware' => ['datapass'], 'as' => 'popularize.task.brokerage.count.export', 'uses' => 'BrokerageController@exportTaskBrokerageCount']);
    });
});

