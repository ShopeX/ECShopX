<?php
return [
    "goods"=>[
        "rpc_type"=>"local",
        "base_url"=>"",
        // 生产环境务必通过 GOODS_SERVICE_SIGN 注入强随机签名，勿使用默认值
        "sign"=> env('GOODS_SERVICE_SIGN', '123'),
    ],
    "trade"=>[
        "rpc_type"=>"rpc",
        "base_url"=>"http://192.168.1.2:8080/api/",
        "sign"=> env('TRADE_SERVICE_SIGN', '123'),
    ],
    "trade2"=>[
        "rpc_type"=>"grpc",
        "base_url"=>"http://127.0.0.1",
    ],
];
