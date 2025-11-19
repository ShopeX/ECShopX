<?php

/*
|--------------------------------------------------------------------------
| openapi 接口
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

$api->version('v1', function($api) {
	
    $api->group(['namespace' => 'AdaPayBundle\Http\FrontApi\V1\Action','prefix'=>'systemlink'], function($api) {
        
        $api->post('adapay/agent/callback', ['as' => 'adapay.agent.callback',  'uses'=>'CallBack@handle']);
        
    });
    
});

