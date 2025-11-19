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
    // 微信相关信息
    $api->group(['namespace' => 'DepositBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/deposit/rechargerule',        ['name' => '创建充值面额规则','middleware'=>'activated',  'as' => 'deposit.rule.create',  'uses'=>'Recharge@createRechargeRule']);
        $api->get('/deposit/rechargerules',        ['name' => '获取充值面额规则','middleware'=>'activated',  'as' => 'deposit.rule.list',    'uses'=>'Recharge@getRechargeRuleList']);
        $api->delete('/deposit/rechargerule/{id}', ['name' => '根据ID删除充值面额规则','middleware'=>'activated',  'as' => 'deposit.rule.delete',  'uses'=>'Recharge@deleteRechargeRuleById']);
        $api->put('/deposit/rechargerule',         ['name' => '编辑指定充值面额规则','middleware'=>'activated',  'as' => 'deposit.rule.edit',    'uses'=>'Recharge@editRechargeRuleById']);

        $api->post('/deposit/recharge/agreement',   ['name' => '设置储值协议','middleware'=>'activated',    'as' => 'deposit.recharge.agreement.set',  'uses'=>'Recharge@setRechargeAgreement']);
        $api->get('/deposit/recharge/agreement',    ['name' => '获取储值协议','middleware'=>'activated',    'as' => 'deposit.recharge.agreement.get',  'uses'=>'Recharge@getRechargeAgreementByCompanyId']);
        $api->post('/deposit/recharge/multiple',   ['name' => '设置充值送积分','middleware'=>'activated',    'as' => 'deposit.recharge.multiple.set',  'uses'=>'Recharge@setRechargeMultiple']);
        $api->get('/deposit/recharge/multiple',    ['name' => '获取充值送积分信息','middleware'=>'activated',    'as' => 'deposit.recharge.multiple.get',  'uses'=>'Recharge@getRechargeMultipleByCompanyId']);
        $api->get('/deposit/trades',                ['name' => '获取储值交易记录', 'middleware' => ['activated', 'datapass'], 'as' => 'deposit.trades',                  'uses'=>'Recharge@getDepositTradeList']);
        $api->get('/deposit/count/index',           ['name' => '获取储值统计页数据',  'middleware' => 'activated', 'as' => 'deposit.count.index',             'uses'=>'Recharge@getDepositCountIndex']);
        $api->post('/deposit/recharge',           ['name' => '后台充值',  'middleware' => 'activated', 'as' => 'deposit.recharge',             'uses'=>'Recharge@recharge']);
    });
});

