<?php
//$api = app('api.router');
/** @var Dingo\Api\Routing\Router $api */
$api = app('Dingo\Api\Routing\Router');

$app->router->group(['namespace' => 'WechatBundle\Http\Controllers'], function ($app) {
    require __DIR__.'/../routes/web.php';
});

if ($lumenRoutingKeyOne == 'wechatAuth') {
    require __DIR__.'/../routes/frontapi/qrcode.php';
} else {
    switch ($dingoRoutingKeyOne) {
        case 'admin' :
            require __DIR__.'/../routes/admin/aftersales.php';
            require __DIR__.'/../routes/admin/auth.php';
            require __DIR__.'/../routes/admin/reservation.php';
            require __DIR__.'/../routes/admin/trade.php';
            require __DIR__.'/../routes/admin/member.php';
            require __DIR__.'/../routes/admin/goods.php';
            require __DIR__.'/../routes/admin/orders.php';
            require __DIR__.'/../routes/admin/card.php';
            require __DIR__.'/../routes/admin/distributor.php';
            require __DIR__.'/../routes/admin/selfService.php';
            require __DIR__.'/../routes/admin/promotions.php';
            break;
        case ($dingoRoutingKeyOne == 'superadmin' || $dingoRoutingKeyOne == 'super') :
            require __DIR__.'/../routes/super/auth.php';
            require __DIR__.'/../routes/super/companys.php';
            require __DIR__.'/../routes/super/permission.php';
            require __DIR__.'/../routes/super/operators.php';
            require __DIR__.'/../routes/super/notice.php';
            require __DIR__.'/../routes/super/logistics.php';
            require __DIR__.'/../routes/super/datacube.php';
            require __DIR__.'/../routes/super/globalconfig.php';
            require __DIR__.'/../routes/super/espier.php';
            break;
        case 'systemlink' :
            require __DIR__.'/../routes/systemlink/ome.php';
            require __DIR__.'/../routes/systemlink/openapi.php';
            require __DIR__.'/../routes/systemlink/adapay.php';
            require __DIR__.'/../routes/systemlink/jushuitan.php';
            break;
        case ($dingoRoutingKeyOne == 'test' || $dingoRoutingKeyOne == 'ome'):
            require __DIR__.'/../routes/systemlink/ome.php';
            break;
        case 'third' :
            require __DIR__.'/../routes/api/third.php';
            require __DIR__.'/../routes/thirdparty/saascert.php';
            require __DIR__.'/../routes/thirdparty/customs.php';
            require __DIR__.'/../routes/thirdparty/hfpay.php';
            require __DIR__.'/../routes/thirdparty/kuaizhen.php';
            require __DIR__.'/../routes/thirdparty/icbcpay.php';
            require __DIR__.'/../routes/thirdparty/dm.php';
            break;
        case ($dingoRoutingKeyOne == 'openapi') :
            require __DIR__.'/../routes/thirdparty/openapi.php';
            break;
        case ($dingoRoutingKeyOne == 'thirdparty' || $dingoRoutingKeyOne == 'saaserp') :
            require __DIR__.'/../routes/thirdparty/saaserp.php';
            break;
        case 'h5app' :
            $filename = app('redis')->get('routecache:' . $lumenRoutingMd5);
            if ($filename) {
                require __DIR__.'/../routes/frontapi/' . $filename;
            } else {
                require __DIR__.'/../routes/admin/distributor.php';
                require __DIR__.'/../routes/frontapi/aftersales.php';
                require __DIR__.'/../routes/frontapi/adapay.php';
                require __DIR__.'/../routes/frontapi/auth.php';
                require __DIR__.'/../routes/frontapi/card.php';
                require __DIR__.'/../routes/frontapi/comments.php';
                require __DIR__.'/../routes/frontapi/companys.php';
                require __DIR__.'/../routes/frontapi/datacube.php';
                require __DIR__.'/../routes/frontapi/deposit.php';
                require __DIR__.'/../routes/frontapi/distributor.php';
                require __DIR__.'/../routes/frontapi/goods.php';
                require __DIR__.'/../routes/frontapi/hfpay.php';
                require __DIR__.'/../routes/frontapi/im.php';
                require __DIR__.'/../routes/frontapi/member.php';
                require __DIR__.'/../routes/frontapi/orders.php';
                require __DIR__.'/../routes/frontapi/pageSetting.php';
                require __DIR__.'/../routes/frontapi/pagestemplate.php';
                require __DIR__.'/../routes/frontapi/payment.php';
                require __DIR__.'/../routes/frontapi/point.php';
                require __DIR__.'/../routes/frontapi/pointsmallgoods.php';
                require __DIR__.'/../routes/frontapi/promotions.php';
                require __DIR__.'/../routes/frontapi/qrcode.php';
                require __DIR__.'/../routes/frontapi/rate.php';
                require __DIR__.'/../routes/frontapi/salesperson.php';
                require __DIR__.'/../routes/frontapi/trade.php';
                require __DIR__.'/../routes/frontapi/thire_party.php';
                require __DIR__.'/../routes/frontapi/merchant.php';
                require __DIR__.'/../routes/frontapi/community.php';
                require __DIR__.'/../routes/frontapi/employeepurchase.php';
                require __DIR__.'/../routes/frontapi/ugc.php';
            }
            break;
        case ($dingoRoutingKeyOne == 'wxapp' || $dingoRoutingKeyOne == 'wxa') :
            require __DIR__.'/../routes/frontapi/old.php';
            require __DIR__.'/../routes/api/weapp.php';
            require __DIR__.'/../routes/frontapi/reservation.php';
            require __DIR__.'/../routes/api/promotions.php';
            break;
        default :
            require __DIR__.'/../routes/api/auth.php';
            require __DIR__.'/../routes/api/aliminiapp.php';
            require __DIR__.'/../routes/api/companys.php';
            require __DIR__.'/../routes/api/wechat.php';
            require __DIR__.'/../routes/api/member.php';
            require __DIR__.'/../routes/api/CardVoucher.php';
            require __DIR__.'/../routes/api/weapp.php';
            require __DIR__.'/../routes/api/trade.php';
            require __DIR__.'/../routes/api/datacube.php';
            require __DIR__.'/../routes/api/deposit.php';
            require __DIR__.'/../routes/api/reservation.php';
            require __DIR__.'/../routes/api/goods.php';
            require __DIR__.'/../routes/api/comments.php';
            require __DIR__.'/../routes/api/transcript.php';
            require __DIR__.'/../routes/api/order.php';
            require __DIR__.'/../routes/api/openapi.php';
            require __DIR__.'/../routes/api/promotions.php';
            require __DIR__.'/../routes/api/distributor.php';
            require __DIR__.'/../routes/api/espier.php';
            require __DIR__.'/../routes/api/onecode.php';
            require __DIR__.'/../routes/api/aftersales.php';
            require __DIR__.'/../routes/api/popularize.php';
            require __DIR__.'/../routes/api/point.php';
            require __DIR__.'/../routes/api/selfService.php';
            require __DIR__.'/../routes/api/third.php';
            require __DIR__.'/../routes/api/notice.php';
            require __DIR__.'/../routes/api/im.php';
            require __DIR__.'/../routes/api/fapiao.php';
            require __DIR__.'/../routes/api/pagestemplate.php';
            require __DIR__.'/../routes/api/crossborder.php';
            require __DIR__.'/../routes/api/shopmenu.php';
            require __DIR__.'/../routes/api/dataAnalysis.php';
            require __DIR__.'/../routes/api/hfpay.php';
            require __DIR__.'/../routes/api/tdkset.php';
            require __DIR__.'/../routes/api/pointsmall.php';
            require __DIR__.'/../routes/api/adapay.php';
            require __DIR__.'/../routes/api/aliyunsms.php';
            require __DIR__.'/../routes/api/merchant.php';
            require __DIR__.'/../routes/api/community.php';
            require __DIR__.'/../routes/api/bspay.php';
            require __DIR__.'/../routes/api/employeepurchase.php';
            require __DIR__.'/../routes/api/ugc.php';
    }
}

return $app;
