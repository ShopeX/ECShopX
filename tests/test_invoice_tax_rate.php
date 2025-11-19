<?php

require_once __DIR__ . '/vendor/autoload.php';

// 测试发票商品税率查询功能
function testInvoiceTaxRate() {
    echo "=== 测试发票商品税率查询功能 ===\n";
    
    // 模拟商品数据
    $items = [
        [
            'item_id' => 1001,
            'item_name' => '测试商品1',
            'item_category' => 1,
            'num' => 2,
            'item_fee' => 20000, // 200元，以分为单位
            'discount_fee' => 0,
            'unit' => '件'
        ],
        [
            'item_id' => 1002,
            'item_name' => '测试商品2',
            'item_category' => 2,
            'num' => 1,
            'item_fee' => 15000, // 150元，以分为单位
            'discount_fee' => 1000, // 10元优惠
            'unit' => '个'
        ],
        [
            'item_id' => 1003,
            'item_name' => '测试商品3',
            'item_category' => 0, // 无分类，应该使用默认税率
            'num' => 3,
            'item_fee' => 30000, // 300元，以分为单位
            'discount_fee' => 0,
            'unit' => '盒'
        ]
    ];
    
    $companyId = 1;
    
    echo "测试商品数据:\n";
    foreach ($items as $item) {
        echo "- 商品ID: {$item['item_id']}, 名称: {$item['item_name']}, 分类: {$item['item_category']}\n";
    }
    
    // 模拟分类税率数据
    $categoryRates = [
        1 => 0.13, // 13%税率
        2 => 0.09, // 9%税率
    ];
    
    // 模拟默认税率
    $defaultRate = 0.06; // 6%默认税率
    
    echo "\n分类税率配置:\n";
    foreach ($categoryRates as $categoryId => $rate) {
        echo "- 分类{$categoryId}: " . ($rate * 100) . "%\n";
    }
    echo "- 默认税率: " . ($defaultRate * 100) . "%\n";
    
    // 模拟税率查询结果
    $itemRateMap = [];
    foreach ($items as $item) {
        $categoryId = $item['item_category'];
        if ($categoryId && isset($categoryRates[$categoryId])) {
            $rate = $categoryRates[$categoryId];
        } else {
            $rate = $defaultRate;
        }
        $itemRateMap[$item['item_id']] = $rate;
    }
    
    echo "\n税率查询结果:\n";
    foreach ($itemRateMap as $itemId => $rate) {
        echo "- 商品{$itemId}: " . ($rate * 100) . "%\n";
    }
    
    // 模拟百旺API参数格式
    echo "\n百旺API商品明细格式:\n";
    foreach ($items as $item) {
        $itemRate = $itemRateMap[$item['item_id']] ?? $defaultRate;
        $taxRatePercent = number_format($itemRate * 100, 0);
        $unitPrice = ($item['item_fee'] - $item['discount_fee']) / $item['num'] / 100; // 转换为元
        
        echo "- 商品: {$item['item_name']}\n";
        echo "  税率: {$taxRatePercent}%\n";
        echo "  数量: {$item['num']}\n";
        echo "  单价: {$unitPrice}元\n";
        echo "  单位: {$item['unit']}\n";
        echo "\n";
    }
    
    echo "=== 测试完成 ===\n";
}

// 运行测试
testInvoiceTaxRate(); 