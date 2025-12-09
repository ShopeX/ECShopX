<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
