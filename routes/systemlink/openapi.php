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
	
    $api->group(['namespace' => 'SystemLinkBundle\Http\OpenApi\V1\Action','prefix'=>'systemlink','middleware' => ['SystemLinkOpenapiCheck']], function($api) {
        // openapi
        $api->post('openapi/{method}', ['as' => 'systemlink.openapi',  'uses'=>'Verify@openApi']);

    });
});

