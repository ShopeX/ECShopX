<?php

/*
|--------------------------------------------------------------------------
| JushuitanErp 接口
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

$api->version('v1', function($api) {
	
    $api->group(['namespace' => 'SystemLinkBundle\Http\JushuitanApi\V1\Action','prefix'=>'systemlink','middleware' => ['JushuitanCheck']], function($api) {
        // JushuitanErp api
        $api->post('jushuitan/{companyId}', ['as' => 'jushuitan.api',  'uses'=>'Verify@jushuitanApi']);

    });

    $api->group(['namespace' => 'SystemLinkBundle\Http\JushuitanApi\V1\Action','prefix'=>'systemlink'], function($api) {
        // JushuitanErp api
        $api->any('jushuitan/oauth/callback', ['as' => 'jushuitan.oauth.callback',  'uses'=>'Oauth@callback']);

    });
});

