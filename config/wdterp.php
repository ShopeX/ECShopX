<?php
// 旺店通旗舰版接口配置文件

return [
    'api_base_url' => env('WDTERP_BASE_URL'),
    'methods' => [
        'shop_query' => 'setting.Shop.queryShop',
        'item_add' => 'goods.Goods.push',
        'item_api_add' => 'goods.ApiGoods.upload',
        'stock_get_wait_sync' => 'sales.StockSync.getSelfWaitSyncIdListOpen',
        'store_query' => 'sales.StockSync.calcStock',
        'store_sync_success' => 'sales.StockSync.syncSuccess',
        'store_sync_fail' => 'sales.StockSync.syncFail',
        'order_add' => 'sales.RawTrade.pushSelf2',
        'logistics_get_wait_sync' => 'sales.LogisticsSync.getSyncListExt',
        'logistics_sync_success' => 'sales.LogisticsSync.update',
        'after_sale_add' => 'aftersales.refund.RawRefund.upload2',
        'after_sale_query' => 'aftersales.refund.Refund.search',
    ],
];
