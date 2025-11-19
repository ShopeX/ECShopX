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
    // 来源相关api
    $api->group(['namespace' => 'FormBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/transcript',                   ['name'=>'创建成绩单','middleware'=>'activated', 'as' => 'transcript.create', 'uses' => 'Transcripts@createTranscript']);
        $api->get('/transcript/{transcript_id}',    ['name'=>'获取成绩单','middleware'=>'activated', 'as' => 'transcript.detail', 'uses' => 'Transcripts@getTranscript']);
        $api->patch('/transcript/{transcript_id}',  ['name'=>'获取成绩单','middleware'=>'activated', 'as' => 'transcript.update', 'uses' => 'Transcripts@updateTranscript']);
        $api->delete('/transcript/{transcript_id}', ['name'=>'删除成绩单','middleware'=>'activated', 'as' => 'transcript.delete', 'uses' => 'Transcripts@deleteTranscript']);

        $api->post('/usertranscript',               ['name'=>'创建用户成绩单','middleware'=>'activated','as' => 'usertranscript.create', 'uses' => 'UserTranscripts@createUserTranscript']);
        $api->get('/usertranscript',                ['name'=>'获取用户成绩单','middleware'=>'activated','as' => 'usertranscript.list', 'uses' => 'UserTranscripts@getUserTranscript']);
    });
});
