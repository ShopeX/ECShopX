<?php

// 测试百旺签名
function testBaiwangSign() {
    // 模拟配置
    $config = [
        'appKey' => '1002948',
        'appSecret' => '223998c6-5b76-4724-b5c9-666ff4215b45',
        'token' => 'test_token_123',
        'taxNo' => '338888888888SMB'
    ];
    
    // 协议参数
    $protocolParams = [
        'method' => 'baiwang.s.outputinvoice.query',
        'appKey' => $config['appKey'],
        'token' => $config['token'],
        'timestamp' => '1751375777327',
        'version' => '6.0',
        'format' => 'json',
        'type' => 'sync',
        'requestId' => 'bw_6874c83a4eaf58.36123267'
    ];
    
    // 业务参数
    $bodyParams = [
        'taxNo' => $config['taxNo']
    ];
    $body = json_encode($bodyParams, JSON_UNESCAPED_UNICODE);
    
    // 生成签名
    $protocolKeys = ['appKey', 'format', 'method', 'timestamp', 'token', 'type', 'version', 'requestId'];
    $signParams = [];
    foreach ($protocolKeys as $k) {
        if (isset($protocolParams[$k])) {
            $signParams[$k] = $protocolParams[$k];
        }
    }
    ksort($signParams);

    $str = '';
    foreach ($signParams as $k => $v) {
        if ($v === '' || $v === null) continue;
        $str .= $k . $v;
    }

    $secret = $config['appSecret'];
    $signStr = $secret . $str . $body . $secret;
    $sign = strtoupper(md5($signStr));
    
    echo "=== 百旺API签名测试 ===\n";
    echo "协议参数: " . json_encode($signParams, JSON_UNESCAPED_UNICODE) . "\n";
    echo "协议字符串: " . $str . "\n";
    echo "业务参数: " . $body . "\n";
    echo "密钥: " . $secret . "\n";
    echo "签名字符串: " . $signStr . "\n";
    echo "签名结果: " . $sign . "\n";
    
    // 构建完整URL
    $protocolParams['sign'] = $sign;
    $url = 'https://sandbox-openapi.baiwang.com/router/rest?' . http_build_query($protocolParams);
    echo "完整URL: " . $url . "\n";
    echo "请求体: " . $body . "\n";
}

// 运行测试
testBaiwangSign(); 