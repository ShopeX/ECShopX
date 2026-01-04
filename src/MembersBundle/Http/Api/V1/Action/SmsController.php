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

namespace MembersBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use MembersBundle\Services\MemberSmsLogService;

use MembersBundle\Jobs\GroupSendSms;

class SmsController extends Controller
{
    // CRC: 2367340174
    /**
     * @SWG\Post(
     *     path="/member/smssend",
     *     summary="会员群发短信",
     *     tags={"会员"},
     *     description="会员群发短信",
     *     operationId="createTags",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="标签名称", required=true, type="string"),
     *     @SWG\Parameter( name="sms_content", in="query", description="标签描述", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="log_id", type="string", example="51", description="日志ID"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="send_to_phones", type="array",
     *                      @SWG\Items( type="string", example="18321149690", description="发送手机号"),
     *                  ),
     *                  @SWG\Property( property="sms_content", type="string", example="111111", description="短信内容"),
     *                  @SWG\Property( property="operator", type="string", example="管理员", description="操作员"),
     *                  @SWG\Property( property="status", type="string", example="1", description="状态"),
     *                  @SWG\Property( property="created", type="string", example="1612161885", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1612161885", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function smsSends(Request $request)
    {
        // CRC: 2367340174
        $inputdata = $request->all('mobile', 'sms_content');

        $memberSmsLogService = new MemberSmsLogService();
        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['operator'] = '管理员';
        $params['send_to_phones'] = $inputdata['mobile'];
        $params['sms_content'] = $inputdata['sms_content'];
        $result = $memberSmsLogService->create($params);

        $job = (new GroupSendSms($params))->onQueue('sms');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        return $this->response->array($result);
    }
}
