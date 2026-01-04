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

namespace SystemLinkBundle\Http\JushuitanApi\V1\Action;

use Illuminate\Http\Request;

use SystemLinkBundle\Http\Controllers\Controller as Controller;

class Verify extends Controller
{

    public function jushuitanApi($companyId, Request $request)
    {
        // CRC: 2367340174
        $params = $request->query();
        app('log')->debug('jushuitan::callback::request::params=>:', $params);
        foreach((array)$params as $key=>$val)
        {
            $params[$key] = trim($val);
        }
        $jushuitanAct = [
            'logistics.upload'  => 'Order@orderDelivery', // 订单发货
            'inventory.upload' => 'Item@updateItemStore', // 更新商品库存        
            'refund.goods' => 'Aftersales@updateAftersalesStatus', // 更新售后申请单
        ];

        if (!isset($params['method']) || !isset($jushuitanAct[trim($params['method'])]) || !$jushuitanAct[trim($params['method'])])
        {
            app('log')->debug('jushuitan request result=>:'.$params['method'].'接口不存在');
            $this->api_response_shuyun('fail', '接口不存在');
        }

        list($ctl, $act) = explode('@', trim($jushuitanAct[$params['method']]));

        if (!$ctl || !$act)
        {
            app('log')->debug('jushuitan request result=>:'.$ctl.'或'.$act.'方法不存在');
            $this->api_response_shuyun('fail', '方法不存在');
        }

        $className = 'SystemLinkBundle\Http\JushuitanApi\V1\Action\\'.$ctl;

        $ctlObj = new $className();

        return  $ctlObj->$act($companyId, $request);
    }

}
