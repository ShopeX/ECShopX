<?php
$wap_base = env("WAP_BASE_URL","https://wap.kukahome.com");

return [
    'appKey' => env('KUJIALE_APPKEY',''),
    'appSecret' => env('KUJIALE_APPSECRET',''),
    'appUid' => env('KUJIALE_APPUID',''),
    'apiUrl' => [
        'cat' => 'https://openapi.kujiale.com/v2/commodity/cat',  //获取商品类目
        'tag' => 'https://openapi.kujiale.com/v2/commodity/tag',  //获取商品标签
        'search' => 'https://openapi.kujiale.com/v2/commodity/search',  //搜索商品
        'brand' => 'https://openapi.kujiale.com/v2/commodity/brand',  //搜索商品
        'search' => 'https://openapi.kujiale.com/v2/commodity/search',  //搜索商品
        'detail' => 'https://openapi.kujiale.com/v2/commodity/detail',  //商品详情
        'designer_works' => 'https://openapi.kujiale.com/v2/designeroc/design/excellent/list',  //设计师方案作品
        'designer_works_detail' => 'https://openapi.kujiale.com/v2/design/{designId}/basic/v2',  //设计师方案作品详情
        'designer_works_tag' => 'https://openapi.kujiale.com/v2/design/new-tag',  //设计师方案作品标签
        'designer_tags_list' => 'https://openapi.kujiale.com/v2/design/new-tag/category/list',  //方案标签
        'designer_works_pic' => 'https://openapi.kujiale.com/v2/renderpic/list',  //方案全景图
        'designer_room_brand_goods' => 'https://openapi.kujiale.com/v2/panopic/{picId}/currentroom/brandgoods',  //方案全景图

        'access_token'      => 'https://openapi.kujiale.com/v2/sso/token',      //获取一次性access_token
        'register_user'     => 'https://openapi.kujiale.com/v2/register',       //绑定设计圈账号，通过邮箱
        'search_account'    => 'https://openapi.kujiale.com/v2/account/search', //查找设计师账号
    ],

    'webViewUrl' => $wap_base.'/pages/commodity/scene-detail' //web-view url
];